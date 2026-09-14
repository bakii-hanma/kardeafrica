<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Relance la récupération asynchrone des cartes afrikard/Bamboo pour les
 * commandes payées dont la livraison est restée en attente (202 sans cartes,
 * timeout, worker interrompu). Ne POSTE JAMAIS : uniquement GET /orders/{id}.
 *
 * À schedule toutes les 5 minutes (routes/console.php).
 */
class RecoverPendingDeliveries extends Command
{
    protected $signature = 'orders:recover';

    protected $description = 'Poll Bamboo (GET /orders/{requestId}) pour les commandes dont la génération des cartes est restée en attente.';

    public function handle(): int
    {
        // Commandes payées, non clôturées, avec un premier appel fournisseur
        // déjà déclenché (H4) — et pas (encore) de cartes livrées.
        $pending = Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_CANCELLED])
            ->whereNotNull('delivery_requested_at')
            ->doesntHave('userCards')
            ->orderBy('id')
            ->limit(20)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Aucune livraison en attente à récupérer.');
            return self::SUCCESS;
        }

        $delivered = 0;
        $service   = app(OrderDeliveryService::class);

        foreach ($pending as $order) {
            try {
                $result = $service->recover($order);

                if ($result['type'] === 'delivered') {
                    $delivered++;
                    $this->line("  #{$order->id} ({$order->order_number}) : livrée ({$result['saved']} carte(s)).");
                } else {
                    $this->line("  #{$order->id} ({$order->order_number}) : {$result['type']}.");
                }
            } catch (\Throwable $e) {
                Log::error('orders:recover: exception', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Livraisons récupérées : {$delivered}/" . $pending->count() . '.');
        return self::SUCCESS;
    }
}