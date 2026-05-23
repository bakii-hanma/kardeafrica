<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('vendor')->check()) {
            return redirect()->route('vendor.dashboard');
        }
        return view('vendor.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'vendor_code' => ['required', 'string', 'max:30'],
            'password'    => ['required', 'string'],
        ]);

        $reseller = Reseller::where('vendor_code', $request->vendor_code)->first();

        if (!$reseller || !\Hash::check($request->password, $reseller->password)) {
            throw ValidationException::withMessages([
                'vendor_code' => 'Code vendeur ou mot de passe incorrect.',
            ]);
        }

        if (!$reseller->is_active) {
            throw ValidationException::withMessages([
                'vendor_code' => 'Ton compte vendeur est désactivé. Contacte l\'admin.',
            ]);
        }

        Auth::guard('vendor')->login($reseller, $request->boolean('remember'));
        $reseller->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        return redirect()->intended(route('vendor.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('vendor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('vendor.login');
    }
}
