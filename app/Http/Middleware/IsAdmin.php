<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Vérifier que l'utilisateur est administrateur.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pas connecté → redirection vers le login admin (avec intended URL)
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Authentification requise.'], 401);
            }
            return redirect()->guest(route('admin.login'));
        }

        // Connecté mais pas admin → on déconnecte et on renvoie vers /admin/login avec un message
        if (!Auth::user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès non autorisé.'], 403);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => "Ce compte n'a pas les droits d'administration.",
            ]);
        }

        return $next($request);
    }
}
