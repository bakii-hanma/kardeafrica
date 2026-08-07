<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ResellerCard;
use App\Models\ResellerOrder;
use App\Models\ResellerOrderItem;
use App\Services\PaymentRefundService;
use App\Services\ProductApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    /**
     * Catalogue des produits disponibles à vendre — filtres & pagination boutique-like.
     */
    public function shop(Request $request, ProductApiService $service)
    {
        $reseller = Auth::guard('vendor')->user();

        $page    = (int) $request->get('page', 1);
        $perPage = 24;

        $allowedSorts = ['popular', 'price_asc', 'price_desc', 'newest'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'popular';

        $filters = [
            'search'      => $request->get('search'),
            'category'    => $request->get('category'),
            'price_range' => $request->get('price_range'),
            'country'     => $request->get('country'),
            'sort'        => $sort,
        ];

        $result = $service->getFilteredProducts($filters, $page, $perPage);

        return view('vendor.sale.shop', [
            'reseller'          => $reseller,
            'products'          => $result['items'],
            'pagination'        => [
                'total'        => $result['total'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
                'per_page'     => $result['per_page'],
            ],
            'categories'        => $service->getCategories(),
            'search'            => $filters['search'],
            'categoryId'        => $filters['category'],
            'priceRange'        => $filters['price_range'] ?? [],
            'selectedCountries' => $filters['country'] ?? [],
            'sort'              => $sort,
        ]);
    }

    /**
     * Étape 1 : reçoit le panier depuis la page Vendre, le sauvegarde en session
     * et redirige vers la page Checkout pour le récap + choix paiement.
     */
    public function checkoutPage(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'cart' => 'required|string',
            ]);

            $cart = json_decode($request->cart, true);
            if (!is_array($cart) || empty($cart)) {
                return redirect()->route('vendor.sell')->with('error', 'Panier vide ou invalide.');
            }

            // Filtre les items valides
            $items = collect($cart)->filter(function ($i) {
                return !empty($i['product_id']) && !empty($i['name'])
                    && isset($i['price']) && (float) $i['price'] > 0
                    && isset($i['quantity']) && (int) $i['quantity'] > 0;
            })->values()->all();

            if (empty($items)) {
                return redirect()->route('vendor.sell')->with('error', 'Aucun article valide.');
            }

            session([
                'vendor.checkout.cart'  => $items,
                'vendor.checkout.name'  => $request->input('customer_name'),
                'vendor.checkout.phone' => $request->input('customer_phone'),
            ]);

            return redirect()->route('vendor.checkout');
        }

        // GET : affichage du checkout
        $cart = session('vendor.checkout.cart', []);
        if (empty($cart)) {
            return redirect()->route('vendor.sell')->with('error', 'Aucun article en attente de paiement.');
        }

        $reseller = Auth::guard('vendor')->user();

        $subtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['quantity']);
        $commission = round($subtotal * ((float) $reseller->commission_rate / 100), 2);

        return view('vendor.sale.checkout', [
            'reseller'      => $reseller,
            'cart'          => $cart,
            'subtotal'      => $subtotal,
            'commission'    => $commission,
            'customerName'  => session('vendor.checkout.name'),
            'customerPhone' => session('vendor.checkout.phone'),
        ]);
    }

    /**
     * Étape 2 : démarre une vente depuis la page checkout.
     * Crée ResellerOrder pending + appelle E-Billing portal.php.
     * Renvoie JSON { portal_url, external_reference } à charger en redirection client.
     * (Le wallet n'est PAS débité tant que le paiement n'est pas confirmé.)
     */
    public function sell(Request $request)
    {
        // Si la requête n'a pas d'items, on les lit depuis la session checkout
        if (!$request->has('items')) {
            $sessItems = session('vendor.checkout.cart', []);
            $request->merge([
                'items'          => $sessItems,
                'customer_name'  => $request->input('customer_name', session('vendor.checkout.name')),
                'customer_phone' => $request->input('customer_phone', session('vendor.checkout.phone')),
            ]);
        }

        $request->validate([
            'items'              => 'required|array|min:1|max:30',
            'items.*.product_id' => 'required|string|max:50',
            'items.*.name'       => 'required|string|max:255',
            'items.*.brand'      => 'nullable|string|max:120',
            'items.*.image_url'  => 'nullable|string|max:500',
            'items.*.color'           => 'nullable|string|max:7',
            'items.*.price'           => 'required|numeric|min:1',
            'items.*.native_value'    => 'nullable|numeric|min:0',
            'items.*.native_currency' => 'nullable|string|max:8',
            'items.*.quantity'        => 'required|integer|min:1|max:50',
            'customer_name'      => 'nullable|string|max:120',
            'customer_phone'     => 'nullable|string|max:20',
            'customer_email'     => 'nullable|email|max:120',
        ]);

        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();
        $items    = collect($request->items);
        $subtotal = $items->sum(fn($i) => $i['price'] * $i['quantity']);

        // Vérif solde wallet
        if ((float) $reseller->wallet_balance < $subtotal) {
            return response()->json([
                'success' => false,
                'message' => "Solde insuffisant. Tu as " . number_format($reseller->wallet_balance, 0, ',', ' ') . " FCFA, il faut " . number_format($subtotal, 0, ',', ' ') . " FCFA. Demande une recharge à ton gérant.",
            ], 422);
        }

        $commission  = round($subtotal * ((float) $reseller->commission_rate / 100), 2);
        $externalRef = 'KAV_' . time() . '_' . rand(1000, 9999);

        try {
            $order = DB::transaction(function () use ($reseller, $items, $subtotal, $commission, $request, $externalRef) {
                $order = ResellerOrder::create([
                    'reseller_id'        => $reseller->id,
                    'customer_name'      => $request->customer_name,
                    'customer_phone'     => $request->customer_phone,
                    'subtotal'           => $subtotal,
                    'commission_earned'  => $commission,
                    'total_amount'       => $subtotal,
                    'currency'           => 'XAF',
                    'status'             => ResellerOrder::STATUS_PENDING,
                    'payment_status'     => ResellerOrder::PAYMENT_PENDING,
                    'payment_method'     => 'ebilling',
                    'external_reference' => $externalRef,
                ]);

                foreach ($items as $item) {
                    ResellerOrderItem::create([
                        'reseller_order_id' => $order->id,
                        'product_id'        => $item['product_id'],
                        'name'              => $item['name'],
                        'brand'             => $item['brand'] ?? null,
                        'image_url'         => $item['image_url'] ?? null,
                        'color'             => $item['color'] ?? null,
                        'unit_price'        => $item['price'],
                        // Valeur NATIVE (EUR/USD/...) capturée depuis le catalogue
                        // au moment de l'ajout au panier — utilisée à la livraison
                        // afrikard sans avoir besoin de relookup le catalogue.
                        'native_value'      => $item['native_value']    ?? null,
                        'native_currency'   => $item['native_currency'] ?? null,
                        'quantity'          => $item['quantity'],
                        'total_price'       => $item['price'] * $item['quantity'],
                    ]);
                }

                return $order->fresh()->load('items');
            });

            // Init E-Billing portal
            // Le payeur saisit son numéro directement sur le portail E-Billing,
            // donc on n'exige pas de téléphone côté vendeur (placeholder envoyé).
            $payload = [
                'amount'            => (int) round($order->total_amount),
                'short_description' => "Vente {$order->order_number} (vendeur {$reseller->vendor_code})",
                'reference'         => $externalRef,
                'email'             => $request->customer_email ?: 'noreply@kardafrica.com',
                'msisdn'            => $this->formatPhoneNumber($request->customer_phone ?: '24174000000'),
                'name'              => $request->customer_name ?: 'Client KardAfrica',
                'callback_url'      => url('/vendor/payment/return?ref=' . $externalRef),
                'format'            => 'json',
            ];

            $response = Http::timeout(20)->acceptJson()->asForm()
                ->post(config('services.payment_backend.init_url'), $payload);

            Log::info('Vendor sell: portal.php response', [
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                $body = $response->json();
                $order->update(['status' => ResellerOrder::STATUS_FAILED, 'notes' => 'E-Billing init: ' . ($body['error'] ?? 'HTTP ' . $response->status())]);
                return response()->json([
                    'success' => false,
                    'message' => $body['error'] ?? 'Erreur lors de l\'initialisation du paiement E-Billing.',
                ], 502);
            }

            $body      = $response->json();
            $data      = $body['data'] ?? [];
            $portalUrl = $data['portal_url'] ?? null;
            $billId    = $data['bill_id'] ?? null;

            if (!$portalUrl) {
                $order->update(['status' => ResellerOrder::STATUS_FAILED, 'notes' => 'E-Billing: portal_url manquant']);
                return response()->json([
                    'success' => false,
                    'message' => 'Réponse E-Billing incomplète.',
                ], 502);
            }

            $order->update([
                'payment_status' => ResellerOrder::PAYMENT_PENDING,
                'notes'          => 'bill_id: ' . $billId,
            ]);

            // On garde la session pour permettre le retour panier en cas d'échec
            return response()->json([
                'success'            => true,
                'portal_url'         => $portalUrl,
                'bill_id'            => $billId,
                'external_reference' => $externalRef,
                'order_id'           => $order->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Vendor sale exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DEV ONLY : simule un paiement réussi et bypass E-Billing.
     * Crée la commande, débite le wallet, crédite la commission, livre les cartes.
     */
    public function simulate(Request $request)
    {
        // SÉCURITÉ (H3) : flag dédié, jamais actif en prod (défaut false).
        if (!config('app.payments_simulation_enabled')) {
            abort(404);
        }

        // Lit la session checkout si pas d'items dans la requête
        if (!$request->has('items')) {
            $sessItems = session('vendor.checkout.cart', []);
            $request->merge([
                'items'          => $sessItems,
                'customer_name'  => $request->input('customer_name', session('vendor.checkout.name')),
                'customer_phone' => $request->input('customer_phone', session('vendor.checkout.phone')),
            ]);
        }

        $request->validate([
            'items'              => 'required|array|min:1|max:30',
            'items.*.product_id'      => 'required|string|max:50',
            'items.*.name'            => 'required|string|max:255',
            'items.*.price'           => 'required|numeric|min:1',
            'items.*.native_value'    => 'nullable|numeric|min:0',
            'items.*.native_currency' => 'nullable|string|max:8',
            'items.*.quantity'        => 'required|integer|min:1|max:50',
            'customer_name'      => 'nullable|string|max:120',
            'customer_phone'     => 'nullable|string|max:20',
        ]);

        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();
        $items    = collect($request->items);
        $subtotal = $items->sum(fn($i) => $i['price'] * $i['quantity']);

        if ((float) $reseller->wallet_balance < $subtotal) {
            return response()->json([
                'success' => false,
                'message' => "Solde insuffisant pour simuler. Solde : " . number_format($reseller->wallet_balance, 0, ',', ' ') . " FCFA, requis : " . number_format($subtotal, 0, ',', ' ') . " FCFA.",
            ], 422);
        }

        $commission  = round($subtotal * ((float) $reseller->commission_rate / 100), 2);
        $externalRef = 'SIM_' . time() . '_' . rand(1000, 9999);

        try {
            $order = DB::transaction(function () use ($reseller, $items, $subtotal, $commission, $request, $externalRef) {
                $order = ResellerOrder::create([
                    'reseller_id'        => $reseller->id,
                    'customer_name'      => $request->customer_name,
                    'customer_phone'     => $request->customer_phone,
                    'subtotal'           => $subtotal,
                    'commission_earned'  => $commission,
                    'total_amount'       => $subtotal,
                    'currency'           => 'XAF',
                    'status'             => ResellerOrder::STATUS_PROCESSING,
                    'payment_status'     => ResellerOrder::PAYMENT_COMPLETED,
                    'payment_method'     => 'simulated',
                    'external_reference' => $externalRef,
                    'notes'              => 'Paiement simulé (DEV)',
                ]);

                foreach ($items as $item) {
                    ResellerOrderItem::create([
                        'reseller_order_id' => $order->id,
                        'product_id'        => $item['product_id'],
                        'name'              => $item['name'],
                        'brand'             => $item['brand'] ?? null,
                        'image_url'         => $item['image_url'] ?? null,
                        'color'             => $item['color'] ?? null,
                        'unit_price'        => $item['price'],
                        // Valeur NATIVE (EUR/USD/...) capturée depuis le catalogue
                        // au moment de l'ajout au panier — utilisée à la livraison
                        // afrikard sans avoir besoin de relookup le catalogue.
                        'native_value'      => $item['native_value']    ?? null,
                        'native_currency'   => $item['native_currency'] ?? null,
                        'quantity'          => $item['quantity'],
                        'total_price'       => $item['price'] * $item['quantity'],
                    ]);
                }

                $reseller->debit($subtotal, "Vente simulée #{$order->order_number}", $order->order_number);
                $reseller->commission($commission, "Commission simulée #{$order->order_number}", $order->order_number);
                $reseller->increment('total_volume', $subtotal);

                return $order->fresh()->load('items');
            });

            $this->tryDeliver($order);

            // Vide la session checkout
            session()->forget(['vendor.checkout.cart', 'vendor.checkout.name', 'vendor.checkout.phone']);

            return response()->json([
                'success'      => true,
                'message'      => 'Paiement simulé avec succès — cartes générées.',
                'redirect_url' => route('vendor.orders.show', $order),
                'order_id'     => $order->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Vendor sale simulate exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur simulation : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Encaissement cash physique — création de la commande pending + verrou wallet.
     *
     * Le vendeur reçoit l'argent en main, crée la commande qui réserve le montant
     * sur son portefeuille de vente (wallet_locked). La livraison ne se fait qu'à
     * la confirmation explicite ensuite (cashConfirm). Si le vendeur abandonne
     * (cashCancel) ou laisse expirer (30 min via cash:expire-orders), le verrou
     * est libéré sans toucher au solde.
     *
     * Sécurité reposant sur le même pattern que les commandes cash boutique :
     * - lockFunds() vérifie le solde dispo de manière atomique (race-safe)
     * - le débit final passe par debitLocked() qui décrémente wallet ET locked
     */
    public function payCash(Request $request)
    {
        // Lit la session checkout si pas d'items dans la requête
        if (!$request->has('items')) {
            $sessItems = session('vendor.checkout.cart', []);
            $request->merge([
                'items'          => $sessItems,
                'customer_name'  => $request->input('customer_name', session('vendor.checkout.name')),
                'customer_phone' => $request->input('customer_phone', session('vendor.checkout.phone')),
            ]);
        }

        $request->validate([
            'items'              => 'required|array|min:1|max:30',
            'items.*.product_id'      => 'required|string|max:50',
            'items.*.name'            => 'required|string|max:255',
            'items.*.price'           => 'required|numeric|min:1',
            'items.*.native_value'    => 'nullable|numeric|min:0',
            'items.*.native_currency' => 'nullable|string|max:8',
            'items.*.quantity'        => 'required|integer|min:1|max:50',
            'customer_name'      => 'nullable|string|max:120',
            'customer_phone'     => 'nullable|string|max:20',
        ]);

        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();
        $items    = collect($request->items);
        $subtotal = (float) $items->sum(fn($i) => $i['price'] * $i['quantity']);

        if ((float) $reseller->available_balance < $subtotal) {
            return response()->json([
                'success' => false,
                'message' => "Solde dispo insuffisant. Dispo : " . number_format($reseller->available_balance, 0, ',', ' ') . " FCFA, requis : " . number_format($subtotal, 0, ',', ' ') . " FCFA.",
            ], 422);
        }

        // Garde-fou : bloque les nouvelles ventes cash si le vendeur a déjà
        // accumulé plus de cash physique (à reverser) que son plafond wallet.
        // Le vendeur DOIT remettre du cash via E-Billing avant de continuer.
        $projectedCashToRemit = (float) $reseller->cash_to_remit + $subtotal;
        if ($projectedCashToRemit > (float) $reseller->max_wallet) {
            return response()->json([
                'success' => false,
                'message' => "Cash à remettre déjà élevé : " . number_format($reseller->cash_to_remit, 0, ',', ' ') . " FCFA. Reverse via E-Billing avant de faire une nouvelle vente cash.",
                'redirect_url' => route('vendor.remittance.index'),
            ], 422);
        }

        $commission  = round($subtotal * ((float) $reseller->commission_rate / 100), 2);
        $externalRef = 'CASH_' . time() . '_' . rand(1000, 9999);

        try {
            $order = DB::transaction(function () use ($reseller, $items, $subtotal, $commission, $request, $externalRef) {
                // 1. Verrou wallet (lockForUpdate atomique côté Reseller)
                $reseller->lockFunds($subtotal, $externalRef);

                // 2. Création de la commande pending
                $order = ResellerOrder::create([
                    'reseller_id'        => $reseller->id,
                    'customer_name'      => $request->customer_name,
                    'customer_phone'     => $request->customer_phone,
                    'subtotal'           => $subtotal,
                    'commission_earned'  => $commission,
                    'total_amount'       => $subtotal,
                    'currency'           => 'XAF',
                    'status'             => ResellerOrder::STATUS_PENDING,
                    'payment_status'     => ResellerOrder::PAYMENT_PENDING,
                    'payment_method'     => 'cash',
                    'external_reference' => $externalRef,
                    'expires_at'         => now()->addMinutes(30),
                    'notes'              => 'En attente de confirmation cash par le vendeur',
                ]);

                foreach ($items as $item) {
                    ResellerOrderItem::create([
                        'reseller_order_id' => $order->id,
                        'product_id'        => $item['product_id'],
                        'name'              => $item['name'],
                        'brand'             => $item['brand'] ?? null,
                        'image_url'         => $item['image_url'] ?? null,
                        'color'             => $item['color'] ?? null,
                        'unit_price'        => $item['price'],
                        // Valeur NATIVE (EUR/USD/...) capturée depuis le catalogue
                        // au moment de l'ajout au panier — utilisée à la livraison
                        // afrikard sans avoir besoin de relookup le catalogue.
                        'native_value'      => $item['native_value']    ?? null,
                        'native_currency'   => $item['native_currency'] ?? null,
                        'quantity'          => $item['quantity'],
                        'total_price'       => $item['price'] * $item['quantity'],
                    ]);
                }

                return $order->fresh()->load('items');
            });

            return response()->json([
                'success'      => true,
                'message'      => 'Vente cash créée — confirme l\'encaissement quand tu as l\'argent en main.',
                'redirect_url' => route('vendor.sell.cash.show', $order),
                'order_id'     => $order->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Vendor sale cash exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Page de confirmation d'encaissement cash — vendeur clique pour finaliser.
     */
    public function cashShow(ResellerOrder $order)
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();
        if ($order->reseller_id !== $reseller->id) abort(404);

        $order->load('items');

        return view('vendor.sale.cash-pending', [
            'order'    => $order,
            'reseller' => $reseller,
        ]);
    }

    /**
     * Confirmation de l'encaissement cash : débit verrou + commission + livraison.
     */
    public function cashConfirm(Request $request, ResellerOrder $order)
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();
        if ($order->reseller_id !== $reseller->id) abort(404);

        if ($order->payment_method !== 'cash') {
            return back()->with('error', 'Cette commande n\'est pas un encaissement cash.');
        }
        if ($order->payment_status !== ResellerOrder::PAYMENT_PENDING) {
            return back()->with('error', 'Cette commande n\'est plus en attente.');
        }
        if ($order->expires_at && $order->expires_at->isPast()) {
            return back()->with('error', 'Le délai de cette commande est expiré. Crée une nouvelle vente.');
        }

        try {
            DB::transaction(function () use ($reseller, $order) {
                $subtotal   = (float) $order->subtotal;
                $commission = (float) $order->commission_earned;

                // 1. Débit final : décrémente wallet ET locked en une transaction
                $reseller->debitLocked($subtotal, "Vente cash #{$order->order_number}", $order->order_number);
                // 2. Commission créditée sur le compte commission
                $reseller->commission($commission, "Commission cash #{$order->order_number}", $order->order_number);
                // 3. Cash physique reçu : à reverser à KardAfrica via E-Billing
                $reseller->recordCashCollection($subtotal, $order->order_number);

                $reseller->increment('total_volume', $subtotal);

                $order->update([
                    'status'         => ResellerOrder::STATUS_PROCESSING,
                    'payment_status' => ResellerOrder::PAYMENT_COMPLETED,
                    'notes'          => 'Encaissement cash confirmé par le vendeur',
                ]);
            });

            // Livraison cartes (afrikard ou Daywatch local)
            $this->tryDeliver($order->fresh());

            // Vide la session checkout
            session()->forget(['vendor.checkout.cart', 'vendor.checkout.name', 'vendor.checkout.phone']);

            return redirect()
                ->route('vendor.orders.show', $order)
                ->with('success', 'Encaissement confirmé — cartes générées et prêtes à remettre au client.');
        } catch (\Throwable $e) {
            Log::error('Vendor cash confirm exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Annulation d'une vente cash en attente : libère le verrou wallet.
     */
    public function cashCancel(Request $request, ResellerOrder $order)
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();
        if ($order->reseller_id !== $reseller->id) abort(404);

        if ($order->payment_status !== ResellerOrder::PAYMENT_PENDING) {
            return back()->with('error', 'Cette commande n\'est plus en attente.');
        }

        try {
            DB::transaction(function () use ($reseller, $order) {
                $reseller->releaseFunds((float) $order->subtotal, $order->order_number);
                $order->update([
                    'status'         => ResellerOrder::STATUS_CANCELLED,
                    'payment_status' => ResellerOrder::PAYMENT_FAILED,
                    'notes'          => 'Annulée par le vendeur — fonds libérés',
                ]);
            });

            return redirect()->route('vendor.sell')->with('success', 'Vente annulée — solde libéré.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Retour depuis le portal E-Billing — affiche la page de vérification (poll).
     */
    public function paymentReturn(Request $request)
    {
        $ref = $request->query('ref');
        if (!$ref) {
            return redirect()->route('vendor.sell')->with('error', 'Référence de paiement manquante.');
        }
        return view('vendor.payment.verify', ['ref' => $ref]);
    }

    /**
     * Vérifie le statut E-Billing puis, si paiement OK :
     * - débite le wallet
     * - crédite la commission
     * - appelle afrikard pour livrer les cartes
     * - renvoie le redirect_url vers vendor.orders.show
     */
    public function paymentFinalize(Request $request)
    {
        $ref = $request->input('ref') ?? $request->input('external_reference');
        if (!$ref) {
            return response()->json(['success' => false, 'message' => 'Référence manquante.'], 400);
        }

        $reseller = Auth::guard('vendor')->user();
        if (!$reseller) {
            return response()->json(['success' => false, 'message' => 'Session vendeur expirée.'], 401);
        }

        $order = ResellerOrder::where('external_reference', $ref)
            ->where('reseller_id', $reseller->id)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Commande introuvable.'], 404);
        }

        // Si déjà finalisé → redirige direct
        if ($order->payment_status === ResellerOrder::PAYMENT_COMPLETED) {
            return response()->json([
                'success'      => true,
                'redirect_url' => route('vendor.orders.show', $order),
                'message'      => 'Paiement déjà confirmé.',
            ]);
        }

        try {
            // 1. Vérifier statut E-Billing
            $check = Http::timeout(10)->get(config('services.payment_backend.check_url'), [
                'external_reference' => $ref,
            ]);

            $status = 'pending';
            if ($check->successful()) {
                $body = $check->json();
                $status = $body['status'] ?? ($body['data']['status'] ?? 'pending');
            }

            $isPaid = in_array($status, ['completed', 'success'], true);

            if (!$isPaid) {
                return response()->json([
                    'success' => false,
                    'message' => "Paiement non confirmé (statut : {$status}). Réessaie dans quelques secondes.",
                    'status'  => $status,
                ]);
            }

            // 2. Re-vérifie le solde au cas où
            if ((float) $reseller->wallet_balance < (float) $order->subtotal) {
                $order->update(['status' => ResellerOrder::STATUS_FAILED, 'notes' => 'Solde wallet insuffisant au moment de la finalisation']);
                return response()->json([
                    'success' => false,
                    'message' => 'Solde wallet insuffisant. Demande une recharge à ton gérant.',
                ], 422);
            }

            // 3. Débit + commission + état (atomic)
            DB::transaction(function () use ($reseller, $order) {
                $reseller->debit((float) $order->subtotal, "Vente #{$order->order_number}", $order->order_number);
                $reseller->commission((float) $order->commission_earned, "Commission vente #{$order->order_number}", $order->order_number);
                $reseller->increment('total_volume', $order->subtotal);
                $order->update([
                    'status'          => ResellerOrder::STATUS_PROCESSING,
                    'payment_status'  => ResellerOrder::PAYMENT_COMPLETED,
                ]);
            });

            // 4. Livraison via afrikard
            $order->load('items');
            $this->tryDeliver($order);

            // Vide la session checkout
            session()->forget(['vendor.checkout.cart', 'vendor.checkout.name', 'vendor.checkout.phone']);

            return response()->json([
                'success'      => true,
                'message'      => 'Paiement validé — cartes en cours de livraison.',
                'redirect_url' => route('vendor.orders.show', $order),
                'order_id'     => $order->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Vendor payment finalize exception', [
                'ref'   => $ref,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la finalisation : ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatPhoneNumber(?string $phone): string
    {
        if (!$phone) return '24174000000';
        $cleaned = preg_replace('/\D/', '', $phone);
        if (str_starts_with($cleaned, '00')) $cleaned = substr($cleaned, 2);
        if (str_starts_with($cleaned, '241')) return $cleaned;
        if (str_starts_with($cleaned, '0'))   return '241' . substr($cleaned, 1);
        return '241' . $cleaned;
    }

    /**
     * Appelle afrikard /orders/checkout pour récupérer les codes des cartes.
     * Pour Daywatch (id `daywatch_X`), on génère les cartes localement (pas d'API).
     * Pour les autres : on utilise la face value native de l'API (USD, EUR, AED…),
     * pas le prix FCFA stocké en base.
     */
    public function tryDeliver(ResellerOrder $order): void
    {
        // Sépare Daywatch (locaux) des produits afrikard (distants)
        $daywatchItems = $order->items->filter(fn($i) => str_starts_with((string) $i->product_id, 'daywatch_'));
        $apiItems      = $order->items->filter(fn($i) => !str_starts_with((string) $i->product_id, 'daywatch_'));

        // 1. Cartes Daywatch (génération locale)
        foreach ($daywatchItems as $item) {
            for ($i = 0; $i < (int) $item->quantity; $i++) {
                ResellerCard::create([
                    'reseller_order_id'      => $order->id,
                    'reseller_order_item_id' => $item->id,
                    'product_id'             => (string) $item->product_id,
                    'name'                   => $item->name,
                    'brand'                  => $item->brand ?? 'Daywatch',
                    'card_code'              => 'DW-' . strtoupper(\Illuminate\Support\Str::random(10)),
                    'pin'                    => str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
                    'face_value'             => $item->unit_price,
                    'currency'               => 'XAF',
                    'status'                 => ResellerCard::STATUS_ACTIVE,
                    'image_url'              => $item->image_url,
                    'metadata'               => ['source' => 'daywatch_local'],
                ]);
            }
        }

        // 2. Cartes afrikard
        if ($apiItems->isEmpty()) {
            $order->update([
                'status'       => ResellerOrder::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            return;
        }

        // Look up des face values natives (afrikard exige EUR/USD/... pas FCFA)
        try {
            $service = app(ProductApiService::class);
            $allProducts = collect($service->getAllProducts(0, 99999))->keyBy('id');
        } catch (\Throwable $e) {
            $allProducts = collect();
            Log::warning('Vendor delivery: lookup catalogue échoué', ['error' => $e->getMessage()]);
        }

        $missingProducts = [];
        $payload = $apiItems->map(function ($item) use ($allProducts, $order, $service, &$missingProducts) {
            $productId = (int) $item->product_id;

            // 1. PRIORITÉ : valeur native stockée à la création de la commande
            //    (capturée depuis le catalogue au moment de l'ajout au panier).
            //    Plus besoin de réinterroger afrikard ni le cache.
            if ($item->native_value && (float) $item->native_value > 0) {
                return [
                    'ProductId' => $productId,
                    'Quantity'  => (int) $item->quantity,
                    'Value'     => (int) round((float) $item->native_value),
                ];
            }

            // 2. Fallback (anciennes commandes pré-migration) : cache catalogue
            $product = $allProducts->get($productId) ?? $allProducts->get((string) $productId);

            // 3. Fallback ultime : appel API ciblé
            if (!$product) {
                Log::info('Vendor delivery: cache miss, fallback API productId', [
                    'order_id' => $order->id, 'product_id' => $productId,
                ]);
                $product = $service->getProductByIdLight($productId);
            }

            if (!$product) {
                $missingProducts[] = $productId;
                return null;
            }

            $value = (int) round($product['minFaceValue'] ?? $product['price']['min'] ?? 0);
            if ($value <= 0) {
                $missingProducts[] = $productId;
                return null;
            }

            return [
                'ProductId' => $productId,
                'Quantity'  => (int) $item->quantity,
                'Value'     => $value,
            ];
        })->filter()->values()->toArray();

        // Si un seul produit n'a pas pu être résolu, on n'envoie RIEN à afrikard
        // (sinon HTTP 500 garanti car valeurs incohérentes). Le vendeur peut
        // retenter ou rembourser.
        if (!empty($missingProducts)) {
            Log::warning('Vendor delivery: aborted, products with unknown native value', [
                'order_id'         => $order->id,
                'missing_products' => $missingProducts,
            ]);
            $order->update([
                'notes' => 'Le catalogue fournisseur est temporairement incomplet. Réessaie dans quelques minutes ou rembourse le client.',
            ]);
            return;
        }

        if (empty($payload)) {
            $order->update(['notes' => 'Aucun produit livrable. Rembourse le client.']);
            return;
        }

        Log::info('Vendor delivery: appel afrikard', [
            'order_id' => $order->id,
            'payload'  => $payload,
        ]);

        try {
            $res = Http::timeout(30)
                ->post(config('services.product_api.base_url') . '/orders/checkout', $payload);

            if ($res->status() === 202 || $res->successful()) {
                $this->saveCards($order, $res->json());
                $order->update([
                    'status'       => ResellerOrder::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'notes'        => null,
                ]);
            } else {
                $body = $res->body();
                // Détails techniques UNIQUEMENT dans les logs (pour debug)
                Log::warning('Vendor delivery: afrikard HTTP error', [
                    'order_id' => $order->id,
                    'status'   => $res->status(),
                    'body'     => $body,
                ]);
                // Message user-friendly différencié selon le code HTTP
                $userMsg = match (true) {
                    $res->status() >= 500 => 'Le fournisseur de cartes a un problème temporaire. Réessaie dans quelques minutes ou rembourse le client.',
                    $res->status() === 422 || $res->status() === 400 => 'Le fournisseur a refusé la commande (produit ou montant invalide). Rembourse le client.',
                    default => 'Le fournisseur n\'a pas pu générer les cartes. Réessaie ou rembourse le client.',
                };
                $order->update(['notes' => $userMsg]);
            }
        } catch (\Throwable $e) {
            // Détails techniques UNIQUEMENT dans les logs
            Log::warning('Vendor delivery: exception', ['order' => $order->id, 'error' => $e->getMessage()]);
            // Distingue timeout (afrikard down) du reste
            $isTimeout = str_contains($e->getMessage(), 'timed out')
                      || str_contains($e->getMessage(), 'Timeout')
                      || str_contains($e->getMessage(), 'cURL error 28');
            $order->update([
                'notes' => $isTimeout
                    ? 'Le serveur du fournisseur ne répond pas. Réessaie dans quelques minutes.'
                    : 'Connexion au fournisseur indisponible. Réessaie ou rembourse le client.',
            ]);
        }
    }

    /**
     * Re-tente la livraison des cartes (utile si afrikard a échoué).
     */
    public function retryDelivery(ResellerOrder $order)
    {
        if ($order->reseller_id !== Auth::guard('vendor')->id()) abort(403);
        if ($order->payment_status !== ResellerOrder::PAYMENT_COMPLETED) {
            return back()->with('error', 'Cette commande n\'est pas payée.');
        }
        if ($order->cards()->count() > 0) {
            return back()->with('error', 'Les cartes ont déjà été livrées.');
        }

        $order->load('items');
        $this->tryDeliver($order);
        $order->refresh();

        if ($order->cards()->count() > 0) {
            return back()->with('success', 'Livraison réussie — ' . $order->cards()->count() . ' carte(s) générée(s).');
        }
        // Pas de fuite technique : message générique. Le détail est dans les logs.
        return back()->with('error', 'Le fournisseur n\'a pas pu livrer les cartes. Tu peux réessayer ou rembourser le client.');
    }

    /**
     * Remboursement d'une vente vendeur.
     *
     * Selon le mode de paiement :
     * - 'ebilling'  → appel API transfer.php pour rembourser le payeur initial,
     *                 puis annulation de la commande + restauration wallet vendeur
     *                 (cards non livrées) + retrait commission
     * - 'cash'      → uniquement confirmation manuelle que le vendeur a rendu le
     *                 cash au client (pas d'appel API). Le wallet est restauré
     *                 et la commission retirée. cash_to_remit décrémenté.
     * - 'simulated' → annulation locale uniquement (DEV)
     */
    public function refund(Request $request, ResellerOrder $order, PaymentRefundService $refundSvc)
    {
        if ($order->reseller_id !== Auth::guard('vendor')->id()) abort(403);

        if ($order->payment_status !== ResellerOrder::PAYMENT_COMPLETED) {
            return back()->with('error', 'Cette commande n\'est pas payée — utilise « Annuler » plutôt.');
        }
        if ($order->cards()->count() > 0) {
            return back()->with('error', 'Les cartes ont déjà été livrées — impossible de rembourser automatiquement.');
        }

        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        // Pour le cash : on demande une confirmation explicite que le vendeur a
        // rendu l'argent au client (case à cocher passée en POST)
        if ($order->payment_method === 'cash' && !$request->boolean('cash_returned_to_client')) {
            return back()->with('error', 'Coche la case de confirmation : tu dois avoir rendu l\'argent au client.');
        }

        // E-Billing : on appelle l'API transfer
        if ($order->payment_method === 'ebilling') {
            $result = $refundSvc->refund(
                originalReference: $order->external_reference,
                amountFcfa: (int) round($order->total_amount),
                reason: "Remboursement vente {$order->order_number}",
                extras: [
                    'msisdn' => $order->customer_phone,
                    'name'   => $order->customer_name,
                ],
            );
            if (!$result['ok']) {
                return back()->with('error', 'Remboursement E-Billing refusé : ' . $result['message']);
            }
        }

        try {
            DB::transaction(function () use ($reseller, $order) {
                $subtotal   = (float) $order->subtotal;
                $commission = (float) $order->commission_earned;

                // 1. Restaure le wallet du vendeur (les cartes n'ont pas été livrées
                //    donc le float est restitué)
                $reseller->credit($subtotal, null, "Remboursement vente #{$order->order_number}", $order->order_number);

                // 2. Retire la commission précédemment créditée
                if ($commission > 0) {
                    $reseller->commission(-$commission, "Annulation commission vente #{$order->order_number}", $order->order_number);
                }

                // 3. Si vente cash, le vendeur a rendu l'argent au client →
                //    décrémente cash_to_remit (l'argent n'est plus à reverser)
                if ($order->payment_method === 'cash') {
                    $fresh = $reseller->refresh();
                    $newCash = max(0, (float) $fresh->cash_to_remit - $subtotal);
                    $fresh->cash_to_remit = $newCash;
                    $fresh->save();
                }

                $order->update([
                    'status'         => ResellerOrder::STATUS_REFUNDED,
                    'payment_status' => ResellerOrder::PAYMENT_REFUNDED,
                    'notes'          => 'Remboursée ' . ($order->payment_method === 'cash' ? '(cash rendu au client)' : '(E-Billing transfer)'),
                ]);
            });

            return back()->with('success', 'Commande remboursée — ton wallet a été restauré.');
        } catch (\Throwable $e) {
            Log::error('Vendor refund exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function saveCards(ResellerOrder $order, array $checkoutData): void
    {
        foreach ($checkoutData['items'] ?? [] as $apiItem) {
            $productId = $apiItem['productId'] ?? null;
            $cards     = $apiItem['cards'] ?? [];
            $orderItem = $order->items
                ->firstWhere('product_id', (string) $productId)
                ?? $order->items->firstWhere('product_id', $productId);

            foreach ($cards as $card) {
                ResellerCard::create([
                    'reseller_order_id'      => $order->id,
                    'reseller_order_item_id' => $orderItem?->id,
                    'product_id'             => (string) $productId,
                    'checkout_card_id'       => $card['id'] ?? null,
                    'name'                   => $orderItem?->name ?? 'Carte cadeau',
                    'brand'                  => $orderItem?->brand ?? null,
                    'serial_number'          => $card['serialNumber'] ?? null,
                    'card_code'              => $card['cardCode'] ?? '',
                    'pin'                    => $card['pin'] ?? null,
                    'expiration_date'        => !empty($card['expirationDate']) ? $card['expirationDate'] : null,
                    'face_value'             => $apiItem['productFaceValue'] ?? $orderItem?->unit_price ?? 0,
                    'currency'               => $checkoutData['currency'] ?? 'XAF',
                    'image_url'              => $orderItem?->image_url,
                    'metadata'               => ['source' => 'vendor_sale'],
                ]);
            }
        }
    }

    /**
     * Détail d'une commande vendeur (avec QR code à montrer au client).
     */
    public function showOrder(ResellerOrder $order)
    {
        if ($order->reseller_id !== Auth::guard('vendor')->id()) abort(403);
        $order->load(['items', 'cards']);
        return view('vendor.sale.order', compact('order'));
    }

    /**
     * Liste des commandes du vendeur — avec filtres recherche / statut + stats.
     */
    public function orders(Request $request)
    {
        $reseller = Auth::guard('vendor')->user();

        $query = $reseller->orders()->with('items');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('customer_phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['pending', 'processing', 'completed', 'cancelled', 'failed'], true)) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        // Stats globales (sur toutes les commandes du vendeur, indépendantes des filtres)
        $stats = [
            'total'      => $reseller->orders()->count(),
            'completed'  => $reseller->orders()->where('status', 'completed')->count(),
            'pending'    => $reseller->orders()->where('status', ResellerOrder::STATUS_PROCESSING)->count(),
            'volume'     => (float) $reseller->orders()->where('status', 'completed')->sum('total_amount'),
            'commission' => (float) $reseller->orders()->sum('commission_earned'),
        ];

        return view('vendor.sale.orders', compact('orders', 'stats'));
    }
}
