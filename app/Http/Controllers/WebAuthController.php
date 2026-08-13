<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WebAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            // SÉCURITÉ (H12) : un compte désactivé depuis le back-office ne doit
            // plus pouvoir se connecter ni acheter.
            if (!Auth::user()->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $message = 'Ce compte est désactivé. Contactez le support.';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['errors' => ['email' => [$message]]], 403);
                }
                return back()->withErrors(['email' => $message])->onlyInput('email');
            }

            $request->session()->regenerate();

            $redirect = Auth::user()->isAdmin() ? route('admin.dashboard') : route('home');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'redirect' => $redirect
                ]);
            }

            return redirect()->intended($redirect);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'errors' => [
                    'email' => ['Les identifiants fournis ne correspondent pas à nos enregistrements.']
                ]
            ], 422);
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
        ])->onlyInput('email');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // M21 : role/is_active ne sont plus mass-assignables — affectation explicite
        $user = new User([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);
        $user->role = 'user';
        $user->is_active = true;
        $user->save();

        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
        ]);

        Auth::login($user);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'redirect' => route('home')
            ]);
        }

        return redirect(route('home'));
    }

    /**
     * Verifie le mot de passe de l'utilisateur connecte.
     * Utilise pour gater les actions sensibles (reveal de codes/PIN de cartes).
     * Renvoie JSON. Soumis a un throttle pour eviter le brute force.
     */
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:1',
        ]);

        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Mot de passe incorrect.',
            ], 422);
        }

        // M1 : le gate de révélation est désormais APPLIQUÉ CÔTÉ SERVEUR.
        // Une vérification réussie ouvre une fenêtre courte (120 s) pendant
        // laquelle GET /api/cards/{id} accepte de renvoyer code+PIN. Sans cette
        // fenêtre, l'endpoint ne renvoie jamais les secrets — le gate n'est plus
        // décoratif/client-side.
        $ttl = 120;
        \Illuminate\Support\Facades\Cache::put(
            'card-reveal:' . $request->user()->id,
            true,
            now()->addSeconds($ttl)
        );

        return response()->json(['ok' => true, 'reveal_ttl' => $ttl]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
