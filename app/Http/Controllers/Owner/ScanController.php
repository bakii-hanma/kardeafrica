<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\MerchantCardPurchase;
use App\Models\MerchantCardRedemption;
use App\Support\MerchantCardCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Scan & validation au comptoir.
 *
 * Le propriétaire saisit un code 8 chiffres + PIN 4 chiffres (ou scanne le QR
 * de la carte) pour valider un achat client. La transaction débite le solde
 * restant et journalise un MerchantCardRedemption — lockForUpdate sur la
 * purchase pour éviter les doubles dépenses en cas de scan simultané.
 */
class ScanController extends Controller
{
    public function index()
    {
        return view('owner.scan.index', [
            'owner' => Auth::guard('card_owner')->user(),
        ]);
    }

    /**
     * Recherche une purchase via code+PIN ou via QR payload.
     * Retourne JSON avec les détails de la carte pour confirmation côté UI.
     */
    public function lookup(Request $request)
    {
        $owner = Auth::guard('card_owner')->user();

        $mode = $request->input('mode', 'manual'); // 'manual' | 'qr'

        if ($mode === 'qr') {
            $request->validate(['qr' => ['required', 'string', 'min:20']]);
            $decoded = MerchantCardCode::decodeQrPayload($request->input('qr'));
            if (!$decoded || empty($decoded['pid'])) {
                return response()->json(['ok' => false, 'message' => 'QR code invalide ou corrompu.'], 422);
            }
            $purchase = MerchantCardPurchase::with('merchantCard:id,name,visual_url,card_owner_id,validity_months')
                ->find((int) $decoded['pid']);
        } else {
            $request->validate([
                'code' => ['required', 'string', 'size:8'],
                'pin'  => ['required', 'string', 'size:4'],
            ]);

            // SÉCURITÉ (H2) : anti brute-force du PIN — max 5 tentatives ÉCHOUÉES
            // par minute, par propriétaire + code carte visé.
            $rateKey = 'scan-pin:' . $owner->id . ':' . $request->input('code');
            if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Trop de tentatives. Réessaie dans une minute.',
                ], 429);
            }

            $purchase = MerchantCardPurchase::with('merchantCard:id,name,visual_url,card_owner_id,validity_months')
                ->where('unique_code', $request->input('code'))
                ->first();

            // Le PIN n'est plus comparable en SQL (seul son condensat est stocké) :
            // il se vérifie ici. Un PIN faux doit être indistinguable d'un code
            // inconnu, sinon l'écran devient un oracle qui confirme l'existence
            // d'une carte à qui n'en connaît que le numéro.
            if ($purchase && !$purchase->checkPin($request->input('pin'))) {
                $purchase = null;
            }

            if (!$purchase) {
                // Compte uniquement les échecs de code/PIN
                RateLimiter::hit($rateKey, 60);
            } else {
                RateLimiter::clear($rateKey);
            }
        }

        if (!$purchase) {
            return response()->json([
                'ok'      => false,
                'message' => $mode === 'qr'
                    ? 'Cette carte n\'a pas été trouvée.'
                    : 'Code ou PIN invalide.',
            ], 404);
        }

        // Sécurité : la carte doit appartenir à ce propriétaire.
        if ($purchase->merchantCard?->card_owner_id !== $owner->id) {
            return response()->json([
                'ok'      => false,
                'message' => 'Cette carte n\'est pas rattachée à ton compte.',
            ], 403);
        }

        // Vérifie l'état utilisable
        $reason = $this->unredeemableReason($purchase);
        if ($reason) {
            return response()->json([
                'ok'        => false,
                'message'   => $reason,
                'purchase'  => $this->purchasePayload($purchase),
            ], 422);
        }

        return response()->json([
            'ok'       => true,
            'purchase' => $this->purchasePayload($purchase),
        ]);
    }

    /**
     * Débite le solde d'une purchase. Bloque la purchase (FOR UPDATE) le temps
     * de calculer balance_after, créer le journal MerchantCardRedemption et
     * mettre à jour le statut (active → partially_used → fully_used).
     */
    public function redeem(Request $request)
    {
        $owner = Auth::guard('card_owner')->user();

        $request->validate([
            'purchase_id' => ['required', 'integer'],
            'amount'      => ['required', 'numeric', 'min:1'],
            'pin'         => ['required', 'string', 'size:4'],
            'scan_method' => ['nullable', 'string', 'in:qr,code'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        // SÉCURITÉ (H2) : anti brute-force du PIN — max 5 tentatives ÉCHOUÉES
        // par minute, par propriétaire + purchase visée.
        $rateKey = 'scan-pin:' . $owner->id . ':' . $request->input('purchase_id');
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Trop de tentatives. Réessaie dans une minute.',
            ], 429);
        }

        try {
            $redemption = DB::transaction(function () use ($request, $owner, $rateKey) {
                $purchase = MerchantCardPurchase::with('merchantCard:id,name,card_owner_id')
                    ->lockForUpdate()
                    ->find($request->input('purchase_id'));

                if (!$purchase) {
                    throw ValidationException::withMessages(['amount' => 'Achat introuvable.']);
                }

                if ($purchase->merchantCard?->card_owner_id !== $owner->id) {
                    throw ValidationException::withMessages(['amount' => 'Cette carte n\'est pas la tienne.']);
                }

                // Le PIN n'est plus stocké en clair : seul son condensat fait foi.
                if (!$purchase->checkPin($request->input('pin'))) {
                    // Le compteur est en cache : il survit au rollback de la transaction
                    RateLimiter::hit($rateKey, 60);
                    throw ValidationException::withMessages(['pin' => 'PIN invalide.']);
                }

                // PIN correct → on remet le compteur à zéro
                RateLimiter::clear($rateKey);

                $reason = $this->unredeemableReason($purchase);
                if ($reason) {
                    throw ValidationException::withMessages(['amount' => $reason]);
                }

                $amount = (float) $request->input('amount');
                $remaining = (float) $purchase->remaining_balance;

                if ($amount > $remaining) {
                    throw ValidationException::withMessages([
                        'amount' => "Montant supérieur au solde restant ({$remaining} FCFA).",
                    ]);
                }

                $balanceBefore = $remaining;
                $balanceAfter  = round($remaining - $amount, 2);

                $redemption = MerchantCardRedemption::create([
                    'merchant_card_purchase_id' => $purchase->id,
                    'merchant_user_id'          => null,
                    'amount_used'               => $amount,
                    'balance_before'            => $balanceBefore,
                    'balance_after'             => $balanceAfter,
                    'scan_method'               => $request->input('scan_method', 'code'),
                    'notes'                     => $request->input('notes'),
                    'redeemed_at'               => now(),
                ]);

                // Met à jour la purchase
                $newStatus = $balanceAfter <= 0
                    ? MerchantCardPurchase::STATUS_FULLY_USED
                    : MerchantCardPurchase::STATUS_PARTIALLY_USED;

                $purchase->update([
                    'remaining_balance' => $balanceAfter,
                    'status'            => $newStatus,
                ]);

                return $redemption;
            });
        } catch (ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        }

        return response()->json([
            'ok'         => true,
            'message'    => 'Validation enregistrée.',
            'redemption' => [
                'id'             => $redemption->id,
                'amount_used'    => (float) $redemption->amount_used,
                'balance_before' => (float) $redemption->balance_before,
                'balance_after'  => (float) $redemption->balance_after,
                'redeemed_at'    => $redemption->redeemed_at?->toIso8601String(),
            ],
        ]);
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function unredeemableReason(MerchantCardPurchase $p): ?string
    {
        // Vente revendeur non finalisée : le code est inerte tant que le
        // revendeur n'a pas « récupéré » la carte (débit wallet + activation).
        if ($p->status === MerchantCardPurchase::STATUS_INACTIVE) {
            return 'Cette carte n\'a pas encore été activée (vente non finalisée).';
        }
        if ($p->payment_status !== MerchantCardPurchase::PAYMENT_PAID) {
            return 'Cette carte n\'a pas été payée.';
        }
        if ($p->status === MerchantCardPurchase::STATUS_FULLY_USED) {
            return 'Cette carte est déjà épuisée.';
        }
        if ($p->status === MerchantCardPurchase::STATUS_CANCELLED) {
            return 'Cette carte a été annulée.';
        }
        if ($p->status === MerchantCardPurchase::STATUS_EXPIRED || $p->isExpired()) {
            return 'Cette carte est expirée.';
        }
        if ((float) $p->remaining_balance <= 0) {
            return 'Solde épuisé.';
        }
        return null;
    }

    private function purchasePayload(MerchantCardPurchase $p): array
    {
        return [
            'id'                => $p->id,
            'card_name'         => $p->merchantCard?->name,
            'card_visual'       => $p->merchantCard?->visual_url ? asset($p->merchantCard->visual_url) : null,
            'buyer_name'        => $p->buyer_name,
            'buyer_phone'       => $p->buyer_phone,
            'unique_code'       => $p->unique_code,
            'amount'            => (float) $p->amount,
            'remaining_balance' => (float) $p->remaining_balance,
            'status'            => $p->status,
            'expires_at'        => $p->expires_at?->translatedFormat('d M Y'),
            'paid_at'           => $p->paid_at?->translatedFormat('d M Y'),
        ];
    }
}
