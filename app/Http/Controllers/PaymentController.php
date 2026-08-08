<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\UserCard;
use App\Models\ShoppingCart;
use App\Jobs\ProcessCheckoutJob;

class PaymentController extends Controller
{
    /**
     * Formater le numero de telephone (format Gabon +241)
     */
    private function formatPhoneNumber($phone)
    {
        if (empty($phone)) return '24174000000';

        $cleaned = preg_replace('/\D/', '', $phone);

        if (str_starts_with($cleaned, '00')) {
            $cleaned = substr($cleaned, 2);
        }

        if (str_starts_with($cleaned, '241')) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '0')) {
            return '241' . substr($cleaned, 1);
        }

        return '241' . $cleaned;
    }

    /**
     * Initialiser un paiement via futursowax/portal.php pour une commande existante.
     *
     * Body attendu :
     *   - order_id (required) : id de la commande pending
     *   - phone   (required) : numero du payeur
     *   - email   (optional) : sinon user->email
     *   - name    (optional) : sinon user->name
     */
    public function init(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'phone'    => 'required|string|max:20',
                'email'    => 'nullable|email|max:255',
                'name'     => 'nullable|string|max:255',
            ]);

            $order = Order::findOrFail($validated['order_id']);
            $user  = $request->user();

            if ($user && $order->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Commande non autorisee.'], 403);
            }

            if ($order->payment_status === Order::PAYMENT_STATUS_COMPLETED) {
                return response()->json(['success' => false, 'message' => 'Commande deja payee.'], 409);
            }

            $externalRef = $order->external_reference ?: ('KARD_' . time() . '_' . rand(1000, 9999));
            if (!$order->external_reference) {
                $order->update(['external_reference' => $externalRef]);
            }

            $payload = [
                'amount'            => (int) round($order->total_amount),
                'short_description' => 'Commande ' . $order->order_number,
                'reference'         => $externalRef,
                'email'             => $validated['email'] ?? $user?->email ?? 'noreply@kardafrica.com',
                'msisdn'            => $this->formatPhoneNumber($validated['phone']),
                'name'              => $validated['name'] ?? $user?->name ?? 'Client KardAfrica',
                'callback_url'      => config('services.payment_backend.callback_url')
                    ?? url('/payment/return?ref=' . $externalRef),
                'format'            => 'json',
            ];

            $response = Http::timeout(20)
                ->acceptJson()
                ->asForm()
                ->post(config('services.payment_backend.init_url'), $payload);

            Log::info('Futursowax portal.php response', [
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                $body = $response->json();
                return response()->json([
                    'success' => false,
                    'message' => $body['error'] ?? 'Erreur lors de l\'initialisation du paiement.',
                    'code'    => $body['code'] ?? null,
                ], 502);
            }

            $body = $response->json();
            $data = $body['data'] ?? [];
            $portalUrl = $data['portal_url'] ?? null;
            $billId    = $data['bill_id'] ?? null;

            if (!$portalUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reponse portal.php incomplete (portal_url manquant).',
                ], 502);
            }

            // Trace une intention de paiement (pending), une seule par external_reference
            Payment::firstOrCreate(
                ['transaction_id' => $externalRef],
                [
                    'order_id'                => $order->id,
                    'user_id'                 => $order->user_id,
                    'payment_method'          => 'ebilling',
                    'provider'                => 'futursowax',
                    'amount'                  => $order->total_amount,
                    'currency'                => $order->currency ?? 'XAF',
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
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Donnees invalides',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment init exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'initialisation du paiement.',
            ], 500);
        }
    }

    /**
     * Verifier le statut d'un paiement via futursowax/check_status.php
     * Format de reponse aligne sur la doc : { is_completed, is_failed, status }
     */
    public function checkStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'external_reference' => 'required|string|max:100',
            ]);

            $response = Http::timeout(10)->get(
                config('services.payment_backend.check_url'),
                ['external_reference' => $validated['external_reference']]
            );

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la verification du statut.',
                ], 502);
            }

            $body = $response->json();
            $data = $body['data'] ?? $body;

            // SÉCURITÉ (C6) : endpoint PUBLIC (polling WebView). On ne renvoie QUE
            // le statut — jamais le payload brut du fournisseur qui contient des
            // données personnelles du payeur (msisdn, email, montant). Sinon,
            // couplé à des références devinables, c'était un outil d'énumération
            // des paiements d'autrui.
            return response()->json([
                'success'      => true,
                'status'       => $data['status'] ?? 'unknown',
                'is_completed' => (bool) ($data['is_completed'] ?? false),
                'is_failed'    => (bool) ($data['is_failed'] ?? false),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Donnees invalides',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment check exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la verification.',
            ], 500);
        }
    }

    /**
     * Retour apres paiement (Redirect from E-Billing)
     */
    public function handleReturn(Request $request)
    {
        $externalRef = $request->query('ref');

        if (!$externalRef) {
            return redirect()->route('cart.index')->with('error', 'Reference de paiement manquante.');
        }

        // Récupère la commande pour alimenter l'événement Meta Pixel "Purchase"
        // (tiré côté client une fois le paiement confirmé — voir payment/verify).
        $order = \App\Models\Order::where('external_reference', $externalRef)->first();

        return view('payment.verify', [
            'ref'              => $externalRef,
            'purchaseValue'    => $order ? (int) round($order->total_amount) : null,
            'purchaseCurrency' => 'XAF',
            'purchaseOrderId'  => $order?->id,
        ]);
    }

    /**
     * Finaliser le paiement : verifier statut + appeler checkout API + sauvegarder cartes
     * Aussi utilise comme API endpoint pour le mobile
     */
    public function finalize(Request $request)
    {
        $externalRef = $request->input('ref') ?? $request->input('external_reference');

        if (!$externalRef) {
            return response()->json(['success' => false, 'message' => 'Reference manquante.'], 400);
        }

        // SÉCURITÉ (C0) : la référence est réutilisée dans des requêtes SQL et
        // identifie une commande. On impose un format strict — aucun métacaractère
        // LIKE (% _) possible. Format réel des refs : KARD_<timestamp>_<rand>.
        if (!preg_match('/^[A-Za-z0-9_\-]{1,64}$/', (string) $externalRef)) {
            return response()->json(['success' => false, 'message' => 'Référence invalide.'], 422);
        }

        $userId = Auth::id();

        // Prevent duplicate orders — CORRESPONDANCE EXACTE + SCOPE PROPRIÉTAIRE.
        // Avant : `LIKE "%Ref: $ref%"` sans contrôle de propriété permettait, avec
        // ref="%", de récupérer les cartes et PIN d'AUTRES clients. On ne retourne
        // désormais que les commandes de l'utilisateur authentifié.
        $existingOrder = Order::where('user_id', $userId)
            ->where('notes', 'Ref: ' . $externalRef)
            ->first();
        if ($existingOrder) {
            $existingOrder->load('orderItems');
            $userCards = UserCard::where('order_id', $existingOrder->id)->get();
            return response()->json([
                'success' => true,
                'redirect_url' => route('orders.show', $existingOrder),
                'order' => $existingOrder,
                'cards' => $userCards->makeVisible(['card_code', 'pin']),
            ]);
        }

        try {
            // 1. Verify Payment Status
            $responseCheck = Http::timeout(10)->get(config('services.payment_backend.check_url'), [
                'external_reference' => $externalRef
            ]);

            $status = 'pending';
            if ($responseCheck->successful()) {
                $data = $responseCheck->json();
                $status = $data['status'] ?? 'pending';
            }

            if ($status !== 'completed' && $status !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Le paiement n\'a pas ete confirme (Statut: ' . $status . '). Veuillez reessayer.'
                ]);
            }

            // 2. Get cart items (support both user_id and token-based auth)
            $userId = Auth::id();
            $cartItems = ShoppingCart::where('user_id', $userId)->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Panier deja traite',
                    'redirect_url' => route('orders.index')
                ]);
            }

            // C1 (Palier 3) — le montant à facturer est recalculé côté serveur
            // depuis le catalogue, jamais le prix stocké seul. Défense en profondeur
            // en plus du palier panier (couvre aussi les items ajoutés avant ou
            // toute tentative de manipulation directe).
            $priceSvc = app(\App\Services\ProductApiService::class);
            foreach ($cartItems as $item) {
                $item->auth_unit_price = $priceSvc->authoritativeUnitPrice($item->product_id, $item->price);
            }
            $subtotal = $cartItems->sum(fn($item) => $item->auth_unit_price * $item->quantity);

            // C3 (Palier 4) — le montant LIVRÉ ne doit pas dépasser le montant
            // PAYÉ. La facture E-Billing a été créée à l'init avec un montant
            // verrouillé ; on le retrouve par la référence. Si le total recalculé
            // du panier le dépasse (panier gonflé APRÈS paiement), on refuse la
            // livraison plutôt que de livrer plus que ce qui a été encaissé.
            $invoiced = Payment::where('transaction_id', $externalRef)->value('amount')
                ?? Order::where('external_reference', $externalRef)->value('total_amount');
            if ($invoiced !== null && (int) round($subtotal) > (int) round((float) $invoiced)) {
                Log::warning('C3 total supérieur au montant payé — livraison refusée', [
                    'ref'        => $externalRef,
                    'recomputed' => $subtotal,
                    'invoiced'   => $invoiced,
                    'user_id'    => $userId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Le panier a changé depuis le paiement. Contacte le support avec ta référence de commande.',
                ], 422);
            }

            // 3. DB Transaction for atomicity
            $result = DB::transaction(function () use ($cartItems, $subtotal, $externalRef, $userId) {
                // Create Order
                $order = Order::create([
                    'user_id' => $userId,
                    'order_number' => Order::generateOrderNumber(),
                    'status' => Order::STATUS_PROCESSING,
                    'payment_status' => Order::PAYMENT_STATUS_COMPLETED,
                    'subtotal' => $subtotal,
                    'tax_amount' => 0,
                    'total_amount' => $subtotal,
                    'currency' => 'XAF',
                    'payment_method' => 'ebilling',
                    'notes' => 'Ref: ' . $externalRef,
                ]);

                // Create Payment record
                Payment::create([
                    'transaction_id' => $externalRef,
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'payment_method' => 'ebilling',
                    'provider' => 'E-Billing',
                    'amount' => $subtotal,
                    'currency' => 'XAF',
                    'status' => Payment::STATUS_COMPLETED,
                    'external_transaction_id' => $externalRef,
                    'processed_at' => now(),
                ]);

                // Create Order Items — on résout la valeur NATIVE (10 EUR pour
                // une Roblox FR 10€) maintenant pour que ProcessCheckoutJob/retry
                // puissent appeler afrikard avec la bonne valeur. Sans ça, le job
                // envoyait le prix XAF (6560) qu'afrikard rejette → toutes les
                // commandes nécessitaient un retry manuel pour être livrées.
                $productService = app(\App\Services\ProductApiService::class);
                $orderItems = [];
                foreach ($cartItems as $item) {
                    // Skip lookupNativeValue pour items marchand (Carte Gabon)
                    $isMerchant = str_starts_with((string) $item->product_id, 'merchant_');
                    $native = $isMerchant ? null : $productService->resolveNativeValue($item->product_id);
                    $orderItem = OrderItem::create([
                        'order_id'        => $order->id,
                        'product_id'      => $item->product_id,
                        'card_id'         => null,
                        'name'            => $item->name,
                        'unit_price'      => $item->auth_unit_price,
                        'total_price'     => $item->auth_unit_price * $item->quantity,
                        'quantity'        => $item->quantity,
                        'image_url'       => $item->image_url,
                        'native_value'    => $native['value']    ?? null,
                        'native_currency' => $native['currency'] ?? null,
                    ]);
                    $orderItems[] = $orderItem;
                }

                // 4. Clear Cart
                ShoppingCart::where('user_id', $userId)->delete();

                // 5a. Items marchand (Carte Gabon) : génération LOCALE en synchrone
                //     pour ne PAS dépendre du queue worker (QUEUE_CONNECTION=database
                //     sans worker sur shared hosting). Le code 8 chiffres + QR signé
                //     sont créés immédiatement après le paiement.
                $orderFresh = $order->fresh()->load('orderItems');
                $merchantItems = $orderFresh->orderItems->filter(
                    fn ($i) => \App\Support\MerchantCardCode::isMerchantOrderItem($i)
                );
                foreach ($merchantItems as $item) {
                    try {
                        \App\Support\MerchantCardCode::createPurchaseForOrderItem($orderFresh, $item);
                    } catch (\Throwable $e) {
                        Log::error('PaymentController finalize: échec MerchantCardPurchase', [
                            'order_id'      => $order->id,
                            'order_item_id' => $item->id,
                            'product_id'    => $item->product_id,
                            'error'         => $e->getMessage(),
                        ]);
                    }
                }

                // 5b. Items afrikard : dispatch du job async (= comportement historique)
                $afrikardItems = $orderFresh->orderItems->reject(
                    fn ($i) => \App\Support\MerchantCardCode::isMerchantOrderItem($i)
                );
                if ($afrikardItems->isNotEmpty()) {
                    ProcessCheckoutJob::dispatch($order);
                    Log::info('Checkout job dispatched (afrikard items present)', ['order_id' => $order->id]);
                } else {
                    // Commande 100% marchand → on complète immédiatement
                    $order->update([
                        'status'       => Order::STATUS_COMPLETED,
                        'completed_at' => now(),
                    ]);
                    Log::info('Order 100% marchand complétée inline', ['order_id' => $order->id]);
                }

                return [
                    'order' => $order->fresh()->load('orderItems'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Commande creee avec succes. Vos cartes seront disponibles dans quelques instants.',
                'redirect_url' => route('orders.show', $result['order']),
                'order_id' => $result['order']->id,
                'order' => $result['order'],
                'cards' => [], // Cards will be delivered async via queue job
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Finalize Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la finalisation du paiement.'
            ], 500);
        }
    }

    /**
     * Sauvegarder les cartes recues de l'API checkout dans user_cards
     */
    private function saveCheckoutCards(array $checkoutData, Order $order, array $orderItems, $cartItems)
    {
        $items = $checkoutData['items'] ?? [];
        $savedCards = collect();

        foreach ($items as $item) {
            $productId = $item['productId'] ?? null;
            $cards = $item['cards'] ?? [];

            // Find matching order item and cart item
            $matchingOrderItem = collect($orderItems)->firstWhere('product_id', (string) $productId);
            $matchingCartItem = $cartItems->firstWhere('product_id', (string) $productId);

            foreach ($cards as $card) {
                $userCard = UserCard::create([
                    'user_id' => Auth::id(),
                    'order_id' => $order->id,
                    'order_item_id' => $matchingOrderItem?->id,
                    'product_id' => (string) $productId,
                    'checkout_card_id' => $card['id'] ?? null,
                    'name' => $matchingCartItem?->name ?? 'Carte cadeau',
                    'brand' => $matchingCartItem?->name ? explode(' ', $matchingCartItem->name)[0] : null,
                    'serial_number' => $card['serialNumber'] ?? null,
                    'card_code' => $card['cardCode'] ?? '',
                    'pin' => $card['pin'] ?? null,
                    'expiration_date' => !empty($card['expirationDate']) ? $card['expirationDate'] : null,
                    'status' => ($card['status'] ?? 'active') === 'active' ? 'active' : 'used',
                    'face_value' => $item['productFaceValue'] ?? $matchingCartItem?->price ?? 0,
                    'currency' => $checkoutData['currency'] ?? 'XAF',
                    'image_url' => $matchingCartItem?->image_url ?? null,
                    'metadata' => [
                        'checkout_order_id' => $checkoutData['orderId'] ?? null,
                        'checkout_status' => $checkoutData['status'] ?? null,
                        'original_card_data' => $card,
                    ],
                ]);
                $savedCards->push($userCard);
            }
        }

        Log::info('Saved user cards from checkout', [
            'user_id' => Auth::id(),
            'order_id' => $order->id,
            'total_cards' => $savedCards->count(),
        ]);

        return $savedCards;
    }

    /**
     * Webhook Callback (Server to Server)
     */
    public function handleCallback(Request $request)
    {
        // SÉCURITÉ (C4) : endpoint public NON AUTHENTIFIÉ. Il est volontairement
        // INERTE — il ne modifie AUCUN état (statut de paiement, commande, carte).
        // ⚠️ NE JAMAIS lui faire confiance sans vérifier la signature HMAC du
        // fournisseur au préalable (avec idempotence sur external_reference).
        // La source de vérité du paiement reste la vérification serveur active
        // (check_status) dans finalize(). On ne journalise pas le corps brut.
        Log::info('Payment Webhook reçu (inerte)', [
            'ip'  => $request->ip(),
            'ref' => $request->input('external_reference') ?? $request->input('ref'),
        ]);
        return response()->json(['success' => true]);
    }
}
