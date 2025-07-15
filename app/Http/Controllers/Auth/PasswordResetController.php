<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Afficher le formulaire de demande de réinitialisation de mot de passe
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Envoyer un lien de réinitialisation de mot de passe
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'L\'adresse e-mail est requise.',
            'email.email' => 'Veuillez entrer une adresse e-mail valide.',
            'email.exists' => 'Cette adresse e-mail n\'est pas enregistrée dans notre système.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Envoyer le lien de réinitialisation
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Un lien de réinitialisation a été envoyé à votre adresse e-mail.');
        }

        return back()->withErrors(['email' => 'Impossible d\'envoyer le lien de réinitialisation.']);
    }

    /**
     * Afficher le formulaire de réinitialisation de mot de passe
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password')->with([
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'token.required' => 'Le token de réinitialisation est requis.',
            'email.required' => 'L\'adresse e-mail est requise.',
            'email.email' => 'Veuillez entrer une adresse e-mail valide.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('home')->with('status', 'Votre mot de passe a été réinitialisé avec succès !');
        }

        return back()->withErrors(['email' => 'Impossible de réinitialiser le mot de passe.']);
    }
} 