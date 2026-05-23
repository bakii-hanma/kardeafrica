<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCheckoutJob;
use App\Models\Order;
use App\Models\Reseller;
use App\Models\UserCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentRefundService;
use App\Services\ProductApiService;

class OrderController extends Controller
{
    /**
     * Display a listing of the user's orders.
     */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['orderItems', 'userCards'])
            ->latest()
            ->paginate(10);

        // If API request, return JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        }

        return view('orders.index', compact('orders'));
    }

    /**
     * Display the specified order with items and cards.
     */
    public function show(Request $request, Order $order)
    {
        // Ensure the user owns the order
        if ($order->user_id !== Auth::id()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Non autorise'], 403);
            }
            abort(403);
        }

        $order->load('orderItems');

        // Load associated user cards
        $userCards = UserCard::where('order_id', $order->id)
            ->where('user_id', Auth::id())
            ->with('orderItem')
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'order' => $order,
                'cards' => $userCards->makeVisible(['card_code', 'pin']),
            ]);
        }

        return view('orders.show', compact('order', 'userCards'));
    }

    /**
     * Re-essaye la livraison des cartes pour une commande payee mais sans cartes.
     * (cas typique : afrikard a echoue lors du paiement initial)
     */
    public function retryCheckout(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->userCards()->exists()) {
            return back()->with('error', 'Cette commande a deja ete livree.');
        }

        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return back()->with('error', 'Cette commande n\'a pas encore ete payee.');
        }

        // Lookup face values reels depuis afrikard. Cache → API ciblée → deepScan
        // multi-pages (pour anciennes commandes / produits récemment dépréciés).
        $service = app(ProductApiService::class);
        $order->load('orderItems');

        $missing  = [];
        $payload  = [];
        foreach ($order->orderItems as $item) {
            $productId = (int) $item->product_id;
            $qty       = (int) $item->quantity;

            // 1. Native value stocké sur l'OrderItem (commandes récentes)
            if ($item->native_value && (float) $item->native_value > 0) {
                $payload[] = ['ProductId' => $productId, 'Quantity' => $qty, 'Value' => (int) round((float) $item->native_value)];
                continue;
            }

            // 2. Lookup rapide cache + API ciblée
            $resolved = $service->resolveNativeValue($productId, deepScan: false);
            if ($resolved && $resolved['value'] > 0) {
                $payload[] = ['ProductId' => $productId, 'Quantity' => $qty, 'Value' => $resolved['value']];
                continue;
            }

            $missing[] = $productId;
        }

        // Si manquants : on warm-cache (deepScan) et retry
        if (!empty($missing)) {
            Log::info('Retry checkout: deepScan for missing products', ['order_id' => $order->id, 'missing' => $missing]);
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
            // Dispatch le job async qui retry avec backoff
            \App\Jobs\ProcessCheckoutJob::dispatch($order);

            Log::warning('Retry checkout: catalogue incomplete, dispatched async', [
                'order_id' => $order->id, 'still_missing' => $missing,
            ]);
            return back()->with('warning',
                'Catalogue fournisseur incomplet (produits ' . implode(', ', $missing) . ' manquants). '
                . 'On a programmé un nouvel essai automatique — patiente quelques minutes puis recharge la page.');
        }

        Log::info('Retry checkout (user) : appel afrikard', [
            'user_id'  => Auth::id(),
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
                return redirect()->route('orders.show', $order)
                    ->with('success', 'Cartes livrees avec succes !');
            }

            Log::warning('Retry checkout : afrikard a echoue', [
                'order_id' => $order->id,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            return back()->with('error', "Nos serveurs n'ont pas pu finaliser la livraison. Patientez quelques minutes puis réessayez. Si le problème persiste, contactez le support.");
        } catch (\Throwable $e) {
            Log::error('Retry checkout : exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', "Connexion temporairement indisponible. Vos données sont en sécurité — réessayez dans un instant.");
        }
    }

    /**
     * Demande de remboursement par le client. Disponible si :
     * - paiement OK + pas de cartes livrées (échec afrikard)
     * - statut order != deja remboursée
     *
     * Selon payment_method :
     * - 'ebilling'           → appel API transfer.php (rembourse Mobile Money/carte)
     * - 'cash_at_reseller'   → demande au vendeur de rendre le cash physiquement
     *                           (le vendeur valide ensuite via /vendor/cash/{order})
     * - 'simulated'          → annulation locale uniquement
     */
    public function refund(Request $request, Order $order, PaymentRefundService $refundSvc)
    {
        if ($order->user_id !== Auth::id()) abort(403);

        if ($order->userCards()->exists()) {
            return back()->with('error', 'Cette commande a déjà été livrée — impossible de rembourser automatiquement.');
        }
        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return back()->with('error', 'Cette commande n\'a pas été payée.');
        }
        if ($order->status === Order::STATUS_REFUNDED) {
            return back()->with('error', 'Cette commande est déjà remboursée.');
        }

        // Cash chez vendeur : on ne peut pas rembourser via API, le vendeur doit
        // rendre l'argent en physique. On informe le client.
        if ($order->payment_method === Order::PAYMENT_METHOD_CASH_RESELLER) {
            try {
                DB::transaction(function () use ($order) {
                    // Restitue le wallet du vendeur (les cartes n'ont pas été livrées)
                    $reseller = Reseller::lockForUpdate()->find($order->cash_reseller_id);
                    if ($reseller) {
                        $reseller->credit((float) $order->total_amount, null, "Remboursement commande #{$order->order_number}", $order->order_number);
                    }
                    $order->update([
                        'status'         => Order::STATUS_REFUNDED,
                        'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
                        'notes'          => 'Remboursée — le vendeur doit rendre le cash au client',
                    ]);
                });
                return back()->with('success', 'Remboursement enregistré. Va voir le vendeur Kardafrica pour récupérer ton argent en cash.');
            } catch (\Throwable $e) {
                return back()->with('error', 'Erreur : ' . $e->getMessage());
            }
        }

        // E-Billing : appel transfer
        if ($order->payment_method === 'ebilling') {
            $result = $refundSvc->refund(
                originalReference: $order->external_reference,
                amountFcfa: (int) round($order->total_amount),
                reason: "Remboursement commande {$order->order_number}",
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
            DB::transaction(function () use ($order) {
                $order->update([
                    'status'         => Order::STATUS_REFUNDED,
                    'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
                    'notes'          => 'Remboursée via E-Billing transfer',
                ]);
            });
            return back()->with('success', 'Remboursement effectué — l\'argent te sera renvoyé sur ton moyen de paiement.');
        } catch (\Throwable $e) {
            Log::error('Customer refund exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Sauvegarde les cartes recues lors d'un retry checkout.
     */
    private function saveRetryCards(Order $order, array $checkoutData): void
    {
        foreach ($checkoutData['items'] ?? [] as $item) {
            $productId = $item['productId'] ?? null;
            $cards     = $item['cards'] ?? [];
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
                    'face_value'       => $item['productFaceValue'] ?? $orderItem?->unit_price ?? 0,
                    'currency'         => $checkoutData['currency'] ?? 'XAF',
                    'image_url'        => $orderItem?->image_url,
                    'metadata'         => [
                        'retry'              => true,
                        'checkout_order_id'  => $checkoutData['orderId'] ?? null,
                    ],
                ]);
            }
        }
    }
}
