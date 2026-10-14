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

        if (str_starts_with($digits, '241')) {
            $rest = substr($digits, 3);
            // 241 + 8 chiffres d'abonné (canonique) → 0XXXXXXXX
            if (strlen($rest) === 8) {
                return '0' . $rest;
            }
            // 241 + 9 chiffres commençant par 0 (le 0 de tête a été conservé à
            // la saisie, ex. +241 077687737) → le local est déjà bon.
            if (strlen($rest) === 9 && str_starts_with($rest, '0')) {
                return $rest;
            }
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

        // Majoration des frais : le PSP/opérateur prélève un % sur le payout, donc
        // le client recevrait moins que $amountFcfa. On envoie le montant BRUT
        // nécessaire pour qu'il reçoive le NET attendu. $extras['gross_up'] = false
        // désactive (ex. un complément déjà calculé).
        $feeRate     = (float) config('services.payment_backend.refund_fee_rate', 0.0);
        $grossUp     = $extras['gross_up'] ?? true;
        $grossAmount = ($grossUp && $feeRate > 0 && $feeRate < 0.5)
            ? (int) ceil($amountFcfa / (1 - $feeRate))
            : $amountFcfa;

        $payload = array_filter([
            'amount'             => $grossAmount,
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
            'amount_net'         => $amountFcfa,
            'amount_gross'       => $grossAmount,
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

            $body = $response->json();

            // transfer.php encapsule PARFOIS un SUCCÈS réel dans un wrapper
            // d'erreur HTTP 400 : {success:false, error:"Réponse inattendue…",
            // details:{success:"transaction_completed", response:{state:"success",
            // payout_id, transaction_id, …}}}. Si on s'arrête au code HTTP, on
            // croit l'opération échouée ALORS QUE L'ARGENT EST PARTI → risque de
            // double remboursement au retry. On inspecte donc le vrai état.
            $nestedState   = data_get($body, 'details.response.state');
            $nestedSuccess = data_get($body, 'details.success');
            $topState      = data_get($body, 'data.state') ?? ($body['status'] ?? null);

            $ok = ($body['success'] ?? false) === true
                || in_array($nestedState, ['success', 'completed', 'ok', 'pending'], true)
                || $nestedSuccess === 'transaction_completed'
                || in_array($topState, ['ok', 'success', 'completed', 'pending'], true);

            // Vrai échec : ni succès réel, ni HTTP OK.
            if (! $ok && ! $response->successful()) {
                return [
                    'ok'                 => false,
                    'transfer_reference' => $transferRef,
                    'message'            => data_get($body, 'error') ?? data_get($body, 'message') ?? "HTTP {$response->status()} de l'API E-Billing",
                    'raw'                => $body,
                ];
            }

            $ref = data_get($body, 'details.response.external_reference')
                ?: (data_get($body, 'details.response.payout_id') ? 'payout_' . data_get($body, 'details.response.payout_id') : null)
                ?: data_get($body, 'data.external_reference')
                ?: $transferRef;

            return [
                'ok'                 => $ok,
                'transfer_reference' => $ref,
                'message'            => $ok
                    ? ('Transfert effectué' . (data_get($body, 'details.response.transaction_id') ? ' (' . data_get($body, 'details.response.transaction_id') . ')' : ''))
                    : (data_get($body, 'error') ?? data_get($body, 'message') ?? 'Transfert refusé'),
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
