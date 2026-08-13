<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\ResellerCashRemittance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gère les remises cash du vendeur vers KardAfrica via E-Billing.
 *
 * Quand un vendeur encaisse en physique X FCFA, ce cash ne lui appartient pas.
 * Il doit le reverser via E-Billing (Mobile Money / carte) pour reconstituer
 * son float de vente. Ce contrôleur orchestre le tour : init portail, attente
 * du callback, validation atomique (cash_to_remit-=, wallet_balance+=).
 */
class RemittanceController extends Controller
{
    /**
     * Page d'accueil : montant à remettre + historique.
     */
    public function index()
    {
        /** @var Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $pending  = ResellerCashRemittance::where('reseller_id', $reseller->id)
            ->where('status', ResellerCashRemittance::STATUS_PENDING)
            ->latest()
            ->take(5)
            ->get();

        // Paginé : l'historique était coupé à 20 sans aucun moyen de voir plus loin.
        $history = ResellerCashRemittance::where('reseller_id', $reseller->id)
            ->whereIn('status', [
                ResellerCashRemittance::STATUS_COMPLETED,
                ResellerCashRemittance::STATUS_FAILED,
                ResellerCashRemittance::STATUS_CANCELLED,
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('vendor.remittance.index', [
            'reseller' => $reseller,
            'pending'  => $pending,
            'history'  => $history,
        ]);
    }

    /**
     * Initialise une remise via E-Billing :
     * 1. Crée un ResellerCashRemittance pending pour le montant choisi
     * 2. Appelle futursowax/portal.php avec une référence unique
     * 3. Renvoie portal_url au front pour redirection
     */
    public function init(Request $request)
    {
        /** @var Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $request->validate([
            'phone'  => 'nullable|string|max:20',
            'email'  => 'nullable|email|max:120',
        ]);

        // Politique : on reverse TOUJOURS l'intégralité du cash à remettre.
        // L'amount client est ignoré pour empêcher toute remise partielle —
        // le vendeur doit clore son cash à zéro pour reconstituer son float.
        $cashToRemit = (float) $reseller->cash_to_remit;
        $headroom    = (float) $reseller->max_wallet - (float) $reseller->wallet_balance;

        if ($cashToRemit <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Aucun cash à reverser pour le moment.",
            ], 422);
        }

        if ($cashToRemit > $headroom) {
            return response()->json([
                'success' => false,
                'message' => "Ton wallet ne peut pas accueillir " . number_format($cashToRemit, 0, ',', ' ') . " FCFA. Vends quelques cartes pour libérer de la place avant de reverser.",
            ], 422);
        }

        $amount = $cashToRemit;

        $externalRef = 'REMIT_' . time() . '_' . strtoupper(bin2hex(random_bytes(4)));

        try {
            $remittance = DB::transaction(function () use ($reseller, $amount, $externalRef) {
                return ResellerCashRemittance::create([
                    'reseller_id'        => $reseller->id,
                    'amount'             => $amount,
                    'external_reference' => $externalRef,
                    'status'             => ResellerCashRemittance::STATUS_PENDING,
                    'payment_method'     => 'ebilling',
                ]);
            });

            // Init portail E-Billing
            $payload = [
                'amount'            => (int) round($amount),
                'short_description' => "Remise cash {$reseller->vendor_code}",
                'reference'         => $externalRef,
                'email'             => $request->input('email') ?: ($reseller->email ?: 'noreply@kardafrica.com'),
                'msisdn'            => $this->formatPhone($request->input('phone') ?: ($reseller->phone ?: '24174000000')),
                'name'              => $reseller->name ?: 'Vendeur Kardafrica',
                'callback_url'      => url('/vendor/remittance/return?ref=' . $externalRef),
                'format'            => 'json',
            ];

            $response = Http::timeout(20)->acceptJson()->asForm()
                ->post(config('services.payment_backend.init_url'), $payload);

            Log::info('Remittance init: portal.php response', [
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                $body = $response->json();
                $remittance->update([
                    'status' => ResellerCashRemittance::STATUS_FAILED,
                    'notes'  => 'E-Billing init: ' . ($body['error'] ?? 'HTTP ' . $response->status()),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $body['error'] ?? 'Erreur lors de l\'initialisation du paiement E-Billing.',
                ], 502);
            }

            $body      = $response->json();
            $data      = $body['data'] ?? [];
            $portalUrl = $data['portal_url'] ?? null;
            $billId    = $data['bill_id'] ?? null;

            if (!$portalUrl) {
                $remittance->update([
                    'status' => ResellerCashRemittance::STATUS_FAILED,
                    'notes'  => 'Réponse E-Billing incomplète',
                ]);
                return response()->json(['success' => false, 'message' => 'Réponse E-Billing incomplète.'], 502);
            }

            $remittance->update([
                'bill_id'    => $billId,
                'portal_url' => $portalUrl,
            ]);

            return response()->json([
                'success'            => true,
                'portal_url'         => $portalUrl,
                'external_reference' => $externalRef,
                'remittance_id'      => $remittance->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Remittance init exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Page de retour depuis E-Billing — affiche un loader qui poll finalize.
     */
    public function paymentReturn(Request $request)
    {
        $ref = $request->query('ref');
        if (!$ref) return redirect()->route('vendor.remittance.index')->with('error', 'Référence manquante.');

        return view('vendor.remittance.verify', ['ref' => $ref]);
    }

    /**
     * Endpoint AJAX qui poll E-Billing et finalise si paiement OK.
     */
    public function paymentFinalize(Request $request)
    {
        $ref = $request->input('ref') ?? $request->input('external_reference');
        if (!$ref) {
            return response()->json(['success' => false, 'message' => 'Référence manquante.'], 400);
        }

        /** @var Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();
        if (!$reseller) return response()->json(['success' => false, 'message' => 'Session expirée.'], 401);

        $remittance = ResellerCashRemittance::where('external_reference', $ref)
            ->where('reseller_id', $reseller->id)
            ->first();

        if (!$remittance) {
            return response()->json(['success' => false, 'message' => 'Remise introuvable.'], 404);
        }

        if ($remittance->status === ResellerCashRemittance::STATUS_COMPLETED) {
            return response()->json([
                'success'      => true,
                'redirect_url' => route('vendor.remittance.index'),
                'message'      => 'Remise déjà confirmée.',
            ]);
        }

        try {
            // 1. Vérifier statut E-Billing — HORS transaction (appel HTTP externe)
            $check = Http::timeout(10)->get(config('services.payment_backend.check_url'), [
                'external_reference' => $ref,
            ]);

            $status = 'pending';
            $body   = null;
            if ($check->successful()) {
                $body = $check->json();
                $status = $body['status'] ?? ($body['data']['status'] ?? 'pending');
            }

            $isPaid = in_array($status, ['completed', 'success'], true);

            if (!$isPaid) {
                return response()->json([
                    'success' => false,
                    'message' => "Paiement non confirmé (statut : {$status}). Réessaie dans quelques secondes.",
                    'status'  => $status,
                ]);
            }

            // M19 — Réconciliation du montant : si le PSP renvoie le montant
            // réellement payé, il doit correspondre au montant attendu. Défensif :
            // check_status.php ne documente pas ce champ de façon garantie, on ne
            // bloque donc que s'il est PRÉSENT et DIFFÉRENT.
            $paidAmount = $body['data']['amount'] ?? $body['amount'] ?? null;
            if ($paidAmount !== null && (int) round((float) $paidAmount) !== (int) round((float) $remittance->amount)) {
                Log::warning('Remittance finalize: montant PSP différent du montant attendu', [
                    'ref'      => $ref,
                    'expected' => (float) $remittance->amount,
                    'paid'     => (float) $paidAmount,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant payé ne correspond pas au montant de la remise. Contacte le support.',
                ], 422);
            }

            // 2. Transaction courte : verrou de la remise + re-test du statut
            //    À L'INTÉRIEUR (idempotence anti double-polling), puis validation.
            $already = DB::transaction(function () use ($reseller, $remittance) {
                $locked = ResellerCashRemittance::where('id', $remittance->id)->lockForUpdate()->first();

                if ($locked->status === ResellerCashRemittance::STATUS_COMPLETED) {
                    return true; // déjà finalisée par un poll concurrent
                }

                $reseller->confirmCashRemittance((float) $locked->amount, $locked->external_reference);
                $locked->update([
                    'status'       => ResellerCashRemittance::STATUS_COMPLETED,
                    'processed_at' => now(),
                ]);
                return false;
            });

            return response()->json([
                'success'      => true,
                'redirect_url' => route('vendor.remittance.index'),
                'message'      => $already
                    ? 'Remise déjà confirmée.'
                    : 'Remise confirmée — ton wallet a été reconstitué.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Remittance finalize exception', ['ref' => $ref, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatPhone(?string $phone): string
    {
        if (!$phone) return '24174000000';
        $cleaned = preg_replace('/\D/', '', $phone);
        if (str_starts_with($cleaned, '00'))  $cleaned = substr($cleaned, 2);
        if (str_starts_with($cleaned, '241')) return $cleaned;
        if (str_starts_with($cleaned, '0'))   return '241' . substr($cleaned, 1);
        return '241' . $cleaned;
    }
}
