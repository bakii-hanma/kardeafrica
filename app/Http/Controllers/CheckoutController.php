<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCheckoutJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Reseller;
use App\Models\ShoppingCart;
use App\Models\UserCard;
use App\Services\ProductApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * Afficher la page de paiement (Checkout).
     */
    public function index()
    {
        return view('checkout.index');
    }

    /**
     * Demarre le paiement depuis la session web :
     * 1. Cree un Order pending depuis ShoppingCart de l'user
     * 2. Appelle futursowax/portal.php
     * 3. Renvoie portal_url + external_reference au client (JSON)
     *
     * Necessite l'auth session web (middleware 'auth' applique sur la route).
     */
    public function start(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'name'  => 'nullable|string|max:255',
        ]);

        $cartItems = ShoppingCart::where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Panier vide.'], 422);
        }

        $subtotal    = $cartItems->sum(fn($i) => $i->price * $i->quantity);
        $externalRef = 'KARD_' . time() . '_' . rand(1000, 9999);

        try {
            $order = DB::transaction(function () use ($cartItems, $subtotal, $externalRef, $user, $validated) {
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
                    // On garde le téléphone/email/nom du formulaire pour pouvoir les
                    // réutiliser plus tard (notamment pour les MerchantCardPurchase
                    // Carte Gabon, qui ont besoin de buyer_name/phone/email).
                    'billing_details'    => [
                        'phone' => $validated['phone'] ?? null,
                        'email' => $validated['email'] ?? $user->email ?? null,
                        'name'  => $validated['name']  ?? $user->name  ?? null,
                    ],
                ]);

                $svc = app(ProductApiService::class);
                foreach ($cartItems as $item) {
                    // Cartes-cadeau marchand (Carte Gabon) : product_id préfixé "merchant_"
                    // → pas dans le catalogue afrikard, donc on ne tente pas lookupNativeValue
                    //   (qui ferait un appel API inutile). Le prix XAF stocké = valeur faciale.
                    $isMerchant = str_starts_with((string) $item->product_id, 'merchant_');
                    $native = $isMerchant ? null : $svc->lookupNativeValue($item->product_id);

                    OrderItem::create([
                        'order_id'        => $order->id,
                        'product_id'      => $item->product_id,
                        'card_id'         => null,
                        'name'            => $item->name,
                        'unit_price'      => $item->price,
                        'native_value'    => $native['value']    ?? null,
                        'native_currency' => $native['currency'] ?? null,
                        'total_price'     => $item->price * $item->quantity,
                        'quantity'        => $item->quantity,
                        'image_url'       => $item->image_url,
                    ]);
                }

                return $order;
            });

            $payload = [
                'amount'            => (int) round($order->total_amount),
                'short_description' => 'Commande ' . $order->order_number,
                'reference'         => $externalRef,
                'email'             => $validated['email'] ?? $user->email ?? 'noreply@kardafrica.com',
                'msisdn'            => $this->formatPhone($validated['phone']),
                'name'              => $validated['name'] ?? $user->name ?? 'Client KardAfrica',
                'callback_url'      => url('/payment/return?ref=' . $externalRef),
                'format'            => 'json',
            ];

            $response = Http::timeout(20)
                ->acceptJson()
                ->asForm()
                ->post(config('services.payment_backend.init_url'), $payload);

            Log::info('Checkout start: futursowax response', [
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                $body = $response->json();
                return response()->json([
                    'success' => false,
                    'message' => $body['error'] ?? 'Erreur lors de l\'initialisation du paiement.',
                ], 502);
            }

            $body      = $response->json();
            $data      = $body['data'] ?? [];
            $portalUrl = $data['portal_url'] ?? null;
            $billId    = $data['bill_id'] ?? null;

            if (!$portalUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reponse portal.php incomplete.',
                ], 502);
            }

            Payment::firstOrCreate(
                ['transaction_id' => $externalRef],
                [
                    'order_id'                => $order->id,
                    'user_id'                 => $user->id,
                    'payment_method'          => 'ebilling',
                    'provider'                => 'futursowax',
                    'amount'                  => $order->total_amount,
                    'currency'                => 'XAF',
                    'status'                  => Payment::STATUS_PENDING,
                    'external_transaction_id' => $billId,
                ]
            );

            $order->update(['payment_status' => Order::PAYMENT_STATUS_PROCESSING]);

            return response()->json([
                'success'            => true,
                'portal_url'         => $portalUrl,
                'bill_id'            => $billId,
                'external_reference' => $externalRef,
                'order_id'           => $order->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Checkout start: exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'initialisation du paiement.',
            ], 500);
        }
    }

    /**
     * DEV ONLY : simule un paiement reussi sans passer par futursowax/E-Billing.
     * - Cree l'Order depuis ShoppingCart
     * - Marque Payment + Order comme completed
     * - Appelle afrikard /orders/checkout pour livrer les cartes
     * - Renvoie l'URL de la page commande
     */
    public function simulate(Request $request)
    {
        $user = $request->user();

        // SÉCURITÉ (H3) : simulation autorisée UNIQUEMENT si le flag dédié est
        // actif (défaut false, jamais en prod). Le bypass admin a été retiré :
        // même un admin ne doit pas pouvoir émettre de vraies cartes gratuites.
        if (!config('app.payments_simulation_enabled')) {
            abort(404);
        }

        $cartItems = ShoppingCart::where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Panier vide.'], 422);
        }

        $subtotal    = $cartItems->sum(fn($i) => $i->price * $i->quantity);
        $externalRef = 'SIM_' . time() . '_' . rand(1000, 9999);

        try {
            // 1. Cree l'order + items + payment dans une transaction
            $order = DB::transaction(function () use ($cartItems, $subtotal, $externalRef, $user) {
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
                    'billing_details'    => [
                        'note'  => 'Paiement simule (DEV)',
                        'phone' => $user->phone ?? null,
                        'email' => $user->email ?? null,
                        'name'  => $user->name  ?? null,
                    ],
                ]);

                $svc = app(ProductApiService::class);
                foreach ($cartItems as $item) {
                    // Skip lookupNativeValue pour les items marchand (Carte Gabon)
                    // — ils ne sont pas dans le catalogue afrikard.
                    $isMerchant = str_starts_with((string) $item->product_id, 'merchant_');
                    $native = $isMerchant ? null : $svc->lookupNativeValue($item->product_id);
                    OrderItem::create([
                        'order_id'        => $order->id,
                        'product_id'      => $item->product_id,
                        'card_id'         => null,
                        'name'            => $item->name,
                        'unit_price'      => $item->price,
                        'native_value'    => $native['value']    ?? null,
                        'native_currency' => $native['currency'] ?? null,
                        'total_price'     => $item->price * $item->quantity,
                        'quantity'        => $item->quantity,
                        'image_url'       => $item->image_url,
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

            // 2a. Items marchand (Carte Gabon) : génération LOCALE de la
            //     MerchantCardPurchase via MerchantCardCode (= équivalent du
            //     payload `cards[]` d'afrikard mais 100% en BDD).
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
                    Log::error('Checkout simulate: échec création MerchantCardPurchase', [
                        'order_id'      => $order->id,
                        'order_item_id' => $item->id,
                        'product_id'    => $item->product_id,
                        'error'         => $e->getMessage(),
                    ]);
                }
            }

            // 2b. Si AUCUN item afrikard restant, on complète l'order immédiatement
            //     sans appeler l'API. La page commande affichera directement les cartes.
            if ($afrikardItems->isEmpty()) {
                $order->update([
                    'status'       => Order::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

                return response()->json([
                    'success'      => true,
                    'order_id'     => $order->id,
                    'redirect_url' => route('orders.show', $order),
                    'message'      => 'Carte locale livrée immédiatement (paiement simulé).',
                ]);
            }

            // 2c. Lookup des face values reelles dans le catalogue afrikard pour
            //     les items afrikard restants
            $service = app(ProductApiService::class);
            $allProducts = collect($service->getAllProducts(0, 99999))->keyBy('id');

            $payload = $afrikardItems->map(function ($item) use ($allProducts) {
                $productId = (int) $item->product_id;
                $product = $allProducts->get($productId);
                $value = $product
                    ? (int) round($product['minFaceValue'] ?? $product['price']['min'] ?? $item->unit_price)
                    : (int) round($item->unit_price);

                return [
                    'ProductId' => $productId,
                    'Quantity'  => (int) $item->quantity,
                    'Value'     => $value,
                ];
            })->values()->toArray();

            Log::info('Checkout simulate: appel afrikard', [
                'order_id' => $order->id,
                'payload'  => $payload,
            ]);

            // 3. Appelle afrikard /orders/checkout
            $cardsDelivered = false;
            $afrikardError  = null;
            try {
                $response = Http::timeout(30)
                    ->post(config('services.product_api.base_url') . '/orders/checkout', $payload);

                if ($response->status() === 202 || $response->successful()) {
                    $checkoutData = $response->json();
                    $this->saveSimulatedCards($order, $checkoutData);

                    $order->update([
                        'status'          => Order::STATUS_COMPLETED,
                        'completed_at'    => now(),
                        'billing_details' => array_merge($order->billing_details ?? [], [
                            'checkout_order_id'   => $checkoutData['orderId'] ?? null,
                            'checkout_request_id' => $checkoutData['requestId'] ?? null,
                            'checkout_status'     => $checkoutData['status'] ?? null,
                        ]),
                    ]);
                    $cardsDelivered = true;
                } else {
                    $afrikardError = 'API afrikard a renvoye HTTP ' . $response->status();
                    Log::warning('Checkout simulate: afrikard echec', [
                        'order_id' => $order->id,
                        'status'   => $response->status(),
                        'body'     => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                $afrikardError = 'Erreur reseau afrikard : ' . $e->getMessage();
                Log::warning('Checkout simulate: afrikard exception', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }

            // Si afrikard a echoue, on note l'erreur sur l'order mais on ne re-throw PAS
            // (le job async peut planter en sync donc on ne le dispatch que si le queue n'est pas sync)
            if (!$cardsDelivered) {
                $order->update([
                    'notes' => trim(($order->notes ?? '') . ' | Simulate: ' . ($afrikardError ?? 'echec inconnu')),
                ]);
                if (config('queue.default') !== 'sync') {
                    try {
                        ProcessCheckoutJob::dispatch($order);
                    } catch (\Throwable $e) {
                        Log::warning('Dispatch fallback job failed', ['error' => $e->getMessage()]);
                    }
                }
            }

            // 3. Vide le panier
            ShoppingCart::where('user_id', $user->id)->delete();

            return response()->json([
                'success'         => true,
                'simulated'       => true,
                'cards_delivered' => $cardsDelivered,
                'order_id'        => $order->id,
                'redirect_url'    => route('orders.show', $order),
                'message'         => $cardsDelivered
                    ? 'Paiement simule avec succes. Cartes livrees.'
                    : 'Commande creee mais livraison des cartes en echec : ' . ($afrikardError ?? 'erreur inconnue'),
                'warning'         => $cardsDelivered ? null : $afrikardError,
            ]);

        } catch (\Throwable $e) {
            Log::error('Checkout simulate: exception fatale', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la simulation : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mappe le statut renvoye par l'API ("Sold", "Active", "Used", ...).
     */
    private function mapApiCardStatus(?string $apiStatus): string
    {
        return match (strtolower($apiStatus ?? '')) {
            'used', 'redeemed', 'consumed' => UserCard::STATUS_USED,
            'expired'                       => UserCard::STATUS_EXPIRED,
            default                         => UserCard::STATUS_ACTIVE,
        };
    }

    /**
     * Sauvegarde les cartes recues d'afrikard dans user_cards.
     */
    private function saveSimulatedCards(Order $order, array $checkoutData): void
    {
        $items = $checkoutData['items'] ?? [];

        foreach ($items as $item) {
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
                    'status'           => $this->mapApiCardStatus($card['status'] ?? null),
                    'face_value'       => $item['productFaceValue'] ?? $orderItem?->unit_price ?? 0,
                    'currency'         => $checkoutData['currency'] ?? 'XAF',
                    'image_url'        => $orderItem?->image_url,
                    'metadata'         => [
                        'simulated'           => true,
                        'checkout_order_id'   => $checkoutData['orderId'] ?? null,
                        'original_card_data'  => $card,
                    ],
                ]);
            }
        }
    }

    /**
     * Vérifie un code vendeur (KA-V-XXXX) et renvoie son état pour la sélection au checkout.
     * Utilisé en AJAX par le checkout pour afficher le nom + dispo avant submit.
     */
    public function lookupReseller(Request $request)
    {
        $code = strtoupper(trim((string) $request->query('code', '')));
        if ($code === '') {
            return response()->json(['found' => false, 'message' => 'Code requis.'], 422);
        }

        $reseller = Reseller::where('vendor_code', $code)
            ->where('is_active', true)
            ->first();

        if (!$reseller) {
            return response()->json([
                'found'   => false,
                'message' => 'Vendeur introuvable ou inactif.',
            ]);
        }

        return response()->json([
            'found'             => true,
            'vendor_code'       => $reseller->vendor_code,
            'name'              => $reseller->name,
            'available_balance' => (float) $reseller->available_balance,
        ]);
    }

    /**
     * Crée une commande "cash chez vendeur" :
     * - vérifie panier non vide + vendeur actif + solde dispo suffisant
     * - bloque le montant sur le wallet du vendeur (pas de débit avant confirmation)
     * - crée l'Order en pending avec un code de confirmation à 6 chiffres
     * - vide le panier
     * - renvoie redirect_url vers la page de suivi (cash.show)
     *
     * Le client doit ensuite se rendre physiquement chez le vendeur, qui valide
     * l'encaissement depuis son portail. Le débit + livraison ne se fait QU'À ce
     * moment-là (CashOrderController côté vendor).
     */
    public function payCash(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'phone'       => 'required|string|max:20',
            'email'       => 'nullable|email|max:255',
            'name'        => 'nullable|string|max:255',
            'vendor_code' => 'required|string|max:20',
        ]);

        $cartItems = ShoppingCart::where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Panier vide.'], 422);
        }

        $reseller = Reseller::where('vendor_code', strtoupper(trim($validated['vendor_code'])))
            ->where('is_active', true)
            ->first();
        if (!$reseller) {
            return response()->json([
                'success' => false,
                'message' => 'Vendeur introuvable ou inactif.',
            ], 422);
        }

        $subtotal = (float) $cartItems->sum(fn($i) => $i->price * $i->quantity);
        if ((float) $reseller->available_balance < $subtotal) {
            return response()->json([
                'success' => false,
                'message' => 'Ce vendeur n\'a pas assez de solde disponible. Choisis un autre vendeur ou paie en ligne.',
            ], 422);
        }

        $externalRef = 'KARD_CASH_' . time() . '_' . rand(1000, 9999);
        $confirmCode = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt   = now()->addHours(2);

        try {
            $order = DB::transaction(function () use ($cartItems, $subtotal, $externalRef, $user, $reseller, $confirmCode, $expiresAt, $validated) {
                // Verrou sur les fonds AVANT de créer la commande — rollback en cascade si échec
                $reseller->lockFunds($subtotal, $externalRef);

                $order = Order::create([
                    'user_id'                => $user->id,
                    'external_reference'     => $externalRef,
                    'status'                 => Order::STATUS_PENDING,
                    'payment_status'         => Order::PAYMENT_STATUS_PENDING,
                    'subtotal'               => $subtotal,
                    'tax_amount'             => 0,
                    'total_amount'           => $subtotal,
                    'currency'               => 'XAF',
                    'payment_method'         => Order::PAYMENT_METHOD_CASH_RESELLER,
                    'cash_reseller_id'       => $reseller->id,
                    'cash_lock_expires_at'   => $expiresAt,
                    'cash_confirmation_code' => $confirmCode,
                    'billing_details'        => [
                        'name'  => $validated['name']  ?? $user->name  ?? null,
                        'email' => $validated['email'] ?? $user->email ?? null,
                        'phone' => $validated['phone'],
                    ],
                ]);

                $svc = app(ProductApiService::class);
                foreach ($cartItems as $item) {
                    $native = $svc->lookupNativeValue($item->product_id);
                    OrderItem::create([
                        'order_id'        => $order->id,
                        'product_id'      => $item->product_id,
                        'card_id'         => null,
                        'name'            => $item->name,
                        'unit_price'      => $item->price,
                        'native_value'    => $native['value']    ?? null,
                        'native_currency' => $native['currency'] ?? null,
                        'total_price'     => $item->price * $item->quantity,
                        'quantity'        => $item->quantity,
                        'image_url'       => $item->image_url,
                    ]);
                }

                ShoppingCart::where('user_id', $user->id)->delete();

                return $order;
            });

            return response()->json([
                'success'      => true,
                'redirect_url' => route('checkout.cash.show', $order),
                'order_id'     => $order->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Checkout cash: exception', [
                'error'   => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Impossible de créer la commande cash.',
            ], 500);
        }
    }

    /**
     * Page de suivi affichée au client après création d'une commande cash :
     * code à présenter au vendeur, expiration, statut en temps réel.
     */
    public function showCashOrder(Order $order)
    {
        if ($order->user_id !== Auth::id() || $order->payment_method !== Order::PAYMENT_METHOD_CASH_RESELLER) {
            abort(404);
        }

        $order->load(['orderItems', 'cashReseller']);
        return view('checkout.cash', ['order' => $order]);
    }

    private function formatPhone(?string $phone): string
    {
        if (!$phone) return '24174000000';
        $cleaned = preg_replace('/\D/', '', $phone);
        if (str_starts_with($cleaned, '00'))  $cleaned = substr($cleaned, 2);
        if (str_starts_with($cleaned, '241')) return $cleaned;
        if (str_starts_with($cleaned, '0'))   return '241' . substr($cleaned, 1);
        return '241' . $cleaned;
    }
}
