<?php

namespace App\Services;

use App\Jobs\ProcessCheckoutJob;
use App\Models\Order;
use App\Models\UserCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Livraison afrikard fiable ET sans doublons.
 *
 * Centralise le flux utilisé par le checkout initial (ProcessCheckoutJob),
 * le retry admin, le retry utilisateur web et le retry mobile.
 *
 * Les deux garanties portent ici :
 *  1. ANTI DOUBLE-DÉBIT (H4) : `delivery_requested_at` est posé sous verrou
 *     AVANT le premier appel /orders/checkout. Un retry ne re-POSTe JAMAIS :
 *     il poll au contraire GET /orders/{requestId} pour récupérer les codes
 *     que Bamboo génère en asynchrone (cas du « 202 sans cartes »).
 *  2. Pas de « completed » à vide : la commande n'est clôturée que lorsque
 *     les cartes sont réellement dans user_cards, sinon elle reste en
 *     `processing` et sera re-pollée (admin, user ou planificateur).
 */
class OrderDeliveryService
{
    /**
     * Retry conforme H4 : POST /orders/checkout si aucun appel n'a encore été
     * fait, sinon GET /orders/{requestId} pour récupérer les codes non reçus.
     *
     * @return array{type: string, saved?: int, missing?: array, status?: int,
     *               message?: string, recovered?: bool}
     */
    public function attempt(Order $order): array
    {
        $order->load(['orderItems', 'userCards']);

        if ($order->userCards->isNotEmpty()) {
            return ['type' => 'already_delivered', 'saved' => $order->userCards->count()];
        }
        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return ['type' => 'not_paid'];
        }
        if (in_array($order->status, [Order::STATUS_REFUNDED, Order::STATUS_CANCELLED], true)
            || in_array($order->payment_status, [Order::PAYMENT_STATUS_REFUNDED, Order::PAYMENT_STATUS_CANCELLED], true)) {
            return ['type' => 'refunded'];
        }

        [$payload, $missing] = $this->buildPayload($order);

        if (!empty($missing)) {
            // Catalogue incomplet : le job async retentera (avec son propre H4).
            ProcessCheckoutJob::dispatch($order);
            Log::warning('OrderDelivery.attempt: catalogue incomplet, async dispatch', [
                'order_id' => $order->id, 'missing' => $missing,
            ]);
            return ['type' => 'missing', 'missing' => $missing, 'dispatched' => true];
        }
        if (empty($payload)) {
            return ['type' => 'no_afrikard_items'];
        }

        // ---- H4 : marqueur posé sous verrou AVANT l'appel fournisseur ----
        $decision = DB::transaction(function () use ($order) {
            $fresh = Order::whereKey($order->id)->lockForUpdate()->first();

            // Un process concurrent a pu livrer entre-temps.
            if ($fresh->userCards()->exists() || $fresh->status === Order::STATUS_COMPLETED) {
                return 'delivered';
            }
            // Livraison déjà demandée → on ne re-facture JAMAIS : récupération.
            if ($fresh->delivery_requested_at !== null) {
                return 'already_requested';
            }
            $fresh->delivery_requested_at = now();
            $fresh->save();
            return 'proceed';
        });

        if ($decision === 'delivered') {
            return ['type' => 'already_delivered', 'saved' => UserCard::where('order_id', $order->id)->count()];
        }
        if ($decision === 'already_requested') {
            return $this->recover($order);
        }

        Log::info('OrderDelivery.attempt: appel afrikard', [
            'order_id' => $order->id,
            'payload'  => $payload,
        ]);

        try {
            $response = Http::timeout(30)
                ->post(config('services.product_api.base_url') . '/orders/checkout', $payload);
        } catch (\Throwable $e) {
            Log::error('OrderDelivery.attempt: exception réseau (marqueur conservé)', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);
            return ['type' => 'unavailable', 'message' => 'Connexion temporairement indisponible avec le fournisseur.'];
        }

