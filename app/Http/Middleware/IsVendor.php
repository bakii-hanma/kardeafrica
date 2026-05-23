<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('vendor')->check()) {
            return redirect()->route('vendor.login');
        }

        $reseller = Auth::guard('vendor')->user();
        if (!$reseller->is_active) {
            Auth::guard('vendor')->logout();
            return redirect()->route('vendor.login')->withErrors([
                'vendor_code' => 'Ton compte vendeur est désactivé. Contacte l\'admin.',
            ]);
        }

        return $next($request);
    }
}
