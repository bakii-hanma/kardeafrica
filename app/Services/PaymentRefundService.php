<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper minimal autour de l'endpoint /transfer.php d'E-Billing (futursowax)
 * pour rembourser une transaction au payeur d'origine.
 *
 * Doc : https://futursowax.com/paiement/portal-docs.php
 *
 * Le paiement original est identifié par sa `external_reference`. L'endpoint
 * `transfer.php` renvoie le montant indiqué vers le compte/MSISDN qui avait
 * effectué le paiement initial.
 */
class PaymentRefundService
{
    /**
     * Lance un transfert de remboursement via E-Billing.
     *
     * @param string $originalReference  external_reference du paiement initial
     * @param int    $amountFcfa         montant à rembourser en FCFA (entier)
     * @param string $reason             raison libre, transmise dans short_description
     * @param array  $extras             champs additionnels (msisdn, email pour fallback)
     * @return array  ['ok' => bool, 'transfer_reference' => ?string, 'message' => string, 'raw' => mixed]
     */
    public function refund(string $originalReference, int $amountFcfa, string $reason = '', array $extras = []): array
    {
        $transferRef = 'REFUND_' . time() . '_' . rand(1000, 9999);

        $payload = array_filter([
            'amount'            => $amountFcfa,
            'reference'         => $transferRef,
            'original_reference'=> $originalReference,
            'short_description' => substr('Remboursement : ' . $reason, 0, 120),
            'msisdn'            => $extras['msisdn'] ?? null,
            'email'             => $extras['email']  ?? null,
            'name'              => $extras['name']   ?? null,
            'format'            => 'json',
        ], fn($v) => $v !== null && $v !== '');

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
            $ok   = ($body['success'] ?? false) || in_array($body['status'] ?? null, ['ok', 'success', 'completed', 'pending'], true);

            return [
                'ok'                 => $ok,
                'transfer_reference' => $transferRef,
                'message'            => $body['message'] ?? ($ok ? 'Transfert initié' : 'Transfert refusé'),
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