        if ($response->status() === 202 || $response->successful()) {
            $data  = $response->json() ?? [];
            $saved = $this->saveCards($order, $data);

            // Toujours conserver orderId/requestId : seuls ils permettent de
            // re-poll Bamboo si les codes arrivent en asynchrone.
            $order->update([
                'billing_details' => array_merge((array) $order->billing_details, [
                    'checkout_order_id'   => $data['orderId'] ?? null,
                    'checkout_request_id' => $data['requestId'] ?? null,
                    'checkout_status'     => $data['status'] ?? null,
                ]),
            ]);

            if ($this->allCardsReceived($order)) {
                $order->update([
                    'status'       => Order::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
                return ['type' => 'delivered', 'saved' => $saved];
            }

            // Codes pas encore prêts (202 sans cartes) : la commande reste en
            // processing, la récupération se fera au prochain retry/polling.
            $order->update([
                'status' => Order::STATUS_PROCESSING,
                'notes'  => trim(($order->notes ?? '')
                    . ' | Cartes en cours de génération côté fournisseur (async) — récupération auto programmée (' . now()->toDateTimeString() . ')'),
            ]);
            return ['type' => 'pending', 'saved' => $saved, 'message' => 'Livraison demandée, codes en cours de génération.'];
        }

        if ($response->status() >= 400 && $response->status() < 500) {
            // 4xx = requête REJETÉE : rien n'a été débité → on libère le
            // marqueur pour autoriser un futur retry (comportement ProcessCheckoutJob).
            $order->update(['delivery_requested_at' => null]);
            Log::warning('OrderDelivery.attempt: 4xx, marqueur relâché', [
                'order_id' => $order->id, 'status' => $response->status(),
            ]);
            return ['type' => 'rejected', 'status' => $response->status()];
        }

        // 5xx/réseau = AMBIGU : la livraison a pu partir. On GARDE le marqueur :
        // les retries suivants polleront Bamboo au lieu de re-POSTer.
        Log::warning('OrderDelivery.attempt: 5xx, marqueur conservé', [
            'order_id' => $order->id, 'status' => $response->status(),
        ]);
        return ['type' => 'failed', 'status' => $response->status()];
    }

    /**
     * Poll GET /orders/{requestId} pour récupérer les cartes générées en
     * asynchrone par Bamboo après un premier checkout accepté.
     */
    public function recover(Order $order): array
    {
        $order->load(['orderItems', 'userCards']);

        $bd = (array) $order->billing_details;
        $id = $bd['checkout_request_id'] ?? $bd['checkout_order_id'] ?? null;

        if ($id === null) {
            // Ancien retry (avant ce correctif) : la carte a pu être débitée
            // chez Bamboo sans requestId enregistré. Interdit de re-POSTer ;
            // la récupération se fait alors via la réconciliation Bamboo manuelle.
            return [
                'type'    => 'orphan_debited',
                'saved'   => 0,
                'message' => 'Livraison déjà demandée sans référence fournisseur enregistrée : la carte a pu être débitée chez Bamboo. Récupération via la réconciliation Bamboo requise (jamais de re-POST automatique).',
            ];
        }

        try {
            $resp = Http::timeout(20)
                ->get(config('services.product_api.base_url') . '/orders/' . rawurlencode((string) $id));
        } catch (\Throwable $e) {
            Log::error('OrderDelivery.recover: exception réseau', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);
            return ['type' => 'unavailable', 'message' => 'Fournisseur injoignable lors de la récupération.'];
        }

        if ($resp->status() === 200) {
            $data  = $resp->json() ?? [];
            $saved = $this->saveCards($order, $data);

            if ($this->allCardsReceived($order)) {
                $order->update([
                    'status'       => Order::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
                Log::info('OrderDelivery.recover: commande complétée par récupération', [
                    'order_id' => $order->id, 'saved' => $saved,
                ]);
                return ['type' => 'delivered', 'saved' => $saved, 'recovered' => true];
            }
            return ['type' => 'pending', 'saved' => $saved, 'message' => 'Codes pas encore tous générés côté fournisseur — réessayez dans quelques minutes.'];
        }

        if ($resp->status() === 404) {
            return ['type' => 'pending', 'saved' => 0, 'message' => 'Commande fournisseur pas encore prête (codes en cours de génération) — réessayez dans quelques minutes.'];
        }

        return ['type' => 'unavailable', 'message' => 'Récupération fournisseur indisponible (HTTP ' . $resp->status() . ').'];
    }

    /**
     * Sauve les cartes reçues dans user_cards (idempotent sur checkout_card_id).
     *
     * @return int nombre de cartes traitées dans cette réponse
     */
    public function saveCards(Order $order, array $checkoutData): int
    {
        $saved = 0;
        $requestId = $checkoutData['requestId'] ?? null;

        foreach ($checkoutData['items'] ?? [] as $item) {
            $productId = $item['productId'] ?? null;
            $cards     = $item['cards'] ?? [];
            $faceValue = $item['productFaceValue'] ?? null;

            $orderItem = $order->orderItems->firstWhere('product_id', (string) $productId)
                ?? $order->orderItems->firstWhere('product_id', $productId)
                ?? $order->orderItems->first(fn ($oi) => (int) $oi->product_id === (int) $productId
                    && ($faceValue === null || $oi->native_value === null
                        || (float) $oi->native_value == (float) $faceValue));

            foreach ($cards as $card) {
                $ccid  = $card['id'] ?? null;
                $attrs = [
                    'user_id'          => $order->user_id,
                    'order_id'         => $order->id,
                    'order_item_id'    => $orderItem?->id,
                    'product_id'       => (string) $productId,
                    'name'             => $orderItem?->name ?? 'Carte cadeau',
                    'brand'            => $orderItem?->name ? explode(' ', $orderItem->name)[0] : null,
                    'serial_number'    => $card['serialNumber'] ?? null,
                    'card_code'        => $card['cardCode'] ?? '',
                    'pin'              => $card['pin'] ?? null,
                    'expiration_date'  => !empty($card['expirationDate']) ? $card['expirationDate'] : null,
                    'status'           => match (strtolower($card['status'] ?? '')) {
                        'used', 'redeemed', 'consumed' => UserCard::STATUS_USED,
                        'expired'                       => UserCard::STATUS_EXPIRED,
                        default                         => UserCard::STATUS_ACTIVE,
                    },
                    'face_value'       => $faceValue ?? $orderItem?->unit_price ?? 0,
                    'currency'         => $checkoutData['currency'] ?? 'XAF',
                    'image_url'        => $orderItem?->image_url,
                    'metadata'         => [
                        'retry'                  => true,
                        'checkout_order_id'      => $checkoutData['orderId'] ?? null,
                        'checkout_request_id'    => $requestId,
                        'checkout_status'        => $checkoutData['status'] ?? null,
                    ],
                ];

                $ccid !== null
                    ? UserCard::firstOrCreate(['checkout_card_id' => $ccid], $attrs)
                    : UserCard::create($attrs + ['checkout_card_id' => null]);
                $saved++;
            }
        }

        return $saved;
    }

    /**
     * Vrai lorsque la quantité d'items afrikard attendue est couverte par les
     * cartes enregistrées (items marchand/daywatch exclus, traités ailleurs).
     */
    public function allCardsReceived(Order $order): bool
    {
        $expected = 0;
        foreach ($order->orderItems as $item) {
            $pid = (string) $item->product_id;
            if (str_starts_with($pid, 'merchant_') || str_starts_with($pid, 'daywatch_')) {
                continue;
            }
            $expected += (int) $item->quantity;
        }

        $received = UserCard::where('order_id', $order->id)->count();
        return $expected > 0 && $received >= $expected;
    }

    /**
     * Construit le payload afrikard (items non marchand ni daywatch) avec les
     * valeurs natives, et liste les produits non résolubles.
     *
     * @return array{array<int,array{ProductId:int,Quantity:int,Value:int}>, array<int,string>}
     */
    private function buildPayload(Order $order): array
    {
        $service = app(ProductApiService::class);
        $missing = [];
        $payload = [];

        foreach ($order->orderItems as $item) {
            $pid = (string) $item->product_id;
            if (str_starts_with($pid, 'merchant_') || str_starts_with($pid, 'daywatch_')) {
                continue;
            }

            $productId = (int) $pid;
            $qty       = (int) $item->quantity;

            // 1. Valeur native stockée à la création de la commande
            if ($item->native_value && (float) $item->native_value > 0) {
                $payload[] = ['ProductId' => $productId, 'Quantity' => $qty, 'Value' => (int) round((float) $item->native_value)];
                continue;
            }

            // 2. Cache → API ciblée → deepScan (anciennes commandes / id virtuels)
            $resolved = $service->resolveNativeValue($pid, deepScan: true);
            if ($resolved && ($resolved['value'] ?? 0) > 0) {
                $payload[] = ['ProductId' => $productId, 'Quantity' => $qty, 'Value' => (int) $resolved['value']];
                continue;
            }

            $missing[] = $pid;
        }

        return [$payload, $missing];
    }
}