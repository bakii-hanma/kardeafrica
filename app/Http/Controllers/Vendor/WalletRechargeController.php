<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\WalletRecharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Recharge wallet marchand via Airtel Money / Moov Money / carte (E-Billing).
 *
 * Le marchand a une cagnotte (wallet_balance). Quand elle est basse, il peut
 * la recharger en payant via Airtel Money (compte marchand) ou autre.
 * Le flow est l'OPPOSÉ du remittance : ici l'argent ENTRE dans le wallet.
 */
class WalletRechargeController extends Controller
{
    /** Montants suggérés rapides */
    public const QUICK_AMOUNTS = [10000, 20000, 50000, 100000];

    /** Page d'accueil : montant courant + form + historique */
    public function index()
    {
        /** @var Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $pending = WalletRecharge::where('reseller_id', $reseller->id)
            ->where('status', WalletRecharge::STATUS_PENDING)
            ->latest()
            ->take(5)
            ->get();

        $history = WalletRecharge::where('reseller_id', $reseller->id)
            ->whereIn('status', [
                WalletRecharge::STATUS_COMPLETED,
                WalletRecharge::STATUS_FAILED,
                WalletRecharge::STATUS_CANCELLED,
            ])
            ->latest()
            ->take(20)
            ->get();

        $headroom = max(0, (float) $reseller->max_wallet - (float) $reseller->wallet_balance);

        return view('vendor.wallet.recharge', [
            'reseller'      => $reseller,
            'pending'       => $pending,
            'history'       => $history,
            'headroom'      => $headroom,
            'quickAmounts'  => self::QUICK_AMOUNTS,
        ]);
    }

    /**
     * Initialise une recharge via E-Billing :
     * 1. Valide le montant + le plafond wallet
     * 2. Crée un WalletRecharge pending
     * 3. Appelle futursowax/portal.php (Airtel/Moov/carte au choix de l'user)
     * 4. Renvoie portal_url au front pour redirection
     */
    public function init(Request $request)
    {
        /** @var Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $validated = $request->validate([
            'amount' => 'required|integer|min:1000|max:1000000',
            'phone'  => 'nullable|string|max:20',
            'email'  => 'nullable|email|max:120',
        ]);

        $amount   = (int) $validated['amount'];
        $headroom = (float) $reseller->max_wallet - (float) $reseller->wallet_balance;

        if ($amount > $headroom) {
            return response()->json([
                'success' => false,
                'message' => "Recharge max possible : " . number_format($headroom, 0, ',', ' ') . " FCFA (plafond wallet atteint).",
            ], 422);
        }

        $externalRef = 'RECH_' . time() . '_' . rand(1000, 9999);

        try {
            $recharge = DB::transaction(function () use ($reseller, $amount, $externalRef) {
                return WalletRecharge::create([
                    'reseller_id'        => $reseller->id,
                    'amount'             => $amount,
                    'external_reference' => $externalRef,
                    'status'             => WalletRecharge::STATUS_PENDING,
                    'payment_method'     => 'ebilling',
                ]);
            });

            $payload = [
                'amount'            => $amount,
                'short_description' => "Recharge cagnotte {$reseller->vendor_code}",
                'reference'         => $externalRef,
                'email'             => $validated['email'] ?? $reseller->email ?? 'noreply@kardafrica.com',
                'msisdn'            => $this->formatPhone($validated['phone'] ?? $reseller->phone ?? '24174000000'),
                'name'              => $reseller->name ?: 'Marchand Kardafrica',
                'callback_url'      => url('/vendor/wallet/recharge/return?ref=' . $externalRef),
                'format'            => 'json',
            ];

            $response = Http::timeout(20)->acceptJson()->asForm()
                ->post(config('services.payment_backend.init_url'), $payload);

            Log::info('Wallet recharge init: portal response', [
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                $body = $response->json();
                $recharge->update([
                    'status' => WalletRecharge::STATUS_FAILED,
                    'notes'  => 'E-Billing init: ' . ($body['error'] ?? 'HTTP ' . $response->status()),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $body['error'] ?? "Erreur lors de l'initialisation du paiement.",
                ], 502);
            }

            $body      = $response->json();
            $data      = $body['data'] ?? [];
            $portalUrl = $data['portal_url'] ?? null;
            $billId    = $data['bill_id'] ?? null;

            if (!$portalUrl) {
                $recharge->update([
                    'status' => WalletRecharge::STATUS_FAILED,
                    'notes'  => 'Réponse E-Billing incomplète',
                ]);
                return response()->json(['success' => false, 'message' => 'Réponse E-Billing incomplète.'], 502);
            }

            $recharge->update([
                'bill_id'    => $billId,
                'portal_url' => $portalUrl,
            ]);

            return response()->json([
                'success'            => true,
                'portal_url'         => $portalUrl,
                'external_reference' => $externalRef,
                'recharge_id'        => $recharge->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Wallet recharge init exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    /** Page de retour depuis E-Billing — affiche un loader qui poll finalize */
    public function paymentReturn(Request $request)
    {
        $ref = $request->query('ref');
        if (!$ref) return redirect()->route('vendor.wallet.recharge')->with('error', 'Référence manquante.');

        return view('vendor.wallet.recharge-verify', ['ref' => $ref]);
    }

    /** Endpoint AJAX qui poll E-Billing et finalise si paiement OK */
    public function paymentFinalize(Request $request)
    {
        $ref = $request->input('ref') ?? $request->input('external_reference');
        if (!$ref) {
            return response()->json(['success' => false, 'message' => 'Référence manquante.'], 400);
        }

        /** @var Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();
        if (!$reseller) return response()->json(['success' => false, 'message' => 'Session expirée.'], 401);

        $recharge = WalletRecharge::where('external_reference', $ref)
            ->where('reseller_id', $reseller->id)
            ->first();

        if (!$recharge) {
            return response()->json(['success' => false, 'message' => 'Recharge introuvable.'], 404);
        }

        if ($recharge->status === WalletRecharge::STATUS_COMPLETED) {
            return response()->json([
                'success'      => true,
                'redirect_url' => route('vendor.wallet.recharge'),
                'message'      => 'Recharge déjà confirmée.',
            ]);
        }

        try {
            $check = Http::timeout(10)->get(config('services.payment_backend.check_url'), [
                'external_reference' => $ref,
            ]);

            $status = 'pending';
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

            // Atomique : crédit wallet + marque recharge comme complétée
            DB::transaction(function () use ($reseller, $recharge) {
                $reseller->refresh();
                $reseller->credit(
                    (float) $recharge->amount,
                    null,
                    "Recharge Airtel/E-Billing #{$recharge->external_reference}",
                    $recharge->external_reference,
                );
                $recharge->update([
                    'status'       => WalletRecharge::STATUS_COMPLETED,
                    'processed_at' => now(),
                ]);
            });

            return response()->json([
                'success'      => true,
                'redirect_url' => route('vendor.wallet.recharge'),
                'message'      => 'Recharge confirmée — ton wallet a été crédité.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Wallet recharge finalize exception', ['ref' => $ref, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    /** Format MSISDN E-Billing (24117XXXXXXX) */
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
