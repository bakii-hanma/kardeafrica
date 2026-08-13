<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCheckoutJob;
use App\Models\MerchantCardPurchase;
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

        // ============================================================
        // Self-heal : si la commande est payée et contient des items carte
        // locale (Carte Gabon) sans MerchantCardPurchase, on les génère ici
        // (boucle sur chaque item marchand, idempotent). Couvre aussi le cas
        // mixte afrikard + local. Évite que le client doive cliquer 'Relancer'.
        // ============================================================
        if ($order->payment_status === Order::PAYMENT_STATUS_COMPLETED) {
            foreach ($order->orderItems as $item) {
                if (\App\Support\MerchantCardCode::isMerchantOrderItem($item)) {
                    try {
                        \App\Support\MerchantCardCode::createPurchaseForOrderItem($order, $item);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('OrderController show: self-heal MerchantCardPurchase échoué', [
                            'order_id'      => $order->id,
                            'order_item_id' => $item->id,
                            'error'         => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Load associated user cards (afrikard catalog items + miroirs carte locale)
        $userCards = UserCard::where('order_id', $order->id)
            ->where('user_id', Auth::id())
            ->with('orderItem')
            ->get();

        // Load merchant card purchases (Carte Gabon items)
        $merchantPurchases = MerchantCardPurchase::where('order_id', $order->id)
            ->with('merchantCard')
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'order' => $order,
                'cards' => $userCards->makeVisible(['card_code', 'pin']),
                'merchant_purchases' => $merchantPurchases->map(fn ($p) => [
                    'id'           => $p->id,
                    'unique_code'  => $p->unique_code,
                    'amount'       => $p->amount,
                    'merchant'     => 'KardAfrica',
                    'expires_at'   => $p->expires_at,
                    'status'       => $p->status,
                ]),
            ]);
        }

        return view('orders.show', compact('order', 'userCards', 'merchantPurchases'));
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

        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return back()->with('error', 'Cette commande n\'a pas encore ete payee.');
        }

        $order->load('orderItems');

        // ============================================================
        // 0. Items marchand (Carte Gabon) : création LOCALE en synchrone.
        //    Pas d'appel API, pas de queue worker requis — le job database
        //    n'a aucun worker sur shared hosting.
        // ============================================================
        $merchantItems = $order->orderItems->filter(
            fn ($i) => \App\Support\MerchantCardCode::isMerchantOrderItem($i)
        );
        $afrikardItems = $order->orderItems->reject(
            fn ($i) => \App\Support\MerchantCardCode::isMerchantOrderItem($i)
        );

        foreach ($merchantItems as $item) {
            try {
                \App\Support\MerchantCardCode::createPurchaseForOrderItem($order, $item);
            } catch (\Throwable $e) {
                Log::error('Retry checkout: échec MerchantCardPurchase', [
                    'order_id'      => $order->id,
                    'order_item_id' => $item->id,
                    'product_id'    => $item->product_id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        // Si aucun item afrikard, on complète la commande et on renvoie.
        if ($afrikardItems->isEmpty()) {
            $order->update([
                'status'       => Order::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            return redirect()->route('orders.show', $order)
                ->with('success', 'Cartes marchand livrées !');
        }

        // Au-delà, on poursuit le flow historique pour les items afrikard.
        if ($order->userCards()->exists()) {
            return back()->with('error', 'Cette commande a deja ete livree.');
        }

        // Lookup face values reels depuis afrikard. Cache → API ciblée → deepScan
        // multi-pages (pour anciennes commandes / produits récemment dépréciés).
        $service = app(ProductApiService::class);

        $missing  = [];
        $payload  = [];
        foreach ($afrikardItems as $item) {
            $productId = (int) $item->product_id;   // id RÉEL afrikard ("1571149v25" → 1571149)
            $qty       = (int) $item->quantity;

            // 1. Native value stocké sur l'OrderItem (commandes récentes)
            if ($item->native_value && (float) $item->native_value > 0) {
                $payload[] = ['ProductId' => $productId, 'Quantity' => $qty, 'Value' => (int) round((float) $item->native_value)];
                continue;
            }

            // 2. Lookup rapide cache + API ciblée — sur l'id ORIGINAL (un id
            // virtuel encode sa valeur ; l'id réel retomberait sur le bas de plage)
            $resolved = $service->resolveNativeValue($item->product_id, deepScan: false);
            if ($resolved && $resolved['value'] > 0) {
                $payload[] = ['ProductId' => $productId, 'Quantity' => $qty, 'Value' => $resolved['value']];
                continue;
            }

            $missing[] = (string) $item->product_id;
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

        // Gardes rapides (messages précis) — re-testées ensuite DANS le verrou,
        // seules les gardes verrouillées font foi contre un double submit.
        if ($order->userCards()->exists()) {
            return back()->with('error', 'Cette commande a déjà été livrée — impossible de rembourser automatiquement.');
        }
        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return back()->with('error', 'Cette commande n\'a pas été payée.');
        }
        if (in_array($order->status, [Order::STATUS_REFUNDED, Order::STATUS_REFUNDING], true)) {
            return back()->with('error', 'Cette commande est déjà remboursée ou un remboursement est en cours.');
        }

        // Cash chez vendeur : on ne peut pas rembourser via API, le vendeur doit
        // rendre l'argent en physique. On informe le client. Tout est local →
        // une seule transaction verrouillée suffit (pas d'appel PSP).
        if ($order->payment_method === Order::PAYMENT_METHOD_CASH_RESELLER) {
            try {
                $already = DB::transaction(function () use ($order) {
                    // Verrou de la commande + re-test des gardes DANS le verrou
                    $locked = Order::where('id', $order->id)->lockForUpdate()->first();
                    if ($locked->payment_status !== Order::PAYMENT_STATUS_COMPLETED
                        || in_array($locked->status, [Order::STATUS_REFUNDED, Order::STATUS_REFUNDING], true)
                        || $locked->userCards()->exists()) {
                        return true;
                    }

                    // Restitue le wallet du vendeur (les cartes n'ont pas été livrées)
                    $reseller = Reseller::lockForUpdate()->find($locked->cash_reseller_id);
                    if ($reseller) {
                        $reseller->refundCredit((float) $locked->total_amount, "Remboursement commande #{$locked->order_number}", $locked->order_number);
                    }
                    $locked->update([
                        'status'         => Order::STATUS_REFUNDED,
                        'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
                        'notes'          => 'Remboursée — le vendeur doit rendre le cash au client',
                    ]);
                    return false;
                });

                if ($already) {
                    return back()->with('error', 'Cette commande est déjà remboursée ou un remboursement est en cours.');
                }
                return back()->with('success', 'Remboursement enregistré. Va voir le vendeur Kardafrica pour récupérer ton argent en cash.');
            } catch (\Throwable $e) {
                return back()->with('error', 'Erreur : ' . $e->getMessage());
            }
        }

        // ============================================================
        // Machine à états anti double-virement (H5) :
        // 1. transaction {verrou + re-test des gardes + état 'refunding'}
        // 2. virement E-Billing HORS transaction
        // 3. transaction {statut REFUNDED}
        // ============================================================

        // Transaction 1 : réserve le remboursement. Un double submit concurrent
        // trouve la commande en 'refunding' (ou 'refunded') et sort proprement.
        $previousStatus = null;
        try {
            $claimed = DB::transaction(function () use ($order, &$previousStatus) {
                $locked = Order::where('id', $order->id)->lockForUpdate()->first();

                if ($locked->payment_status !== Order::PAYMENT_STATUS_COMPLETED
                    || in_array($locked->status, [Order::STATUS_REFUNDED, Order::STATUS_REFUNDING], true)
                    || $locked->userCards()->exists()) {
                    return false;
                }

                $previousStatus = $locked->status;
                $locked->update(['status' => Order::STATUS_REFUNDING]);
                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Customer refund claim exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }

        if (!$claimed) {
            return back()->with('error', 'Cette commande est déjà remboursée ou un remboursement est en cours.');
        }

        // E-Billing : appel transfer — HORS transaction (appel HTTP externe).
        // La référence de transfert est déterministe (REFUND_<ref d'origine>),
        // le PSP peut donc dédupliquer un éventuel double appel.
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
                // Échec du virement → retour à l'état antérieur pour permettre un retry
                Order::where('id', $order->id)->update(['status' => $previousStatus]);
                return back()->with('error', 'Remboursement E-Billing refusé : ' . $result['message']);
            }
        }

        // Transaction 2 : clôture du remboursement
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
            // Retour à l'état antérieur : le retry est sûr même côté E-Billing
            // grâce à la référence de transfert déterministe (déduplication PSP).
            Order::where('id', $order->id)->update(['status' => $previousStatus]);
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
                // H4 : idempotence — un rejeu avec le même checkout_card_id ne
                // recrée pas la carte (et n'entre pas en conflit avec l'unique).
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
                    'metadata'         => [
                        'retry'              => true,
                        'checkout_order_id'  => $checkoutData['orderId'] ?? null,
                    ],
                ];
                $ccid !== null
                    ? UserCard::firstOrCreate(['checkout_card_id' => $ccid], $attrs)
                    : UserCard::create($attrs + ['checkout_card_id' => null]);
            }
        }
    }
}
