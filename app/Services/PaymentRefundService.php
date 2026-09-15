<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\PhoneOperator;

/**
 * Wrapper minimal autour de l'endpoint /transfer.php d'E-Billing (futursowax)
 * pour rembourser une transaction au payeur d'origine.
 *
 * Doc : https://futursowax.com/paiement/portal-docs.php  (§ Transfert / Payout)
 *
 * Le paiement original est identifié par sa `external_reference`. L'endpoint
 * `transfer.php` renvoie le montant indiqué vers le numéro Mobile Money fourni
 * (`phone_number`, privilégié : le montant part vers LE numéro choisi).
 */
class PaymentRefundService
{
    /** Mappage PhoneOperator → valeur `payment_system` attendue par transfer.php. */
    private const PAYMENT_SYSTEM_MAP = [
        PhoneOperator::AIRTEL => 'airtelmoney',
        PhoneOperator::MOOV   => 'moovmoney1',
    ];

    /**
     * Traduit un MSISDN canonique gabonais (241XXXXXXXX) vers le format local
     * à 9 chiffres attendu par transfer.php (0XXXXXXXX). Laisse passer les
     * numéros étrangers tels quels (le PSP les refusera proprement).
     */
    public static function toLocalGabonPhone(?string $msisdn): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $msisdn);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '241') && strlen($digits) === 11) {
            return '0' . substr($digits, 3);
        }

        return $digits;
    }

    /**
     * Lance un transfert de remboursement via E-Billing.
     *
     * @param string $originalReference  external_reference du paiement initial
     * @param int    $amountFcfa         montant à rembourser en FCFA (entier)
     * @param string $reason             raison libre, transmise dans description
     * @param array  $extras             champs additionnels : msisdn (obligatoire),
     *                                   email/name pour traçabilité
     * @return array  ['ok' => bool, 'transfer_reference' => ?string, 'message' => string, 'raw' => mixed]
     */
    public function refund(string $originalReference, int $amountFcfa, string $reason = '', array $extras = []): array
    {
        // Référence DÉTERMINISTE dérivée du paiement d'origine : deux appels de
        // remboursement pour la même commande produisent la MÊME référence, ce
        // qui permet à E-Billing de dédupliquer côté PSP (anti double-virement).
        // Tronquée à 64 caractères (format max des références E-Billing).
        $transferRef = substr('REFUND_' . $originalReference, 0, 64);

        // `payment_system` auto-détecté depuis le MSISDN quand possible, sinon
        // le PSP détecte lui-même (UNKNOWN_PAYMENT_SYSTEM si ambigüe).
        $msisdn = $extras['msisdn'] ?? null;
        $paymentSystem = $msisdn !== null
            ? (self::PAYMENT_SYSTEM_MAP[PhoneOperator::detect($msisdn)] ?? null)
            : null;

        // transfer.php attend un numéro au format LOCAL gabonais (9 chiffres,
        // ex. 074000000) : on traduit la forme canonique 241XXXXXXXX.
        $phoneNumber = ($msisdn !== null)
            ? self::toLocalGabonPhone($msisdn)
            : null;

        $payload = array_filter([
            'amount'             => $amountFcfa,
            'phone_number'       => $phoneNumber,
            'payment_system'     => $paymentSystem,
            'description'        => substr('Remboursement : ' . $reason, 0, 120),
            'external_reference' => $transferRef,
            'user_id'            => $extras['email'] ?? null,
            'metadata'           => $extras['metadata'] ?? ['payout_type' => 'refund'],
            'format'             => 'json',
        ], fn($v) => $v !== null && $v !== '');

        Log::info('PaymentRefund: payload', [
            'transfer_reference' => $transferRef,
            'phone_number'       => $phoneNumber,
            'payment_system'     => $paymentSystem,
            'amount'             => $amountFcfa,
        ]);

        try {
            $response = Http::timeout(20)->acceptJson()->asForm()
                ->post(config('services.payment_backend.transfer_url'), $payload);

            Log::info('PaymentRefund: transfer response', [
                'transfer_reference' => $transferRef,
                'original'           => $originalReference,
                'status'             => $response->status(),
                'body'               => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                $body = $response->json();
                return [
                    'ok'                 => false,
                    'transfer_reference' => $transferRef,
                    'message'            => $body['error'] ?? "HTTP {$response->status()} de l'API E-Billing",
                    'raw'                => $body,
                ];
            }

            $body = $response->json();
            $state = data_get($body, 'data.state') ?? $body['status'] ?? null;
            $ok   = ($body['success'] ?? false)
                || in_array($state, ['ok', 'success', 'completed', 'pending', true], true);

            return [
                'ok'                 => $ok,
                'transfer_reference' => data_get($body, 'data.external_reference') ?: $transferRef,
                'message'            => $body['message'] ?? data_get($body, 'data.message') ?? ($ok ? 'Transfert initié' : 'Transfert refusé'),
                'raw'                => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('PaymentRefund: exception', [
                'transfer_reference' => $transferRef,
                'error'              => $e->getMessage(),
            ]);
            return [
                'ok'                 => false,
                'transfer_reference' => $transferRef,
                'message'            => 'Erreur réseau : ' . $e->getMessage(),
                'raw'                => null,
            ];
        }
    }
}
