<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Bamboo `ordercompleted.v1` — livraison automatique.
 *
 * Bamboo annonce qu'une commande est terminée (codes générés) : on retrouve la
 * commande locale par son requestId (ou orderId) enregistré dans billing_details
 * lors du checkout, puis on déclenche la RÉCUPÉRATION (GET /orders/{requestId})
 * pour sauver les cartes — jamais de re-POST (anti double-débit H4).
 *
 * Idempotent : `OrderDeliveryService::recover()` ne crée les cartes que si
 * elles sont absentes (firstOrCreate sur checkout_card_id) et clôt la commande
 * uniquement quand TOUTES les cartes attendues sont présentes.
 */
class HandleBambooOrderCompleted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 60, 300];

    public $timeout = 90;

    public function __construct(private array $payload)
    {
    }

    public function handle(OrderDeliveryService $delivery): void
    {
        $requestId = $this->payload['requestId'] ?? null;
        $orderId   = $this->payload['orderId']   ?? null;

        if ($requestId === null && $orderId === null) {
            Log::warning('Bamboo ordercompleted: payload sans requestId/orderId.', ['payload' => $this->payload]);
            return;
        }

        $order = $this->findOrder($requestId, $orderId);

        if (! $order) {
            Log::warning('Bamboo ordercompleted: aucune commande locale correspondante.', [
                'requestId' => $requestId,
                'orderId'   => $orderId,
            ]);
            return;
        }

        if ($order->userCards()->exists()) {
            Log::info('Bamboo ordercompleted: cartes déjà livrées, rien à faire.', [
                'order_id'  => $order->id,
                'requestId' => $requestId,
            ]);
            return;
        }

        Log::info('Bamboo ordercompleted: déclenche récupération', [
            'order_id'  => $order->id,
            'requestId' => $requestId,
        ]);

        $result = $delivery->recover($order);

        Log::info('Bamboo ordercompleted: récupération terminée.', [
            'order_id' => $order->id,
            'type'     => $result['type'],
            'saved'    => $result['saved'] ?? 0,
        ]);

        if ($result['type'] === 'delivered') {
            return;
        }

        // Livraison pas encore possible (codes pas prêts / fournisseur
        // indisponible) : en cas de panne transitoire, le polling orders:recover
        // prendra le relais. On ne throw PAS pour éviter un retry inutile.
    }

    /** Retrouve la commande locale par billing_details->checkout_request_id / orderId. */
    private function findOrder(?string $requestId, $orderId): ?Order
    {
        $query = Order::whereNot('status', Order::STATUS_COMPLETED)
            ->whereNotNull('delivery_requested_at');

        if ($requestId !== null) {
            $order = $query->clone()
                ->where('billing_details->checkout_request_id', $requestId)
                ->latest('id')
                ->first();
            if ($order) {
                return $order;
            }
        }

        if ($orderId !== null) {
            return $query->clone()
                ->where('billing_details->checkout_order_id', (string) $orderId)
                ->latest('id')
                ->first();
        }

        return null;
    }
}