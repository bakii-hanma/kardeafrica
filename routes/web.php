<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\CardController as AdminCardController;
use App\Http\Controllers\Admin\CatalogController as AdminCatalogController;
use App\Http\Controllers\Admin\DaywatchController as AdminDaywatchController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\ResellerController as AdminResellerController;
use App\Http\Controllers\Admin\MerchantCardController as AdminMerchantCardController;
use App\Http\Controllers\Admin\VendorOrderController as AdminVendorOrderController;
use App\Http\Controllers\Vendor\AuthController       as VendorAuthController;
use App\Http\Controllers\Vendor\DashboardController  as VendorDashboardController;
use App\Http\Controllers\Vendor\ProfileController    as VendorProfileController;
use App\Http\Controllers\Vendor\SaleController       as VendorSaleController;
use App\Http\Controllers\Vendor\CashOrderController  as VendorCashOrderController;
use App\Http\Controllers\Vendor\RemittanceController as VendorRemittanceController;
use App\Http\Controllers\Vendor\WalletRechargeController as VendorWalletRechargeController;
use App\Http\Controllers\GabonController;
use App\Http\Controllers\ClaimController;

// Route d'accueil avec les produits populaires
Route::get('/', [ProductController::class, 'home'])->name('home');

// Route pour le profil
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Vue dédiée à l'assistante IA Kara (alternative au widget flottant — plein écran)
    Route::get('/profile/assistant', function () {
        return view('profile.assistant');
    })->name('profile.assistant');
});

// Route pour le panier
Route::get('/cart', function () {
    return view('cart.index');
})->name('cart.index');

// Route pour le checkout (page) + start (POST qui cree l'order et lance futursowax)
Route::get('/checkout', [App\Http\Controllers\CheckoutController::class, 'index'])->middleware('auth')->name('checkout.index');
Route::post('/checkout/start', [App\Http\Controllers\CheckoutController::class, 'start'])->middleware(['auth', 'throttle:10,1'])->name('checkout.start');
// Dev only : simulate payment without going through E-Billing (active si APP_DEBUG=true)
Route::post('/checkout/simulate', [App\Http\Controllers\CheckoutController::class, 'simulate'])->middleware(['auth', 'throttle:10,1'])->name('checkout.simulate');
// Paiement physique chez un vendeur Kardafrica
Route::get('/checkout/reseller-lookup', [App\Http\Controllers\CheckoutController::class, 'lookupReseller'])->middleware(['auth', 'throttle:30,1'])->name('checkout.reseller.lookup');
Route::post('/checkout/cash', [App\Http\Controllers\CheckoutController::class, 'payCash'])->middleware(['auth', 'throttle:10,1'])->name('checkout.cash');
Route::get('/checkout/cash/{order}', [App\Http\Controllers\CheckoutController::class, 'showCashOrder'])->middleware('auth')->name('checkout.cash.show');

// Pages web des commandes (auth session)
Route::middleware('auth')->group(function () {
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/retry-checkout', [App\Http\Controllers\OrderController::class, 'retryCheckout'])
        ->middleware('throttle:5,1')
        ->name('orders.retry-checkout');
    Route::post('/orders/{order}/refund', [App\Http\Controllers\OrderController::class, 'refund'])
        ->middleware('throttle:5,1')
        ->name('orders.refund');
});

// Webhook callback E-Billing (cote web pour conserver le nom de route 'payment.callback')
Route::any('/payment/callback', [App\Http\Controllers\PaymentController::class, 'handleCallback'])->name('payment.callback');

// ================================================================
// MARKETPLACE PUBLIC : cartes-cadeau locales (catalogue admin global)
// ================================================================
Route::prefix('gabon')->group(function () {
    Route::get('/',                          [GabonController::class, 'index'])->name('gabon.index');
    Route::get('/categorie/{slug}',          [GabonController::class, 'category'])->name('gabon.category');
    Route::get('/carte/{merchantCard}',      [GabonController::class, 'card'])->name('gabon.card');
});

