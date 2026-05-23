<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Models\UserCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Format unifie d'une UserCard pour le mobile.
 * - `value`/`balance` : prix paye en FCFA (depuis order_item.unit_price), c'est ce que le user voit
 * - `face_value` : valeur faciale de la carte dans sa devise originale (40 EUR par ex.)
 * - `face_currency` : devise faciale de la carte
 *
 * function_exists() : protège contre le double-include lors de `route:cache` /
 * `config:cache` qui peut charger ce fichier deux fois et provoquer un
 * "Cannot redeclare" en prod.
 */
if (!function_exists('formatUserCardForApi')) {
    function formatUserCardForApi(UserCard $card): array
    {
        $pricePaid = $card->price_paid_xaf;

        return [
            'id'             => $card->id,
            'name'           => $card->name,
            'type'           => 'Gift Card',
            'value'          => $pricePaid,
            'balance'        => $pricePaid,
            'currency'       => 'XAF',
            'price_paid_xaf' => $pricePaid,
            'face_value'     => (float) $card->face_value,
            'face_currency'  => $card->currency,
            'code'           => $card->card_code,
            'pin'            => $card->pin,
            'serial_number'  => $card->serial_number,
            'expiry_date'    => $card->expiration_date?->toDateString(),
            'image'          => $card->image_url,
            'status'         => $card->status,
            'description'    => null,
            'brand'          => $card->brand,
            'product_id'     => $card->product_id,
            'order_id'       => $card->order_id,
            'created_at'     => $card->created_at->toIso8601String(),
            'updated_at'     => $card->updated_at->toIso8601String(),
        ];
    }
}

// Health check
Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'KardAfrica API is running',
        'timestamp' => now()
    ]);
});

// Auth routes with rate limiting
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
    Route::post('/profile/password', [AuthController::class, 'changePassword']);
    Route::get('/user', [AuthController::class, 'user']);

    // Verification du mot de passe pour gater les actions sensibles (reveal de cartes)
    Route::post('/verify-password', [App\Http\Controllers\WebAuthController::class, 'verifyPassword'])
        ->middleware('throttle:5,1');

    // ========================================
    // User Cards (purchased cards from checkout API)
    // ========================================
    Route::get('/cards', function (Request $request) {
        $cards = UserCard::where('user_id', $request->user()->id)
            ->with('orderItem')
            ->latest()
            ->get()
            ->map(fn($card) => formatUserCardForApi($card));

        return response()->json($cards);
    });

    Route::get('/cards/{id}', function (Request $request, $id) {
        $card = UserCard::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('orderItem')
            ->first();

        if (!$card) {
            return response()->json(['message' => 'Carte non trouvee'], 404);
        }

        return response()->json(formatUserCardForApi($card));
    });

    // ========================================
    // Orders
    // ========================================
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index']);
    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::post('/orders/simulate', [App\Http\Controllers\Api\OrderController::class, 'simulate'])
        ->middleware('throttle:10,1');
    Route::get('/orders/{order}', [App\Http\Controllers\OrderController::class, 'show']);
    // Recupere les cartes du fournisseur APRES verification du paiement
    Route::post('/orders/{order}/checkout', [App\Http\Controllers\Api\OrderController::class, 'checkout']);
    // Relance la livraison pour une commande payée mais sans cartes (échec afrikard)
    Route::post('/orders/{order}/retry-delivery', [App\Http\Controllers\Api\OrderController::class, 'retryDelivery'])
        ->middleware('throttle:5,1');

    // ========================================
    // Payment
    // ========================================
    Route::post('/payment/init', [App\Http\Controllers\PaymentController::class, 'init'])
        ->middleware('throttle:10,1')
        ->name('api.payment.init');
    Route::post('/payment/finalize', [App\Http\Controllers\PaymentController::class, 'finalize'])
        ->name('api.payment.finalize'); // legacy
});

// Status polling — public car appele frequemment depuis le WebView mobile
Route::get('/payment/check-status', [App\Http\Controllers\PaymentController::class, 'checkStatus'])
    ->middleware('throttle:60,1')
    ->name('api.payment.check');

// Webhook E-Billing
Route::any('/payment/callback', [App\Http\Controllers\PaymentController::class, 'handleCallback']);


/* ============================================================
 * Catalogue public pour l'app mobile (Expo) — wrap ProductApiService
 * Gère filtres, blocage UAE, region tag, popular_in_africa, etc.
 * ============================================================ */
Route::prefix('v1')->group(function () {
    Route::get('/catalog',           [CatalogController::class, 'index'])->middleware('throttle:60,1')->name('api.v1.catalog.index');
    Route::get('/catalog/popular',   [CatalogController::class, 'popular'])->middleware('throttle:60,1')->name('api.v1.catalog.popular');
    Route::get('/catalog/{id}',      [CatalogController::class, 'show'])->middleware('throttle:60,1')->name('api.v1.catalog.show');
    Route::get('/categories',        [CatalogController::class, 'categories'])->middleware('throttle:60,1')->name('api.v1.categories');
    Route::get('/currency-rates',    [CatalogController::class, 'currencyRates'])->middleware('throttle:60,1')->name('api.v1.currency-rates');
});
