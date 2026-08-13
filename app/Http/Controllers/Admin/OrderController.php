<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Reseller;
use App\Models\UserCard;
use App\Jobs\ProcessCheckoutJob;
use App\Services\PaymentRefundService;
use App\Services\ProductApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Petit endpoint JSON pour tester la connectivité afrikard depuis l'UI admin.
     */
    public function pingAfrikard(ProductApiService $service)
    {
        return response()->json($service->ping());
    }

    /**
     * Liste les commandes payées sans cartes livrées (en attente du fournisseur).
     */
    public function pendingDelivery(Request $request)
    {
        $orders = Order::with(['user', 'orderItems'])
            ->where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->where('status', '!=', Order::STATUS_COMPLETED)
            ->whereDoesntHave('userCards')
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $service = app(ProductApiService::class);
        $apiStatus = $service->ping(5);

        return view('admin.orders.pending-delivery', compact('orders', 'apiStatus'));
    }

    /**
     * Retry en lot — relance /orders/checkout pour chaque commande sélectionnée.
     * S'arrête immédiatement si l'API est down (évite N appels qui timeout 30s chacun).
     */
    public function retryBulk(Request $request)
    {
        $request->validate([
            'order_ids'   => 'required|array|min:1|max:100',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $service = app(ProductApiService::class);
        $ping = $service->ping(5);
        if (!$ping['ok']) {
            return back()->with('error', "API afrikard indisponible — {$ping['message']}. Annulé sans aucun appel.");
        }

        $orders = Order::with('orderItems', 'userCards')
            ->whereIn('id', $request->order_ids)
            ->get();

        $allProducts = collect($service->getAllProducts(0, 99999))->keyBy('id');

        $results = ['success' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        foreach ($orders as $order) {
            // Skip si pas éligible
            if ($order->userCards->isNotEmpty()
                || $order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
                $results['skipped']++;
                continue;
            }

            $bulkMissing = [];
            $payload = $order->orderItems->map(function ($item) use ($allProducts, $service, &$bulkMissing) {
                $productId = (int) $item->product_id;
                if ($item->native_value && (float) $item->native_value > 0) {
                    return ['ProductId' => $productId, 'Quantity' => (int) $item->quantity, 'Value' => (int) round((float) $item->native_value)];
                }
                $product = $allProducts->get($productId) ?? $allProducts->get((string) $productId);
                if (!$product) $product = $service->getProductByIdLight($productId);
                if (!$product) { $bulkMissing[] = $productId; return null; }
                $value = (int) round($product['minFaceValue'] ?? $product['price']['min'] ?? 0);
                if ($value <= 0) { $bulkMissing[] = $productId; return null; }
                return ['ProductId' => $productId, 'Quantity' => (int) $item->quantity, 'Value' => $value];
            })->filter()->values()->toArray();

            if (!empty($bulkMissing) || empty($payload)) {
                $results['failed']++;
                $results['errors'][] = "#{$order->order_number} → catalogue incomplet";
                continue;
            }

            try {
                $response = Http::timeout(30)
                    ->post(config('services.product_api.base_url') . '/orders/checkout', $payload);

                if ($response->status() === 202 || $response->successful()) {
                    $this->saveRetryCards($order, $response->json());
                    $order->update([
                        'status'       => Order::STATUS_COMPLETED,
                        'completed_at' => now(),
                    ]);
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "#{$order->order_number} → HTTP {$response->status()}";
                    Log::warning('Bulk retry checkout : afrikard a echoue', [
                        'order_id' => $order->id, 'status' => $response->status(),
                    ]);
                }
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = "#{$order->order_number} → " . $e->getMessage();
                Log::error('Bulk retry checkout : exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                // Si on commence à timeout, on arrête le reste pour ne pas faire patienter l'admin
                if (str_contains($e->getMessage(), 'Timeout') || str_contains($e->getMessage(), 'Failed to connect')) {
                    $results['errors'][] = 'Lot interrompu : l\'API a coupé pendant le traitement.';
                    break;
                }
            }
        }

        $msg = "Retry terminé — {$results['success']} livrées, {$results['failed']} échouées, {$results['skipped']} ignorées.";
        if (!empty($results['errors'])) {
            $msg .= ' Détails : ' . implode(' | ', array_slice($results['errors'], 0, 3));
        }

        return back()->with($results['success'] > 0 ? 'success' : 'error', $msg);
    }

    public function index(Request $request)
    {
        // `withCount` : la liste affiche le nombre d'articles par commande.
        // Le charger par ligne coûtait une requête chacune — 20 par page.
        $query = Order::with('user')->withCount('orderItems')->latest();

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par statut de paiement
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Recherche par numero de commande
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20)->withQueryString();

        // Compteurs d'onglets : une requête groupée, pas une par statut.
        // Ils comptent TOUTES les commandes, pas la page courante — un onglet
        // qui n'annoncerait que sa page ne servirait à rien.
        $statusCounts = Order::query()
            ->select('status', \DB::raw('COUNT(*) as n'))
            ->groupBy('status')
            ->pluck('n', 'status');

        return view('admin.orders.index', compact('orders', 'statusCounts'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'orderItems', 'payments', 'userCards']);

        $canRetry = $order->status === Order::STATUS_PROCESSING
            && $order->userCards->isEmpty();

        return view('admin.orders.show', compact('order', 'canRetry'));
    }

    /**
     * Relancer l'appel afrikard /orders/checkout pour une commande payee mais sans cartes.
     * Sync direct (pas de job async, pour feedback immediat dans l'UI admin).
     */
    public function retryCheckout(Order $order)
    {
        $order->load('orderItems', 'userCards');

        if ($order->userCards->isNotEmpty()) {
            return back()->with('error', 'Cette commande a deja des cartes livrees.');
        }

        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return back()->with('error', 'Le paiement de cette commande n\'est pas confirme.');
        }

        // Lookup face values natives (afrikard refuse FCFA pour les cartes EUR/USD)
        $service = app(ProductApiService::class);
        $allProducts = collect($service->getAllProducts(0, 99999))->keyBy('id');

        $missing = [];
        $payload = $order->orderItems->map(function ($item) use ($allProducts, $service, &$missing) {
            $productId = (int) $item->product_id;

            // 1. Valeur native stockée à la création
            if ($item->native_value && (float) $item->native_value > 0) {
                return ['ProductId' => $productId, 'Quantity' => (int) $item->quantity, 'Value' => (int) round((float) $item->native_value)];
            }

            // 2/3. Fallback cache puis API
            $product = $allProducts->get($productId) ?? $allProducts->get((string) $productId);
            if (!$product) $product = $service->getProductByIdLight($productId);
            if (!$product) { $missing[] = $productId; return null; }
            $value = (int) round($product['minFaceValue'] ?? $product['price']['min'] ?? 0);
            if ($value <= 0) { $missing[] = $productId; return null; }
            return ['ProductId' => $productId, 'Quantity' => (int) $item->quantity, 'Value' => $value];
        })->filter()->values()->toArray();

        if (!empty($missing)) {
            Log::warning('Admin retry: aborted, products with unknown native value', [
                'order_id' => $order->id, 'missing' => $missing,
            ]);
            return back()->with('error', "Catalogue fournisseur incomplet (produits ".implode(',', $missing)." manquants). Réessaie après le warm-cache.");
        }

        Log::info('Retry checkout (admin) : appel afrikard', [
            'order_id' => $order->id,
            'payload'  => $payload,
        ]);

        try {
            $response = Http::timeout(30)
                ->post(config('services.product_api.base_url') . '/orders/checkout', $payload);

            if ($response->status() === 202 || $response->successful()) {
                $checkoutData = $response->json();
                $this->saveRetryCards($order, $checkoutData);
                $order->update([
                    'status'       => Order::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
                return back()->with('success', 'Cartes livrees avec succes pour #' . $order->order_number);
            }

            Log::warning('Admin retry checkout : afrikard a echoue', [
                'order_id' => $order->id, 'status' => $response->status(), 'body' => $response->body(),
            ]);
            return back()->with('error', "Le fournisseur n'a pas pu finaliser la livraison. Réessayez dans quelques minutes.");
        } catch (\Throwable $e) {
            Log::error('Admin retry checkout : exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', "Connexion temporairement indisponible avec le fournisseur. Réessayez dans un instant.");
        }
    }

    /**
     * Remboursement initié par l'admin : restaure le wallet du vendeur si cash,
     * appelle E-Billing transfer si paiement en ligne, puis annule la commande.
     */
    public function refund(Request $request, Order $order, PaymentRefundService $refundSvc)
    {
        if ($order->userCards()->exists()) {
            return back()->with('error', 'Cette commande a déjà des cartes livrées.');
        }
        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return back()->with('error', 'Le paiement n\'est pas confirmé.');
        }
        if ($order->status === Order::STATUS_REFUNDED) {
            return back()->with('error', 'Cette commande est déjà remboursée.');
        }

        // Cash chez vendeur : pas d'appel API, on note que le vendeur doit rendre le cash
        if ($order->payment_method === Order::PAYMENT_METHOD_CASH_RESELLER) {
            try {
                DB::transaction(function () use ($order) {
                    $reseller = Reseller::lockForUpdate()->find($order->cash_reseller_id);
                    if ($reseller) {
                        $reseller->refundCredit((float) $order->total_amount, "Remboursement #{$order->order_number}", $order->order_number);
                    }
                    $order->update([
                        'status'         => Order::STATUS_REFUNDED,
                        'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
                        'notes'          => 'Remboursée par admin — le vendeur doit rendre le cash au client',
                    ]);
                });
                return back()->with('success', 'Remboursement enregistré. Wallet vendeur restauré, le vendeur doit rendre le cash au client.');
            } catch (\Throwable $e) {
                return back()->with('error', 'Erreur : ' . $e->getMessage());
            }
        }

        // E-Billing : transfer
        if ($order->payment_method === 'ebilling') {
            $result = $refundSvc->refund(
                originalReference: $order->external_reference,
                amountFcfa: (int) round($order->total_amount),
                reason: "Remboursement #{$order->order_number}",
                extras: [
                    'msisdn' => data_get($order->billing_details, 'phone'),
                    'name'   => data_get($order->billing_details, 'name') ?? optional($order->user)->name,
                    'email'  => data_get($order->billing_details, 'email') ?? optional($order->user)->email,
                ],
            );
            if (!$result['ok']) {
                return back()->with('error', 'Remboursement E-Billing refusé : ' . $result['message']);
            }
        }

        try {
            $order->update([
                'status'         => Order::STATUS_REFUNDED,
                'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
                'notes'          => 'Remboursée par admin (' . $order->payment_method . ')',
            ]);
            return back()->with('success', 'Remboursement effectué avec succès.');
        } catch (\Throwable $e) {
            Log::error('Admin refund exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    private function saveRetryCards(Order $order, array $checkoutData): void
    {
        foreach ($checkoutData['items'] ?? [] as $item) {
            $productId = $item['productId'] ?? null;
            $cards     = $item['cards'] ?? [];
            $orderItem = $order->orderItems
                ->firstWhere('product_id', (string) $productId)
                ?? $order->orderItems->firstWhere('product_id', $productId);

            foreach ($cards as $card) {
                // H4 : idempotence sur checkout_card_id (rejeu livraison admin).
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
                    'face_value'       => $item['productFaceValue'] ?? $orderItem?->unit_price ?? 0,
                    'currency'         => $checkoutData['currency'] ?? 'XAF',
                    'image_url'        => $orderItem?->image_url,
                    'metadata'         => ['retry_admin' => true, 'checkout_order_id' => $checkoutData['orderId'] ?? null],
                ];
                $ccid !== null
                    ? UserCard::firstOrCreate(['checkout_card_id' => $ccid], $attrs)
                    : UserCard::create($attrs + ['checkout_card_id' => null]);
            }
        }
    }
}
