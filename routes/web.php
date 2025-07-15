<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Routes pour les cartes numériques
Route::resource('cards', CardController::class);

// Routes additionnelles pour les cartes
Route::get('/cards/search', [CardController::class, 'search'])->name('cards.search');
Route::get('/cards/type/{type}', [CardController::class, 'byType'])->name('cards.byType');
Route::patch('/cards/{card}/toggle-status', [CardController::class, 'toggleStatus'])->name('cards.toggleStatus');

// Route pour la page d'accueil avec les cartes en vedette
Route::get('/boutique', function () {
    $cards = \App\Models\Card::where('status', 'active')
        ->latest()
        ->limit(8)
        ->get();
    return view('boutique', compact('cards'));
})->name('boutique');

// Routes pour les pages statiques
Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/support', function () {
    return view('support');
})->name('support');

// Routes pour l'authentification et récupération de mot de passe
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLinkEmail'])
    ->name('password.email');

Route::get('/reset-password/{token}', function ($token) {
    return view('auth.reset-password', ['token' => $token]);
})->name('password.reset');

Route::post('/reset-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])
    ->name('password.update');

// Routes API pour l'authentification
Route::prefix('api')->group(function () {
    Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login'])->name('api.login');
    Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register'])->name('api.register');
    Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [App\Http\Controllers\Auth\AuthController::class, 'user'])->name('api.user');
    Route::get('/check', [App\Http\Controllers\Auth\AuthController::class, 'check'])->name('api.check');
    
    // Routes du panier
    Route::get('/cart', [App\Http\Controllers\CartController::class, 'index'])->name('api.cart.index');
    Route::post('/cart/add', [App\Http\Controllers\CartController::class, 'add'])->name('api.cart.add');
    Route::put('/cart/{id}', [App\Http\Controllers\CartController::class, 'update'])->name('api.cart.update');
    Route::delete('/cart/{id}', [App\Http\Controllers\CartController::class, 'remove'])->name('api.cart.remove');
    Route::delete('/cart', [App\Http\Controllers\CartController::class, 'clear'])->name('api.cart.clear');
    
    // Routes des commandes
    Route::middleware('auth')->group(function () {
        Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index'])->name('api.orders.index');
        Route::post('/orders', [App\Http\Controllers\OrderController::class, 'store'])->name('api.orders.store');
        Route::get('/orders/{order}', [App\Http\Controllers\OrderController::class, 'show'])->name('api.orders.show');
        
        // Routes des paiements
        Route::post('/payments/process', [App\Http\Controllers\PaymentController::class, 'process'])->name('api.payments.process');
        Route::get('/payments/{payment}/status', [App\Http\Controllers\PaymentController::class, 'status'])->name('api.payments.status');
    });
});
