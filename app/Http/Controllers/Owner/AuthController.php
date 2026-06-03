<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\CardOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentification du propriétaire de carte locale (Carte Gabon).
 * Guard `card_owner` — login par email + mot de passe.
 */
class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('card_owner')->check()) {
            return redirect()->route('owner.dashboard');
        }
        return view('owner.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $owner = CardOwner::where('email', $request->email)->first();

        if (!$owner || !Hash::check($request->password, $owner->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email ou mot de passe incorrect.',
            ]);
        }

        if (!$owner->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Ton compte est désactivé. Contacte l\'admin.',
            ]);
        }

        Auth::guard('card_owner')->login($owner, $request->boolean('remember'));
        $owner->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        return redirect()->intended(route('owner.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('card_owner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('owner.login');
    }
}
