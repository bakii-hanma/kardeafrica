<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\EbillingService;
use App\Services\OrderFulfillmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Réconciliation des commandes restées en `payment_status=processing`.
 *
 * Un client peut payer sans jamais revenir sur la page webview /verify
 * (fermeture de l'app, coupure réseau, redirection échouée) : `finalize()`
 * n'est alors JAMAIS appelé et la commande reste bloquée en `processing`,
 * sans livraison, indéfiniment. `orders:recover` ne couvre PAS ce cas (il
 * n'agit que sur les commandes déjà `completed`).
 *
 * Cette commande interroge la source de vérité (BillingEasy) pour les
 * commandes récentes en `processing` : celles réellement PAYÉES sont
 * finalisées puis livrées via le MÊME chemin que le flux normal.
 *
 * Lecture seule côté fournisseur (GET) : aucun risque de double débit.
 */
class ReconcilePendingPayments extends Command
{
    protected $signature = 'orders:reconcile-payments
        {--hours=24 : ne considerer que les commandes creees dans les N dernieres heures}
        {--limit=100 : nombre max de commandes traitees par passage}
        {--dry-run : lister les commandes payees sans les finaliser}';

    protected $description = 'Finalise les commandes payées restées bloquées en "processing" (poll BillingEasy).';

    public function handle(): int
    {
        $hours   = max(1, (int) $this->option('hours'));
        $limit   = max(1, (int) $this->option('limit'));
        $dryRun  = (bool) $this->option('dry-run');
        $since   = now()->subHours($hours);

        $orders = Order::where('payment_status', Order::PAYMENT_STATUS_PROCESSING)
            ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_CANCELLED])
            ->whereNotNull('external_reference')
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Aucune commande en attente de paiement à réconcilier.');
            return self::SUCCESS;
        }

        $ebilling   = app(EbillingService::class);
        $fulfillment = app(OrderFulfillmentService::class);
        $delivered  = 0;
        $checked    = 0;

        foreach ($orders as $order) {
            $ref     = (string) $order->external_reference;
            $total   = (int) round((float) $order->total_amount);
            $billId  = Payment::where('transaction_id', $ref)->value('external_transaction_id');

            try {
                $res = $ebilling->findPaidByReference($billId, $total, $ref);
            } catch (\Throwable $e) {
                Log::error('orders:reconcile-payments: exception BillingEasy', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
                continue;
            }

            $checked++;

            // Paiement NON confirmé (ou montant insuffisant) : on ne fait rien.
            if (! $res['paid'] || (float) $res['amount_paid'] < $total) {
                continue;
            }

            // C3 — réconciliation : le montant facturé (Payment.amount) doit
            // couvrir le total de la commande. Sinon on refuse la livraison.
            $invoiced = Payment::where('transaction_id', $ref)->value('amount');
            if ($invoiced !== null && $total > (int) round((float) $invoiced)) {
                Log::warning('orders:reconcile-payments: total > montant facturé — livraison refusée', [
                    'order_id' => $order->id, 'ref' => $ref, 'total' => $total, 'invoiced' => $invoiced,
                ]);
                continue;
            }

            $this->line("  #{$order->id} ({$order->order_number}) : PAYÉ (" . $res['amount_paid'] . " FCFA) — finalisation…");

            if ($dryRun) {
                continue;
            }

            // Multi-bills : corriger le bill suivi pour la traçabilité.
            if (! empty($res['bill_id']) && $res['bill_id'] !== $billId) {
                Payment::where('transaction_id', $ref)->update(['external_transaction_id' => $res['bill_id']]);
            }

            // Idempotence : si entre-temps le flux normal a finalisé, on saute.
            if (! $fulfillment->markPaid($order, $ref)) {
                $this->line('    (déjà finalisée par ailleurs)');
                continue;
            }

            $fulfillment->deliver($order->fresh());
            $delivered++;

            Log::info('orders:reconcile-payments: commande payée finalisée', [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'ref'          => $ref,
                'amount_paid'  => $res['amount_paid'],
                'bill_id'      => $res['bill_id'],
            ]);
        }

        $this->info("Réconciliation : {$delivered} commande(s) finalisée(s) sur {$checked} vérifiée(s) ({$orders->count()} en attente).");
        return self::SUCCESS;
    }
}