// ================================================================
// ESPACE PROPRIÉTAIRE DE CARTE LOCALE (/proprietaire)
// ================================================================
Route::prefix('proprietaire')->group(function () {
    // Auth (publique)
    Route::get('/login',  [\App\Http\Controllers\Owner\AuthController::class, 'showLoginForm'])->name('owner.login');
    Route::post('/login', [\App\Http\Controllers\Owner\AuthController::class, 'login'])->middleware('throttle:6,1')->name('owner.login.submit');
    Route::post('/logout',[\App\Http\Controllers\Owner\AuthController::class, 'logout'])->name('owner.logout');

    // Dashboard + tout le reste (protégé)
    Route::middleware('is_card_owner')->group(function () {
        Route::get('/',                   [\App\Http\Controllers\Owner\DashboardController::class, 'index'])->name('owner.dashboard');
        Route::get('/cartes',             [\App\Http\Controllers\Owner\DashboardController::class, 'cards'])->name('owner.cards');
        Route::get('/cartes/nouvelle',    [\App\Http\Controllers\Owner\CardController::class, 'create'])->name('owner.card.create');
        Route::post('/cartes',            [\App\Http\Controllers\Owner\CardController::class, 'store'])->name('owner.card.store');
        Route::get('/cartes/{merchantCard}', [\App\Http\Controllers\Owner\DashboardController::class, 'cardShow'])->name('owner.card.show');
        Route::get('/cartes/{merchantCard}/edit',   [\App\Http\Controllers\Owner\CardController::class, 'edit'])->name('owner.card.edit');
        Route::put('/cartes/{merchantCard}',         [\App\Http\Controllers\Owner\CardController::class, 'update'])->name('owner.card.update');
        Route::delete('/cartes/{merchantCard}',      [\App\Http\Controllers\Owner\CardController::class, 'destroy'])->name('owner.card.destroy');
        Route::get('/historique',         [\App\Http\Controllers\Owner\DashboardController::class, 'history'])->name('owner.history');

        Route::get('/scanner',            [\App\Http\Controllers\Owner\ScanController::class, 'index'])->name('owner.scan');
        Route::post('/scanner/lookup',    [\App\Http\Controllers\Owner\ScanController::class, 'lookup'])->name('owner.scan.lookup');
        Route::post('/scanner/redeem',    [\App\Http\Controllers\Owner\ScanController::class, 'redeem'])->name('owner.scan.redeem');
    });
});

// Routes pour les produits de l'API
Route::get('/boutique', [ProductController::class, 'boutique'])->name('boutique');
Route::get('/category/{categoryId}', [ProductController::class, 'category'])->name('category');
Route::get('/card-type/{cardTypeId}', [ProductController::class, 'cardType'])->name('card-type.show');
Route::get('/product/{productId}', [ProductController::class, 'show'])->name('product.show');
Route::get('/search', [ProductController::class, 'search'])->name('search');

// Page de téléchargement de l'app mobile Android (APK hébergé sous public/downloads)
Route::get('/telecharger', function () {
    return view('download', [
        'apkUrl'  => asset('downloads/kardafrica.apk'),
        'version' => '1.0.0',
    ]);
})->name('download');

// Pages légales
Route::view('/confidentialite',     'legal.privacy')->name('privacy');
Route::view('/suppression-donnees', 'legal.data-deletion')->name('data-deletion');

// Routes API pour les produits (web context)
Route::prefix('api')->group(function () {
    Route::get('/products', [ProductController::class, 'apiProducts'])->name('api.products');
    Route::get('/categories', [ProductController::class, 'apiCategories'])->name('api.categories');
});

// Routes pour les cartes numeriques (ancien systeme)
Route::resource('cards', CardController::class)->middleware('auth');
Route::get('/cards/search', [CardController::class, 'search'])->name('cards.search')->middleware('auth');
Route::get('/cards/type/{type}', [CardController::class, 'byType'])->name('cards.byType')->middleware('auth');
Route::patch('/cards/{card}/toggle-status', [CardController::class, 'toggleStatus'])->name('cards.toggleStatus')->middleware('auth');

