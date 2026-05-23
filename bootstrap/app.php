<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/*',
            // Logouts : si la session a expiré pendant la consultation, le token CSRF
            // du form est obsolète → 419 alors qu'on veut juste se déconnecter.
            // L'auth middleware garde la sécurité (faut être logged-in pour hit ces routes).
            'admin/logout',
            'vendor/logout',
            'logout',
        ]);

        // ⚠️ On n'utilise PAS le mode maintenance natif Laravel (storage/framework/down)
        // — il bloque toutes les routes même whitelistées et casse les sessions admin.
        // À la place, on a un middleware custom CheckMaintenanceMode qui lit un flag
        // en BDD (table app_settings) et bypass automatiquement les admins authentifiés.
        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        // Quand un utilisateur non connecté tente d'accéder à /admin/*,
        // on le redirige vers /admin/login plutôt que /login (public).
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
            return route('login');
        });

        $middleware->alias([
            'is_admin'  => \App\Http\Middleware\IsAdmin::class,
            'is_vendor' => \App\Http\Middleware\IsVendor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
