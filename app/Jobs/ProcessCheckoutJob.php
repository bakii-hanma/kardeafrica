<?php

namespace App\Jobs;

use App\Models\MerchantCardPurchase;
use App\Models\Order;
use App\Models\UserCard;
use App\Support\MerchantCardCode;
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

        // Idempotence — garde-fou CRITIQUE. Sans worker/cron, ces jobs sont
        // restés en file des mois ; en réactivant la file ils seraient rejoués.
        // Une commande déjà complétée NE DOIT PAS être re-livrée : cela
        // rappellerait afrikard/Bamboo = double débit + doublons de cartes.
        $this->order->refresh();
        if ($this->order->status === Order::STATUS_COMPLETED) {
            Log::info('ProcessCheckoutJob: commande déjà complétée, aucune action (idempotence)', [
                'order_id' => $this->order->id,
            ]);
            return;
        }

        // Garde PAIEMENT — CRITIQUE. Ne JAMAIS livrer une commande dont le
        // paiement n'est pas confirmé : d'anciens jobs en file correspondent à
        // des paiements abandonnés (payment_status != completed). Les livrer
        // donnerait des cartes gratuites (débit Bamboo réel sans encaissement).
        if ($this->order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            Log::warning('ProcessCheckoutJob: paiement non confirmé, livraison refusée', [
                'order_id'       => $this->order->id,
                'payment_status' => $this->order->payment_status,
            ]);
            return;
        }

        // Charger les order items + user pour l'identité buyer (Carte Gabon)
        $this->order->load(['orderItems', 'user']);

        if ($this->order->orderItems->isEmpty()) {
            Log::error('ProcessCheckoutJob: Aucun item dans la commande', ['order_id' => $this->order->id]);
            $this->order->update(['notes' => ($this->order->notes ?? '') . ' | Erreur: Aucun item dans la commande']);
            return;
        }

        // ============================================================
        // Split items : marchand (Carte Gabon, local) vs afrikard (API)
        // Les items marchand ont un product_id préfixé "merchant_<card_id>_<amount>".
        // ============================================================
        $merchantItems = $this->order->orderItems->filter(
            fn ($i) => str_starts_with((string) $i->product_id, 'merchant_')
        );
        $afrikardItems = $this->order->orderItems->reject(
            fn ($i) => str_starts_with((string) $i->product_id, 'merchant_')
        );

        // ============================================================
        // 1) Items marchand : génération LOCALE (pas d'appel afrikard)
        // ============================================================
        foreach ($merchantItems as $item) {
            try {
                MerchantCardCode::createPurchaseForOrderItem($this->order, $item);
            } catch (\Throwable $e) {
                Log::error('ProcessCheckoutJob: échec création MerchantCardPurchase', [
                    'order_id'      => $this->order->id,
                    'order_item_id' => $item->id,
                    'product_id'    => $item->product_id,
                    'error'         => $e->getMessage(),
                ]);
                // On ne throw PAS : un échec marchand ne doit pas retry toute la commande
                // (les afrikard pourraient déjà avoir réussi). On log et on continue.
            }
        }

        // ============================================================
        // 2) Items afrikard : appel API (= comportement historique)
        // ============================================================
        if ($afrikardItems->isEmpty()) {
            // Commande 100 % Carte Gabon → on complète sans appel afrikard
            $this->order->update([
                'status'       => Order::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            Log::info('ProcessCheckoutJob: Commande 100 % marchand complétée localement', [
                'order_id'        => $this->order->id,
                'merchant_count'  => $merchantItems->count(),
                'purchases_count' => MerchantCardPurchase::where('order_id', $this->order->id)->count(),
            ]);
            return;
        }

        // Construire le payload afrikard
        $service = app(\App\Services\ProductApiService::class);
        $checkoutPayload = $afrikardItems->map(function ($item) use ($service) {
            $native = $item->native_value && (float) $item->native_value > 0
                ? (int) round((float) $item->native_value)
                : null;

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

        Log::info('ProcessCheckoutJob: Appel API checkout afrikard', [
            'order_id' => $this->order->id,
            'payload'  => $checkoutPayload,
            'attempt'  => $this->attempts(),
        ]);

        $response = Http::timeout(30)
            ->post(config('services.product_api.base_url') . '/orders/checkout', $checkoutPayload);

        // SÉCURITÉ (C7) : ne JAMAIS logger le corps de la réponse fournisseur —
        // il contient les codes et PIN des cartes en clair. On ne trace que le
        // statut HTTP. Le succès détaillé est loggé plus bas sans données sensibles.
        Log::info('ProcessCheckoutJob: Reponse API afrikard', [
            'order_id' => $this->order->id,
            'status'   => $response->status(),
        ]);

        if ($response->status() === 202 || $response->successful()) {
            $checkoutData = $response->json();

            $this->saveCards($checkoutData);

            $this->order->update([
                'status'       => Order::STATUS_COMPLETED,
                'completed_at' => now(),
                'billing_details' => array_merge((array) $this->order->billing_details, [
                    'checkout_order_id'   => $checkoutData['orderId'] ?? null,
                    'checkout_request_id' => $checkoutData['requestId'] ?? null,
                    'checkout_status'     => $checkoutData['status'] ?? null,
                ]),
            ]);

            Log::info('ProcessCheckoutJob: Commande complétée avec succès', [
                'order_id'        => $this->order->id,
                'cards_count'     => UserCard::where('order_id', $this->order->id)->count(),
                'purchases_count' => MerchantCardPurchase::where('order_id', $this->order->id)->count(),
            ]);
        } else {
            Log::error('ProcessCheckoutJob: API checkout afrikard a échoué', [
                'order_id' => $this->order->id,
                'status'   => $response->status(),
                'body'     => $response->body(),
                'attempt'  => $this->attempts(),
            ]);

            throw new \Exception(
                'Checkout API échoué avec statut ' . $response->status() .
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
