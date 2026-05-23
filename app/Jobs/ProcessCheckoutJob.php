<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\UserCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessCheckoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives
     */
    public $tries = 3;

    /**
     * Backoff exponentiel : 10s, 60s, 300s
     */
    public $backoff = [10, 60, 300];

    /**
     * Timeout de 60 secondes
     */
    public $timeout = 60;

    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): void
    {
        Log::info('ProcessCheckoutJob: Debut du traitement', ['order_id' => $this->order->id]);

        // Charger les order items
        $this->order->load('orderItems');

        if ($this->order->orderItems->isEmpty()) {
            Log::error('ProcessCheckoutJob: Aucun item dans la commande', ['order_id' => $this->order->id]);
            $this->order->update(['notes' => $this->order->notes . ' | Erreur: Aucun item dans la commande']);
            return;
        }

        // Construire le payload pour le checkout API.
        // PRIORITÉ : native_value (ex: 10 EUR), résolu à la création de la commande.
        // Fallback : unit_price (XAF) — utilisé seulement si native_value n'est pas
        // dispo (anciennes commandes). Dans ce cas afrikard rejettera probablement
        // car la valeur ne correspond pas — l'utilisateur devra retry manuellement.
        $service = app(\App\Services\ProductApiService::class);
        $checkoutPayload = $this->order->orderItems->map(function ($item) use ($service) {
            $native = $item->native_value && (float) $item->native_value > 0
                ? (int) round((float) $item->native_value)
                : null;

            // Fallback runtime si la commande n'a pas été créée avec native_value
            if ($native === null) {
                $resolved = $service->resolveNativeValue($item->product_id);
                $native = $resolved['value'] ?? null;
            }

            return [
                'ProductId' => (int) $item->product_id,
                'Quantity'  => (int) $item->quantity,
                'Value'     => $native ?? (int) round($item->unit_price),
            ];
        })->values()->toArray();

        Log::info('ProcessCheckoutJob: Appel API checkout', [
            'order_id' => $this->order->id,
            'payload' => $checkoutPayload,
            'attempt' => $this->attempts(),
        ]);

        // Appeler l'API checkout
        $response = Http::timeout(30)
            ->post(config('services.product_api.base_url') . '/orders/checkout', $checkoutPayload);

        Log::info('ProcessCheckoutJob: Reponse API', [
            'order_id' => $this->order->id,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->status() === 202 || $response->successful()) {
            $checkoutData = $response->json();

            // Sauvegarder les cartes recues
            $this->saveCards($checkoutData);

            // Marquer la commande comme completee
            $this->order->update([
                'status' => Order::STATUS_COMPLETED,
                'completed_at' => now(),
                'billing_details' => [
                    'checkout_order_id' => $checkoutData['orderId'] ?? null,
                    'checkout_request_id' => $checkoutData['requestId'] ?? null,
                    'checkout_status' => $checkoutData['status'] ?? null,
                ],
            ]);

            Log::info('ProcessCheckoutJob: Commande completee avec succes', [
                'order_id' => $this->order->id,
                'cards_count' => UserCard::where('order_id', $this->order->id)->count(),
            ]);
        } else {
            // Echec - sera retry automatiquement
            Log::error('ProcessCheckoutJob: API checkout a echoue', [
                'order_id' => $this->order->id,
                'status' => $response->status(),
                'body' => $response->body(),
                'attempt' => $this->attempts(),
            ]);

            throw new \Exception(
                'Checkout API echoue avec statut ' . $response->status() .
                ' pour la commande #' . $this->order->order_number
            );
        }
    }

    /**
     * Sauvegarder les cartes du checkout dans user_cards
     */
    private function saveCards(array $checkoutData): void
    {
        $items = $checkoutData['items'] ?? [];

        foreach ($items as $item) {
            $cards = $item['cards'] ?? [];

            // Trouver l'order_item correspondant
            $orderItem = $this->order->orderItems
                ->where('product_id', $item['productId'] ?? null)
                ->first();

            foreach ($cards as $card) {
                UserCard::create([
                    'user_id' => $this->order->user_id,
                    'order_id' => $this->order->id,
                    'order_item_id' => $orderItem?->id,
                    'product_id' => $item['productId'] ?? null,
                    'checkout_card_id' => $card['id'] ?? null,
                    'name' => $orderItem?->name ?? 'Carte cadeau',
                    'brand' => $orderItem?->name ?? 'Unknown',
                    'serial_number' => $card['serialNumber'] ?? null,
                    'card_code' => $card['cardCode'] ?? null,
                    'pin' => $card['pin'] ?? null,
                    'expiration_date' => isset($card['expirationDate'])
                        ? \Carbon\Carbon::parse($card['expirationDate'])->toDateTimeString()
                        : null,
                    'status' => $this->mapCardStatus($card['status'] ?? null),
                    'face_value' => $item['productFaceValue'] ?? $orderItem?->unit_price ?? 0,
                    'currency' => $checkoutData['currency'] ?? 'XAF',
                    'image_url' => $orderItem?->image_url ?? null,
                    'metadata' => [
                        'checkout_order_id' => $checkoutData['orderId'] ?? null,
                        'card_status' => $card['status'] ?? null,
                    ],
                ]);
            }
        }
    }

    /**
     * Mapping du statut renvoye par l'API externe ("Sold", "Active", "Used"...).
     */
    private function mapCardStatus(?string $apiStatus): string
    {
        $normalized = strtolower($apiStatus ?? '');

        return match ($normalized) {
            'used', 'redeemed', 'consumed' => UserCard::STATUS_USED,
            'expired'                       => UserCard::STATUS_EXPIRED,
            default                         => UserCard::STATUS_ACTIVE,
        };
    }

    /**
     * Traitement en cas d'echec definitif (apres toutes les tentatives)
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('ProcessCheckoutJob: ECHEC DEFINITIF', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mettre a jour les notes de la commande
        $this->order->update([
            'notes' => ($this->order->notes ?? '') . ' | ECHEC CHECKOUT: ' . $exception->getMessage() . ' (' . now()->toDateTimeString() . ')',
        ]);
    }
}