// Routes pour les pages statiques
Route::get('/about', function () { return view('about'); })->name('about');
Route::get('/contact', function () { return view('contact'); })->name('contact');
Route::get('/support', function () { return view('support'); })->name('support');

// Routes pour l'authentification web
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [WebAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register']);

    // Réinitialisation de mot de passe (M15) — le contrôleur et les vues
    // existaient mais n'étaient routés nulle part → récupération impossible.
    Route::get('/forgot-password',        [\App\Http\Controllers\Auth\PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password',       [\App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password',        [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [WebAuthController::class, 'logout'])->middleware('auth')->name('logout');

// Verification du mot de passe (gate pour reveal des codes/PIN de cartes)
Route::post('/verify-password', [WebAuthController::class, 'verifyPassword'])
    ->middleware(['auth', 'throttle:5,1'])
    ->name('verify-password');

// Routes API web-context (session auth)
Route::prefix('api')->group(function () {
    Route::get('/check', [App\Http\Controllers\Auth\AuthController::class, 'check'])->name('api.check');

    // Routes du panier
    Route::get('/cart', [App\Http\Controllers\CartController::class, 'index'])->name('api.cart.index');
    Route::post('/cart/add', [App\Http\Controllers\CartController::class, 'add'])->name('api.cart.add');
    Route::put('/cart/{id}', [App\Http\Controllers\CartController::class, 'update'])->name('api.cart.update');
    Route::delete('/cart/{id}', [App\Http\Controllers\CartController::class, 'remove'])->name('api.cart.remove');
    Route::delete('/cart', [App\Http\Controllers\CartController::class, 'clear'])->name('api.cart.clear');

    // Note : routes /api/orders/* et /api/payment/* deplacees dans routes/api.php
    // (Sanctum) pour eviter les conflits avec le web auth session-based.
});

// ================================================================
// NEWSLETTER (public)
// ================================================================
Route::post('/newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe'])
    ->middleware('throttle:5,1')
    ->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [App\Http\Controllers\NewsletterController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe');

// Route de retour de paiement (Browser Redirect)
Route::get('/payment/return', [App\Http\Controllers\PaymentController::class, 'handleReturn'])->middleware('auth')->name('payment.return');

// Route de finalisation du paiement (API call from Verify View)
Route::post('/payment/finalize', [App\Http\Controllers\PaymentController::class, 'finalize'])->middleware('auth')->name('payment.finalize');

// ================================================================
// PORTAIL VENDEUR (réseau revendeurs)
// ================================================================
// Le bare /vendor (sans sous-chemin) collide avec le dossier composer vendor/
// → certains hébergements mutualisés (SiteGround, OVH…) renvoient 403 par sécurité.
// On redirige donc vers /vendor/dashboard (qui contourne la règle).
Route::get('/vendor', fn () => redirect()->route(
    auth('vendor')->check() ? 'vendor.dashboard' : 'vendor.login'
));

Route::prefix('vendor')->group(function () {
    // Auth
    Route::get('/login',  [VendorAuthController::class, 'showLoginForm'])->name('vendor.login');
    Route::post('/login', [VendorAuthController::class, 'login'])->middleware('throttle:6,1')->name('vendor.login.attempt');
    Route::match(['get', 'post'], '/logout', [VendorAuthController::class, 'logout'])->name('vendor.logout');

    // Authentifié
    Route::middleware('is_vendor')->group(function () {
        Route::get('/dashboard',        [VendorDashboardController::class, 'index'])->name('vendor.dashboard');
        Route::get('/sell',             [VendorSaleController::class, 'shop'])->name('vendor.sell');
        Route::post('/sell',            [VendorSaleController::class, 'sell'])->name('vendor.sell.store');
        Route::post('/sell/simulate',   [VendorSaleController::class, 'simulate'])->name('vendor.sell.simulate');

        // Encaissement cash (vente directe au client) — pattern lock+confirm
        Route::post('/sell/cash',                   [VendorSaleController::class, 'payCash'])->name('vendor.sell.cash');
        Route::get('/sell/cash/{order}',            [VendorSaleController::class, 'cashShow'])->name('vendor.sell.cash.show');
        Route::post('/sell/cash/{order}/confirm',   [VendorSaleController::class, 'cashConfirm'])->name('vendor.sell.cash.confirm');
        Route::post('/sell/cash/{order}/cancel',    [VendorSaleController::class, 'cashCancel'])->name('vendor.sell.cash.cancel');
        Route::match(['get', 'post'], '/checkout', [VendorSaleController::class, 'checkoutPage'])->name('vendor.checkout');
        Route::get('/orders',           [VendorSaleController::class, 'orders'])->name('vendor.orders');
        Route::get('/orders/{order}',   [VendorSaleController::class, 'showOrder'])->name('vendor.orders.show');
        Route::post('/orders/{order}/retry-delivery', [VendorSaleController::class, 'retryDelivery'])->name('vendor.orders.retry-delivery');
        Route::post('/orders/{order}/refund',         [VendorSaleController::class, 'refund'])->name('vendor.orders.refund');

        // Remise cash via E-Billing (vendeur reverse à KardAfrica)
        Route::get('/remittance',                       [VendorRemittanceController::class, 'index'])->name('vendor.remittance.index');
        Route::post('/remittance/init',                 [VendorRemittanceController::class, 'init'])->name('vendor.remittance.init');
        Route::get('/remittance/return',                [VendorRemittanceController::class, 'paymentReturn'])->name('vendor.remittance.return');
        Route::post('/remittance/finalize',             [VendorRemittanceController::class, 'paymentFinalize'])->name('vendor.remittance.finalize');

        // Encaissement cash (commandes physiques)
        Route::get('/cash',                          [VendorCashOrderController::class, 'index'])->name('vendor.cash.index');
        Route::get('/cash/{order}',                  [VendorCashOrderController::class, 'show'])->name('vendor.cash.show');
        Route::post('/cash/{order}/confirm',         [VendorCashOrderController::class, 'confirm'])->name('vendor.cash.confirm');
        Route::post('/cash/{order}/reject',          [VendorCashOrderController::class, 'reject'])->name('vendor.cash.reject');

        Route::get('/profile',          [VendorProfileController::class, 'show'])->name('vendor.profile');

        // Recharge wallet (cagnotte) via Airtel Money / Moov Money / carte
        Route::get('/wallet/recharge',           [VendorWalletRechargeController::class, 'index'])->name('vendor.wallet.recharge');
        Route::post('/wallet/recharge/init',     [VendorWalletRechargeController::class, 'init'])->name('vendor.wallet.recharge.init');
        Route::get('/wallet/recharge/return',    [VendorWalletRechargeController::class, 'paymentReturn'])->name('vendor.wallet.recharge.return');
        Route::post('/wallet/recharge/finalize', [VendorWalletRechargeController::class, 'paymentFinalize'])->name('vendor.wallet.recharge.finalize');

        // Note : la création/édition des cartes Carte Gabon a été migrée vers
        // /admin/merchant-cards. Les boutiques NE créent plus de cartes, elles
        // les vendent seulement depuis le catalogue géré par l'admin.

        // E-Billing payment flow (return depuis le portail + vérification)
        Route::get('/payment/return',   [VendorSaleController::class, 'paymentReturn'])->name('vendor.payment.return');
        Route::post('/payment/finalize',[VendorSaleController::class, 'paymentFinalize'])->name('vendor.payment.finalize');
    });
});

// PUBLIC : Récupération des cartes par le client via QR code
Route::get('/claim/{token}',          [ClaimController::class, 'show'])->name('claim.show');
Route::get('/claim/{token}/print',    [ClaimController::class, 'print'])->name('claim.print');

// ================================================================
// ADMIN AUTH (page dédiée /admin/login — accessible même en maintenance)
// ================================================================
Route::prefix('admin')->group(function () {
    Route::get('/login',  [AdminAuthController::class, 'showLoginForm'])->middleware('guest')->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware(['guest', 'throttle:6,1'])->name('admin.login.attempt');
    // Logout : POST (normal via form) + GET (fallback si CSRF a expiré — l'auth middleware garde la sécurité)
    Route::match(['get', 'post'], '/logout', [AdminAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('admin.logout');
});

// ================================================================
// ADMIN PANEL
// ================================================================
Route::prefix('admin')->middleware(['auth', 'is_admin'])->group(function () {
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Commandes
    Route::get('/orders/pending-delivery', [AdminOrderController::class, 'pendingDelivery'])->name('admin.orders.pending-delivery');
    Route::post('/orders/retry-bulk',      [AdminOrderController::class, 'retryBulk'])->middleware('throttle:5,1')->name('admin.orders.retry-bulk');
    Route::get('/health/afrikard',         [AdminOrderController::class, 'pingAfrikard'])->name('admin.health.afrikard');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::post('/orders/{order}/retry',  [AdminOrderController::class, 'retryCheckout'])->name('admin.orders.retry');
    Route::post('/orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('admin.orders.refund');

    // Utilisateurs
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::patch('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('admin.users.toggle-active');
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.update-role');

    // Paiements
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('admin.payments.index');

    // Cartes
    Route::get('/cards', [AdminCardController::class, 'index'])->name('admin.cards.index');

    // Catalogue produits (API externe)
    Route::get('/catalog', [AdminCatalogController::class, 'index'])->name('admin.catalog.index');

    // Vendeurs / Revendeurs
    Route::get('/resellers',                          [AdminResellerController::class, 'index'])->name('admin.resellers.index');
    Route::get('/resellers/create',                   [AdminResellerController::class, 'create'])->name('admin.resellers.create');
    Route::post('/resellers',                         [AdminResellerController::class, 'store'])->name('admin.resellers.store');
    Route::get('/resellers/{reseller}',               [AdminResellerController::class, 'show'])->name('admin.resellers.show');
    Route::post('/resellers/{reseller}/credit',       [AdminResellerController::class, 'credit'])->name('admin.resellers.credit');
    Route::patch('/resellers/{reseller}/settings',    [AdminResellerController::class, 'updateSettings'])->name('admin.resellers.settings');
    Route::patch('/resellers/{reseller}/toggle',      [AdminResellerController::class, 'toggle'])->name('admin.resellers.toggle');
    Route::post('/resellers/{reseller}/reset-password',[AdminResellerController::class, 'resetPassword'])->name('admin.resellers.reset-password');

    // Gestion admin des commandes vendeur (récupération cartes client)
    Route::get('/resellers/{reseller}/orders/{order}',                 [AdminVendorOrderController::class, 'show'])->name('admin.resellers.orders.show');
    Route::post('/resellers/{reseller}/orders/{order}/retry-delivery', [AdminVendorOrderController::class, 'retryDelivery'])->name('admin.resellers.orders.retry-delivery');
    Route::post('/resellers/{reseller}/orders/{order}/refund',         [AdminVendorOrderController::class, 'refund'])->name('admin.resellers.orders.refund');
    Route::post('/resellers/{reseller}/orders/{order}/inject-cards',   [AdminVendorOrderController::class, 'injectCards'])->name('admin.resellers.orders.inject-cards');

    // Propriétaires de cartes locales (commerçants — Carte Gabon)
    Route::get('/card-owners',                         [\App\Http\Controllers\Admin\CardOwnerController::class, 'index'])->name('admin.card-owners.index');
    Route::get('/card-owners/nouveau',                 [\App\Http\Controllers\Admin\CardOwnerController::class, 'create'])->name('admin.card-owners.create');
    Route::post('/card-owners',                        [\App\Http\Controllers\Admin\CardOwnerController::class, 'store'])->name('admin.card-owners.store');
    Route::post('/card-owners/quick',                  [\App\Http\Controllers\Admin\CardOwnerController::class, 'quickStore'])->name('admin.card-owners.quick');
    Route::get('/card-owners/{cardOwner}',             [\App\Http\Controllers\Admin\CardOwnerController::class, 'show'])->name('admin.card-owners.show');
    Route::get('/card-owners/{cardOwner}/edit',        [\App\Http\Controllers\Admin\CardOwnerController::class, 'edit'])->name('admin.card-owners.edit');
    Route::put('/card-owners/{cardOwner}',             [\App\Http\Controllers\Admin\CardOwnerController::class, 'update'])->name('admin.card-owners.update');
    Route::delete('/card-owners/{cardOwner}',          [\App\Http\Controllers\Admin\CardOwnerController::class, 'destroy'])->name('admin.card-owners.destroy');

    // Cartes-cadeau Carte Gabon — créées par l'admin, vendues par les boutiques
    Route::get('/merchant-cards',                            [AdminMerchantCardController::class, 'index'])->name('admin.merchant-cards.index');
    Route::get('/merchant-cards/nouvelle',                   [AdminMerchantCardController::class, 'create'])->name('admin.merchant-cards.create');
    Route::post('/merchant-cards',                           [AdminMerchantCardController::class, 'store'])->name('admin.merchant-cards.store');
    Route::get('/merchant-cards/{merchantCard}',             [AdminMerchantCardController::class, 'show'])->name('admin.merchant-cards.show');
    Route::get('/merchant-cards/{merchantCard}/edit',        [AdminMerchantCardController::class, 'edit'])->name('admin.merchant-cards.edit');
    Route::put('/merchant-cards/{merchantCard}',             [AdminMerchantCardController::class, 'update'])->name('admin.merchant-cards.update');
    Route::patch('/merchant-cards/{merchantCard}/approve',   [AdminMerchantCardController::class, 'approve'])->name('admin.merchant-cards.approve');
    Route::patch('/merchant-cards/{merchantCard}/reject',    [AdminMerchantCardController::class, 'reject'])->name('admin.merchant-cards.reject');
    Route::delete('/merchant-cards/{merchantCard}',          [AdminMerchantCardController::class, 'destroy'])->name('admin.merchant-cards.destroy');

    // Daywatch (offre streaming locale — BDD)
    Route::get('/daywatch',                          [AdminDaywatchController::class, 'index'])->name('admin.daywatch.index');
    Route::get('/daywatch/create',                   [AdminDaywatchController::class, 'create'])->name('admin.daywatch.create');
    Route::post('/daywatch',                         [AdminDaywatchController::class, 'store'])->name('admin.daywatch.store');
    Route::get('/daywatch/{daywatch}/edit',          [AdminDaywatchController::class, 'edit'])->name('admin.daywatch.edit');
    Route::put('/daywatch/{daywatch}',               [AdminDaywatchController::class, 'update'])->name('admin.daywatch.update');
    Route::delete('/daywatch/{daywatch}',            [AdminDaywatchController::class, 'destroy'])->name('admin.daywatch.destroy');
    Route::patch('/daywatch/{daywatch}/toggle',      [AdminDaywatchController::class, 'toggleActive'])->name('admin.daywatch.toggle');

    // Paramètres système
    Route::get('/settings',                       [AdminSettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('/settings/maintenance',          [AdminSettingsController::class, 'toggleMaintenance'])->name('admin.settings.maintenance');
    Route::get('/settings/maintenance/status',    [AdminSettingsController::class, 'maintenanceStatus'])->name('admin.settings.maintenance.status');
    Route::post('/settings/cache',                [AdminSettingsController::class, 'clearCache'])->name('admin.settings.cache');
    Route::post('/settings/test-mail',            [AdminSettingsController::class, 'testMail'])->name('admin.settings.test-mail');
    Route::post('/settings/currency-rates',       [AdminSettingsController::class, 'updateCurrencyRates'])->name('admin.settings.currency-rates');

    // Newsletter
    Route::get('/newsletter',                [App\Http\Controllers\Admin\NewsletterController::class, 'index'])->name('admin.newsletter.index');
    Route::get('/newsletter/export',         [App\Http\Controllers\Admin\NewsletterController::class, 'export'])->name('admin.newsletter.export');
    Route::patch('/newsletter/{subscriber}/toggle', [App\Http\Controllers\Admin\NewsletterController::class, 'toggle'])->name('admin.newsletter.toggle');
    Route::delete('/newsletter/{subscriber}', [App\Http\Controllers\Admin\NewsletterController::class, 'destroy'])->name('admin.newsletter.destroy');
});
