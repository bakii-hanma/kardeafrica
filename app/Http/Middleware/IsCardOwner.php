<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsCardOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('card_owner')->check()) {
            return redirect()->route('owner.login');
        }

        $owner = Auth::guard('card_owner')->user();
        if (!$owner->is_active) {
            Auth::guard('card_owner')->logout();
            return redirect()->route('owner.login')->withErrors([
                'email' => 'Ton compte propriétaire est désactivé. Contacte l\'admin.',
            ]);
        }

        return $next($request);
    }
}
