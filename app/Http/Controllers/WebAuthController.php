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

        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'user',
            'is_active' => true,
        ]);

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

        return response()->json(['ok' => true]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
