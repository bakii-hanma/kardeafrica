<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCheckoutJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ShoppingCart;
use App\Models\UserCard;
use App\Services\ProductApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * POST /api/orders
     * Cree une commande (status = pending). Aucun paiement encore lance —
     * le client appellera /api/payment/init ensuite.
     *
     * Source des items (par ordre de priorite) :
     *   1. body.items : tableau d'items envoye par le client (mobile)
     *   2. ShoppingCart en BDD pour cet user (web/session)
     */
    /**
     * POST /api/orders/simulate
     * Dev only (APP_DEBUG=true). Cree l'order, le marque payé (simulated),
     * tente la livraison via afrikard. Renvoie l'order avec ses cartes.
     */
    public function simulate(Request $request)
    {
        // SÉCURITÉ (H3) : flag dédié, jamais actif en prod (défaut false).
        if (!config('app.payments_simulation_enabled')) {
            return response()->json(['success' => false, 'message' => 'Simulation désactivée en production.'], 403);
        }

        $user = $request->user();

        $validated = $request->validate([
            'items'              => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|string|max:50',
            'items.*.name'       => 'required|string|max:255',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.quantity'   => 'required|integer|min:1|max:100',
            'items.*.image_url'  => 'nullable|string|max:1000',
        ]);

        $items       = collect($validated['items']);
        $subtotal    = $items->sum(fn($i) => $i['price'] * $i['quantity']);
        $externalRef = 'SIM_' . time() . '_' . rand(1000, 9999);

        try {
            $order = DB::transaction(function () use ($items, $subtotal, $externalRef, $user) {
                $order = Order::create([
                    'user_id'            => $user->id,
                    'external_reference' => $externalRef,
                    'status'             => Order::STATUS_PROCESSING,
                    'payment_status'     => Order::PAYMENT_STATUS_COMPLETED,
                    'subtotal'           => $subtotal,
                    'tax_amount'         => 0,
                    'total_amount'       => $subtotal,
                    'currency'           => 'XAF',
                    'payment_method'     => 'simulated',
                    'billing_details'    => ['note' => 'Paiement simulé (mobile)'],
                ]);

                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id'    => $order->id,
                        'product_id'  => $item['product_id'],
                        'card_id'     => null,
                        'name'        => $item['name'],
                        'unit_price'  => $item['price'],
                        'total_price' => $item['price'] * $item['quantity'],
                        'quantity'    => $item['quantity'],
                        'image_url'   => $item['image_url'] ?? null,
                    ]);
                }

                Payment::create([
                    'transaction_id'          => $externalRef,
                    'order_id'                => $order->id,
                    'user_id'                 => $user->id,
                    'payment_method'          => 'simulated',
                    'provider'                => 'dev-simulator',
                    'amount'                  => $subtotal,
                    'currency'                => 'XAF',
                    'status'                  => Payment::STATUS_COMPLETED,
                    'external_transaction_id' => $externalRef,
                    'processed_at'            => now(),
                ]);

                return $order->fresh()->load('orderItems');
            });

            // Tentative live de livraison via afrikard
            $deliveryError = null;
            try {
                $payload = $order->orderItems->map(function ($it) {
                    return [
                        'ProductId' => (int) $it->product_id,
                        'Quantity'  => (int) $it->quantity,
                        'Value'     => (int) round($it->unit_price),
                    ];
                })->values()->toArray();

                $response = Http::timeout(20)
                    ->post(config('services.product_api.base_url') . '/orders/checkout', $payload);

                if ($response->status() === 202 || $response->successful()) {
                    $checkoutData = $response->json();
                    $this->saveSimulateCards($order, $checkoutData);
                    $order->update([
                        'status'       => Order::STATUS_COMPLETED,
                        'completed_at' => now(),
                    ]);
                } else {
                    $deliveryError = "afrikard HTTP " . $response->status();
                    $order->update(['notes' => trim(($order->notes ?? '') . ' | Simulate: ' . $deliveryError)]);
                }
            } catch (\Throwable $e) {
                $deliveryError = $e->getMessage();
                $order->update(['notes' => trim(($order->notes ?? '') . ' | Simulate: ' . $deliveryError)]);
                Log::warning('API Simulate: afrikard exception', ['order_id' => $order->id, 'error' => $deliveryError]);
            }

            $order->refresh()->load('orderItems', 'userCards');

            return response()->json([
                'success'       => true,
                'simulated'     => true,
                'order'         => $order,
                'order_id'      => $order->id,
                'cards_delivered' => $order->userCards->count(),
                'delivery_error'  => $deliveryError,
                'message'       => $deliveryError
                    ? 'Paiement simulé. Cartes en attente de livraison.'
                    : 'Paiement simulé et cartes livrées avec succès !',
            ]);
        } catch (\Throwable $e) {
            Log::error('API Simulate: exception fatale', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur simulation : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sauvegarde les cartes reçues lors d'une simulation.
     */
    private function saveSimulateCards(Order $order, array $checkoutData): void
    {
        foreach ($checkoutData['items'] ?? [] as $apiItem) {
            $productId = $apiItem['productId'] ?? null;
            $cards     = $apiItem['cards'] ?? [];
            $orderItem = $order->orderItems
                ->firstWhere('product_id', (string) $productId)
                ?? $order->orderItems->firstWhere('product_id', $productId);

            foreach ($cards as $card) {
                UserCard::create([
                    'user_id'          => $order->user_id,
                    'order_id'         => $order->id,
                    'order_item_id'    => $orderItem?->id,
                    'product_id'       => (string) $productId,
                    'checkout_card_id' => $card['id'] ?? null,
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
                    'face_value'       => $apiItem['productFaceValue'] ?? $orderItem?->unit_price ?? 0,
                    'currency'         => $checkoutData['currency'] ?? 'XAF',
                    'image_url'        => $orderItem?->image_url,
                    'metadata'         => ['simulated_mobile' => true, 'checkout_order_id' => $checkoutData['orderId'] ?? null],
                ]);
            }
        }
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'items'                 => 'sometimes|array|min:1|max:100',
            'items.*.product_id'    => 'required_with:items|string|max:50',
            'items.*.name'          => 'required_with:items|string|max:255',
            'items.*.price'         => 'required_with:items|numeric|min:0',
            'items.*.quantity'      => 'required_with:items|integer|min:1|max:100',
            'items.*.image_url'     => 'nullable|string|max:1000',
        ]);

        if (!empty($validated['items'])) {
            $items = collect($validated['items']);
        } else {
            $items = ShoppingCart::where('user_id', $user->id)->get()->map(fn($i) => [
                'product_id' => $i->product_id,
                'name'       => $i->name,
                'price'      => (float) $i->price,
                'quantity'   => (int) $i->quantity,
                'image_url'  => $i->image_url,
            ]);
        }

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun item a commander.',
            ], 422);
        }

        // Résout la valeur native (10 EUR, etc.) à la création — afrikard
        // /orders/checkout attend la valeur NATIVE, pas le prix XAF affiché.
        $productService = app(ProductApiService::class);

        // C1 (Palier 3) — ENFORCEMENT. Ce endpoint accepte items[].price du client
        // (bypass possible du panier). Le prix facturé est donc recalculé côté
        // serveur depuis le catalogue ; le prix client n'est jamais cru.
        $items = $items->map(function ($i) use ($productService) {
            $i['price'] = $productService->authoritativeUnitPrice($i['product_id'], $i['price'] ?? 0);
            return $i;
        });

        $subtotal = $items->sum(fn($i) => $i['price'] * $i['quantity']);
        $externalRef = 'KARD_' . time() . '_' . rand(1000, 9999);

        $order = DB::transaction(function () use ($items, $subtotal, $externalRef, $user, $productService) {
            $order = Order::create([
                'user_id'            => $user->id,
                'external_reference' => $externalRef,
                'status'             => Order::STATUS_PENDING,
                'payment_status'     => Order::PAYMENT_STATUS_PENDING,
                'subtotal'           => $subtotal,
                'tax_amount'         => 0,
                'total_amount'       => $subtotal,
                'currency'           => 'XAF',
                'payment_method'     => 'ebilling',
                'billing_details'    => [],
            ]);

            foreach ($items as $item) {
                $native = $productService->resolveNativeValue($item['product_id']);
                OrderItem::create([
                    'order_id'        => $order->id,
                    'product_id'      => $item['product_id'],
                    'card_id'         => null,
                    'name'            => $item['name'],
                    'unit_price'      => $item['price'],
                    'total_price'     => $item['price'] * $item['quantity'],
                    'quantity'        => $item['quantity'],
                    'image_url'       => $item['image_url'] ?? null,
                    'native_value'    => $native['value']    ?? null,
                    'native_currency' => $native['currency'] ?? null,
                ]);
            }

            return $order->fresh()->load('orderItems');
        });

        return response()->json([
            'success' => true,
            'order'   => $order,
        ], 201);
    }

    /**
     * POST /api/orders/{order}/checkout
     * A appeler APRES verification reussie du paiement (is_completed = true).
     * - Re-verifie le statut du paiement cote serveur (securite).
     * - Appelle l'API externe /orders/checkout pour recuperer les cartes.
     * - Sauvegarde les UserCards, vide le panier, marque la commande completed.
     * - Idempotent : si deja traitee, renvoie les cartes existantes.
     * - Si l'API externe est down, dispatch ProcessCheckoutJob (fallback async).
     */
    public function checkout(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Commande non autorisee.'], 403);
        }

        // Idempotence : commande deja traitee
        if ($order->status === Order::STATUS_COMPLETED) {
            $cards = UserCard::where('order_id', $order->id)->get();
            return response()->json([
                'success' => true,
                'message' => 'Commande deja traitee.',
                'order'   => $order->load('orderItems'),
                'cards'   => $cards->makeVisible(['card_code', 'pin']),
            ]);
        }

        // Re-verification cote serveur du statut de paiement
        if (!$order->external_reference) {
            return response()->json([
                'success' => false,
                'message' => 'Reference de paiement manquante sur la commande.',
            ], 422);
        }

        $statusResponse = Http::timeout(10)->get(
            config('services.payment_backend.check_url'),
            ['external_reference' => $order->external_reference]
        );

        if (!$statusResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de verifier le statut du paiement.',
            ], 502);
        }

        $statusData = $statusResponse->json()['data'] ?? $statusResponse->json();
        $isCompleted = (bool) ($statusData['is_completed'] ?? false);

        if (!$isCompleted) {
            return response()->json([
                'success'       => false,
                'message'       => 'Le paiement n\'est pas confirme.',
                'payment_status' => $statusData['status'] ?? 'unknown',
            ], 402);
        }

        // Marquer Payment + Order comme paye
        Payment::where('transaction_id', $order->external_reference)->update([
            'status'       => Payment::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);
        $order->update(['payment_status' => Order::PAYMENT_STATUS_COMPLETED]);

        // Construire le payload pour l'API externe.
        // afrikard /orders/checkout attend la valeur NATIVE (10 EUR), pas le prix
        // XAF. On utilise native_value stocké à la création, fallback runtime si
        // absent (anciennes commandes).
        $order->load('orderItems');
        $service = app(ProductApiService::class);
        $payload = $order->orderItems->map(function ($item) use ($service) {
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

        Log::info('Sync checkout: appel API externe', [
            'order_id' => $order->id,
            'payload'  => $payload,
        ]);

        try {
            $response = Http::timeout(30)
                ->post(config('services.product_api.base_url') . '/orders/checkout', $payload);
        } catch (\Throwable $e) {
            Log::error('Sync checkout: exception reseau, fallback job async', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            ProcessCheckoutJob::dispatch($order);

            return response()->json([
                'success'        => true,
                'cards_pending'  => true,
                'message'        => 'API externe injoignable, traitement asynchrone en cours.',
                'order'          => $order,
                'cards'          => [],
            ], 202);
        }

        if (!($response->status() === 202 || $response->successful())) {
            Log::error('Sync checkout: API externe a echoue, fallback job async', [
                'order_id' => $order->id,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            ProcessCheckoutJob::dispatch($order);

            return response()->json([
                'success'        => true,
                'cards_pending'  => true,
                'message'        => 'API externe a echoue, retry asynchrone en cours.',
                'order'          => $order,
                'cards'          => [],
            ], 202);
        }

        $checkoutData = $response->json();

        // Sauvegarder les cartes + cleanup en transaction
        $savedCards = DB::transaction(function () use ($order, $checkoutData, $user) {
            $cards = $this->saveCards($order, $checkoutData);

            $order->update([
                'status'           => Order::STATUS_COMPLETED,
                'completed_at'     => now(),
                'billing_details'  => [
                    'checkout_order_id'   => $checkoutData['orderId'] ?? null,
                    'checkout_request_id' => $checkoutData['requestId'] ?? null,
                    'checkout_status'     => $checkoutData['status'] ?? null,
                ],
            ]);

            ShoppingCart::where('user_id', $user->id)->delete();

            return $cards;
        });

        return response()->json([
            'success' => true,
            'order'   => $order->fresh()->load('orderItems'),
            'cards'   => $savedCards->makeVisible(['card_code', 'pin']),
        ]);
    }

    /**
     * POST /api/orders/{order}/retry-delivery
     *
     * Relance la récupération des cartes auprès d'afrikard pour une commande
     * déjà payée mais dont la livraison a échoué (ProcessCheckoutJob KO,
     * timeout, indisponibilité afrikard, etc.).
     *
     * Mirror exact de OrderController::retryCheckout (web) mais en JSON pour
     * être consommé depuis l'app mobile via Sanctum. Lookup des native_value
     * depuis afrikard si l'OrderItem ne les a pas (anciennes commandes).
     *
     * Différences avec /checkout :
     *  - PAS de re-vérification du paiement (la commande EST déjà payée)
     *  - Garde-fou anti-double-livraison via userCards()->exists()
     *  - Fallback ProcessCheckoutJob si afrikard down (mêmes garanties que checkout)
     */
    public function retryDelivery(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Commande non autorisée.'], 403);
        }

        if ($order->userCards()->exists()) {
            $cards = $order->userCards()->get();
            return response()->json([
                'success' => true,
                'already_delivered' => true,
                'message' => 'Cette commande a déjà été livrée.',
                'cards'   => $cards->makeVisible(['card_code', 'pin']),
            ]);
        }

        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => "Cette commande n'a pas encore été payée.",
            ], 422);
        }

        // Lookup face values réels (le panier stocke en XAF mais l'API attend
        // la devise NATIVE). On préfère le native_value stocké sur l'OrderItem
        // (= nouvelles commandes), sinon resolveNativeValue (cache → API ciblée
        // → deepScan multi-pages pour les anciennes commandes ou produits
        // récemment dépréciés du cache).
        $service = app(ProductApiService::class);
        $order->load('orderItems');

        // Premier passage : cache + API ciblée (rapide)
        $missing  = [];
        $payload  = [];
        foreach ($order->orderItems as $item) {
            $productId = (int) $item->product_id;
            $qty       = (int) $item->quantity;

            // 1. Native value stocké sur l'OrderItem
            if ($item->native_value && (float) $item->native_value > 0) {
                $payload[] = ['ProductId' => $productId, 'Quantity' => $qty, 'Value' => (int) round((float) $item->native_value)];
                continue;
            }

            // 2. Lookup rapide
            $resolved = $service->resolveNativeValue($productId, deepScan: false);
            if ($resolved && $resolved['value'] > 0) {
                $payload[] = ['ProductId' => $productId, 'Quantity' => $qty, 'Value' => $resolved['value']];
                continue;
            }

            $missing[] = $productId;
        }

        // Deuxième passage : si manquants, on warm-cache (deepScan) et on retry
        if (!empty($missing)) {
            Log::info('Retry delivery: triggering deepScan for missing products', [
                'order_id' => $order->id, 'missing' => $missing,
            ]);
            $stillMissing = [];
            foreach ($missing as $productId) {
                $resolved = $service->resolveNativeValue($productId, deepScan: true);
                if ($resolved && $resolved['value'] > 0) {
                    $payload[] = [
                        'ProductId' => (int) $productId,
                        'Quantity'  => (int) ($order->orderItems->firstWhere('product_id', (string) $productId)?->quantity ?? 1),
                        'Value'     => $resolved['value'],
                    ];
                } else {
                    $stillMissing[] = $productId;
                }
            }
            $missing = $stillMissing;
        }

        if (!empty($missing)) {
            // Dernier recours : on dispatche le job async qui a sa propre logique
            // de retry avec backoff exponentiel — ça donne plus de chances que
            // le catalogue soit complet d'ici-là.
            ProcessCheckoutJob::dispatch($order);

            Log::warning('Retry delivery: catalogue incomplete, dispatched async job', [
                'order_id' => $order->id, 'still_missing' => $missing,
            ]);
            // success=true + cards_pending=true → le mobile affiche une info
            // "Récupération relancée" plutôt qu'une erreur (le job va retry).
            return response()->json([
                'success'       => true,
                'cards_pending' => true,
                'message'       => 'Le catalogue du fournisseur est en train d\'être rafraîchi (produits ' . implode(', ', $missing) . ' temporairement manquants). On retentera automatiquement dans quelques minutes.',
            ], 202);
        }

        Log::info('Retry delivery (mobile): appel afrikard', [
            'user_id' => $user->id, 'order_id' => $order->id, 'payload' => $payload,
        ]);

        try {
            $response = Http::timeout(30)
                ->post(config('services.product_api.base_url') . '/orders/checkout', $payload);
        } catch (\Throwable $e) {
            Log::error('Retry delivery (mobile): exception réseau, fallback async', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);
            ProcessCheckoutJob::dispatch($order);
            return response()->json([
                'success'       => true,
                'cards_pending' => true,
                'message'       => 'API externe injoignable, traitement asynchrone relancé.',
            ], 202);
        }

        if (!($response->status() === 202 || $response->successful())) {
            Log::warning('Retry delivery (mobile): afrikard a échoué, fallback async', [
                'order_id' => $order->id, 'status' => $response->status(), 'body' => $response->body(),
            ]);
            ProcessCheckoutJob::dispatch($order);
            return response()->json([
                'success'       => true,
                'cards_pending' => true,
                'message'       => "Nos serveurs n'ont pas pu finaliser la livraison. Retry asynchrone en cours.",
            ], 202);
        }

        $checkoutData = $response->json();
        $savedCards = DB::transaction(function () use ($order, $checkoutData) {
            $cards = $this->saveCards($order, $checkoutData);
            $order->update([
                'status'       => Order::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            return $cards;
        });

        return response()->json([
            'success' => true,
            'message' => 'Cartes livrées avec succès.',
            'order'   => $order->fresh()->load('orderItems'),
            'cards'   => $savedCards->makeVisible(['card_code', 'pin']),
        ]);
    }

    /**
     * Sauvegarde les cartes recues de l'API checkout dans user_cards.
     */
    private function saveCards(Order $order, array $checkoutData)
    {
        $items = $checkoutData['items'] ?? [];
        $saved = collect();

        foreach ($items as $item) {
            $productId = $item['productId'] ?? null;
            $cards     = $item['cards'] ?? [];

            $orderItem = $order->orderItems
                ->firstWhere('product_id', (string) $productId)
                ?? $order->orderItems->firstWhere('product_id', $productId);

            foreach ($cards as $card) {
                $saved->push(UserCard::create([
                    'user_id'           => $order->user_id,
                    'order_id'          => $order->id,
                    'order_item_id'     => $orderItem?->id,
                    'product_id'        => (string) $productId,
                    'checkout_card_id'  => $card['id'] ?? null,
                    'name'              => $orderItem?->name ?? 'Carte cadeau',
                    'brand'             => $orderItem?->name ? explode(' ', $orderItem->name)[0] : null,
                    'serial_number'     => $card['serialNumber'] ?? null,
                    'card_code'         => $card['cardCode'] ?? '',
                    'pin'               => $card['pin'] ?? null,
                    'expiration_date'   => !empty($card['expirationDate']) ? $card['expirationDate'] : null,
                    'status'            => $this->mapCardStatus($card['status'] ?? null),
                    'face_value'        => $item['productFaceValue'] ?? $orderItem?->unit_price ?? 0,
                    'currency'          => $checkoutData['currency'] ?? 'XAF',
                    'image_url'         => $orderItem?->image_url,
                    'metadata'          => [
                        'checkout_order_id'   => $checkoutData['orderId'] ?? null,
                        'checkout_status'     => $checkoutData['status'] ?? null,
                        'original_card_data'  => $card,
                    ],
                ]));
            }
        }

        Log::info('Sync checkout: cartes sauvegardees', [
            'order_id'    => $order->id,
            'cards_count' => $saved->count(),
        ]);

        return $saved;
    }

    /**
     * L'API externe renvoie "Sold" (carte vendue, non utilisee) ou "Active",
     * et "Used"/"Redeemed" pour une carte deja consommee.
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
}
