<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Affiche la page 503 quand le mode maintenance est ON.
     * Bypass automatique :
     *  - Pour tout admin authentifié (peut continuer à travailler)
     *  - Pour les routes d'authentification admin (login/logout) afin
     *    de pouvoir se connecter même si la maintenance est ON
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!AppSetting::isMaintenanceMode()) {
            return $next($request);
        }

        // Whitelist : routes nécessaires pour s'authentifier en admin
        if ($request->is('admin/login') || $request->is('admin/logout')) {
            return $next($request);
        }

        // Admin authentifié → bypass total
        if (Auth::check() && optional(Auth::user())->isAdmin()) {
            return $next($request);
        }

        // Sinon → page maintenance
        return response()->view('errors.503', [], 503, [
            'Retry-After' => 60,
        ]);
    }
}
