<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Source de vérité du paiement : l'API BillingEasy (e-bills).
 *
 * Extrait de PaymentController pour être réutilisable à la fois par le flux
 * web/mobile (init, check-status, finalize) ET par la réconciliation des
 * commandes restées en `processing` (orders:reconcile-payments).
 *
 * Un e-bill est considéré PAYÉ si `state === "paid"` OU si `amount_paid` a
 * atteint le montant attendu (certains PSP passent l'e-bill à "processed"
 * sans jamais écrire "paid", cf. doc Sowax).
 */
class EbillingService
{
    /**
     * Vérifie le bill suivi en priorité, puis — repli — scanne la liste des
     * e-bills récents à la recherche d'un bill PAYÉ portant la même référence.
     *
     * Le portail crée parfois PLUSIEURS e-bills pour la même référence (un
     * expire, le client en paie un autre) : sans ce scan, un client ayant payé
     * un bill non suivi resterait bloqué.
     *
     * @return array{paid:bool, state:?string, amount_paid:float, bill_id:?string}
     */
    public function findPaidByReference(?string $billId, int $expectedAmount = 0, ?string $externalRef = null): array
    {
        // 1. Chemin rapide : le bill suivi en base.
        $res = $this->getPaid($billId, $expectedAmount);
        if ($res['paid']) {
            return $res + ['bill_id' => $billId];
        }

        // 2. Multi-bills : on scanne la liste BillingEasy récente.
        if ($externalRef) {
            try {
                $base = rtrim((string) config('services.ebilling.url'), '/');
                $r = Http::withHeaders(['Authorization' => (string) config('services.ebilling.auth')])
                    ->acceptJson()->timeout(15)->get($base, ['per_page' => 50]);
                if ($r->successful()) {
                    foreach (($r->json()['entries'] ?? []) as $e) {
                        if (($e['external_reference'] ?? null) !== $externalRef) {
                            continue;
                        }
                        $state = strtolower((string) ($e['state'] ?? ''));
                        $ap    = (float) ($e['amount_paid'] ?? 0);
                        if ($state === 'paid' || ($expectedAmount > 0 && $ap >= $expectedAmount)) {
                            return [
                                'paid' => true, 'state' => 'paid', 'amount_paid' => $ap,
                                'bill_id' => $e['bill_id'] ?? $billId,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('EbillingService: scan liste BillingEasy échoué', ['error' => $e->getMessage()]);
            }
        }

        return $res + ['bill_id' => $billId];
    }

    /**
     * Vérifie un seul e-bill BillingEasy par son id (source de vérité).
     *
     * @return array{paid:bool, state:?string, amount_paid:float}
     */
    public function getPaid(?string $billId, int $expectedAmount = 0): array
    {
        if (empty($billId)) {
            return ['paid' => false, 'state' => null, 'amount_paid' => 0.0];
        }
        try {
            $url = rtrim((string) config('services.ebilling.url'), '/') . '/' . $billId;
            $r = Http::withHeaders(['Authorization' => (string) config('services.ebilling.auth')])
                ->acceptJson()->timeout(12)->get($url);
            if (!$r->successful()) {
                return ['paid' => false, 'state' => null, 'amount_paid' => 0.0];
            }
            $j = $r->json();
            $d = is_array($j) ? ($j['e_bill'] ?? $j['data'] ?? $j) : [];
            $state      = strtolower((string) ($d['state'] ?? ''));
            $amountPaid = (float) ($d['amount_paid'] ?? 0);
            $paid = $state === 'paid' || ($expectedAmount > 0 && $amountPaid >= $expectedAmount);
            return ['paid' => $paid, 'state' => $state ?: null, 'amount_paid' => $amountPaid];
        } catch (\Throwable $e) {
            Log::warning('EbillingService: exception BillingEasy', ['error' => $e->getMessage()]);
            return ['paid' => false, 'state' => null, 'amount_paid' => 0.0];
        }
    }
}
