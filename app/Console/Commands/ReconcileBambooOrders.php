<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\BambooReportingService;
use App\Services\OrderDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Réconciliation Order-Level avec Bamboo (feature 7).
 *
 * Interroge l'historique des commandes Bamboo (`/orders/report`) sur une période
 * et rapproche chaque entrée de la commande locale correspondante, via
 * `billing_details->checkout_request_id` (clientReferenceNumber). Les commandes
 * locales payées, non livrées, dont la référence fournisseur existe chez Bamboo
 * sont récupérées (GET /orders/{requestId}) — jamais de re-POST (anti H4).
 *
 * À schedule quotidien (routes/console.php). Le seuil d'alerte sur les soldes
 * (feature 3) s'appuie sur ce même canal.
 */
class ReconcileBambooOrders extends Command
{
    protected $signature = 'orders:reconcile
        {--days=7 : Nombre de jours d\'historique à interroger}
        {--run : Exécute réellement la récupération des orphelines (sinon : état seulement)}';

    protected $description = 'Rapproche l\'historique des commandes Bamboo des commandes locales et récupère les orphelines.';

    public function handle(BambooReportingService $bamboo): int
    {
        $tz      = 'Africa/Libreville';
        $days    = (int) $this->option('days');
        $endDate = Carbon::now($tz);
        $start   = $endDate->copy()->subDays(max(1, min($days, 90)));
        $run     = (bool) $this->option('run');

        $startDate = $start->format('Y-m-d');
        $endDateY  = $endDate->format('Y-m-d');

        $this->info("Réconciliation Bamboo : {$startDate} → {$endDateY}");

        $report = $bamboo->orderReport($startDate, $endDateY, fresh: true);
        if (! $report['ok']) {
            $this->error('Historique Bamboo indisponible : ' . ($report['error'] ?? '?'));
            return self::FAILURE;
        }

        $this->line(count($report['orders']) . ' commande(s) dans l\'historique Bamboo.');

        // Index des références présentes chez Bamboo.
        $bambooRefs = collect($report['orders'])
            ->pluck('clientReferenceNumber')
            ->filter()
            ->flip();

        // Commandes locales payées, non clôturées, avec référence fournisseur.
        $locales = Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_CANCELLED])
            ->whereNotNull('delivery_requested_at')
            ->doesntHave('userCards')
            ->orderBy('id')
            ->get();

        $orphans = 0;
        foreach ($locales as $order) {
            $bd  = (array) $order->billing_details;
            $req = $bd['checkout_request_id'] ?? null;
            if ($req === null) {
                continue; // livraison demandée sans référence — déjà hors flow
            }
            if ($bambooRefs->has($req)) {
                $orphans++;
                $this->line("  #{$order->id} ({$order->order_number}) : présente chez Bamboo — récupération requise.");
            }
        }

        $accountWarning = $this->lowBalanceWarning($bamboo);
        if ($accountWarning !== null) {
            $this->warn($accountWarning);
        }

        if ($orphans === 0) {
            $this->info('Aucune commande locale en attente présente chez Bamboo.');
        }

        if (! $run || $orphans === 0) {
            return self::SUCCESS;
        }

        $delivery  = app(OrderDeliveryService::class);
        $recovered = 0;

        foreach ($locales as $order) {
            $bd  = (array) $order->billing_details;
            $req = $bd['checkout_request_id'] ?? null;
            if ($req === null || ! $bambooRefs->has($req)) {
                continue;
            }

            try {
                $result = $delivery->recover($order);
                if ($result['type'] === 'delivered') {
                    $recovered++;
                    $this->line("  → #{$order->id} : livrée ({$result['saved']} carte(s)).");
                } else {
                    $this->line("  → #{$order->id} : {$result['type']}.");
                }
            } catch (\Throwable $e) {
                Log::error('orders:reconcile: exception', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Récupérées : {$recovered}/{$orphans}.");
        return self::SUCCESS;
    }

    /**
     * Feature 3 (canal CLI) : remonte un avertissement si le solde d'un compte
     * Bamboo actif passe sous le seuil configuré (EUR par défaut).
     */
    private function lowBalanceWarning(BambooReportingService $bamboo): ?string
    {
        $accounts = $bamboo->accounts(fresh: true);
        if (! $accounts['ok']) {
            return null;
        }

        $threshold = (float) config('services.bamboo.accounts_alert_threshold_eur');
        foreach ($accounts['accounts'] as $acc) {
            $cur = strtoupper((string) ($acc['currency'] ?? ''));
            $bal = (float) ($acc['balance'] ?? 0);
            if ($cur === 'EUR' && $bal < $threshold) {
                return sprintf(
                    '⚠ Solde compte Bamboo %s bas : %s %s (seuil %s).',
                    $acc['id'] ?? '?',
                    number_format($bal, 2, ',', ' '),
                    $cur,
                    number_format($threshold, 2, ',', ' ')
                );
            }
        }

        return null;
    }
}