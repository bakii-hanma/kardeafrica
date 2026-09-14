<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PhoneVerification;
use App\Services\OtpService;
use App\Support\ClientAccount;
use App\Support\Phone;
use App\Support\PhoneInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Connexion client par WhatsApp — et création de compte du même geste.
 *
 * POURQUOI CE CHEMIN EXISTE
 * -------------------------
 * L'inscription classique exige une adresse e-mail unique et un mot de passe.
 * Un client servi au comptoir n'a ni l'une ni l'autre : son compte est créé pour
 * lui à partir de son seul numéro. Sans ce chemin, il ne pourrait jamais y
 * revenir une fois le lien de remise expiré — la promesse « ta carte reste dans
 * ton compte » serait affichée sans être tenue.
 *
 * Connexion et inscription sont volontairement le même geste. Demander à un
 * client de savoir s'il « a déjà un compte » n'a aucun sens quand c'est le
 * vendeur qui l'a ouvert pour lui.
 *
 * Réutilise `OtpService` tel quel : code à 6 chiffres haché en bcrypt, TTL de
 * 10 minutes, 5 tentatives, cooldown de 60 secondes entre deux envois.
 */
class ClientWhatsAppLoginController extends Controller
{
    private const SESSION_PHONE = 'client_login_phone';

    public function __construct(private OtpService $otp) {}

    public function show()
    {
        return view('auth.whatsapp-phone');
    }

    public function send(Request $request)
    {
        $request->validate([
            'phone_country'  => ['nullable', 'string', 'size:2'],
            'phone_national' => ['required_without:phone', 'nullable', 'string', 'max:30'],
            'phone'          => ['required_without:phone_national', 'nullable', 'string', 'max:30'],
        ]);

        $phone = PhoneInput::accountKeyFromRequest($request, 'phone');

        if ($phone === null) {
            $msg = 'Numéro incomplet ou ambigu. Vérifie l\'indicatif du pays et le numéro.';
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->withErrors(['phone' => $msg])->withInput();
        }

        // Le compte est créé maintenant, pas après vérification : c'est ce qui
        // permet à un client servi au comptoir de retrouver ses cartes. Il reste
        // inerte tant que personne n'a prouvé la possession du numéro — l'OTP
        // est cette preuve.
        $user = ClientAccount::findOrCreate($phone, null, ClientAccount::VIA_ONLINE);

        if ($user === null) {
            return back()->withErrors(['phone' => 'Ce numéro ne permet pas d\'identifier un compte.'])->withInput();
        }

        if (! $user->is_active) {
            // Message neutre : un compte suspendu ne doit pas se deviner depuis
            // l'écran de connexion.
            $msg = 'Connexion impossible avec ce numéro. Contacte le support.';
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 403)
                : back()->withErrors(['phone' => $msg])->withInput();
        }

        $envoi = $this->otp->sendOtp($phone, PhoneVerification::PURPOSE_CLIENT_LOGIN);

        if ($envoi['cooldown']) {
            $request->session()->put(self::SESSION_PHONE, $phone);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true, 'step' => 'code', 'cooldown' => true,
                    'seconds' => $envoi['seconds'], 'phone_masked' => Phone::masked($phone),
                ]);
            }

            return redirect()->route('client.whatsapp.code')
                ->with('info', "Un code vient d'être envoyé. Patiente {$envoi['seconds']} secondes avant d'en demander un autre.");
        }

        if (! $envoi['sent']) {
            Log::warning('ClientLogin: envoi OTP WhatsApp en échec', ['phone' => Phone::masked($phone)]);

            $msg = "L'envoi WhatsApp a échoué. Vérifie que ce numéro a bien WhatsApp, ou réessaie dans un instant.";
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 502)
                : back()->withErrors(['phone' => $msg])->withInput();
        }

        $request->session()->put(self::SESSION_PHONE, $phone);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'step' => 'code', 'phone_masked' => Phone::masked($phone)]);
        }

        return redirect()->route('client.whatsapp.code');
    }

    public function showCode(Request $request)
    {
        $phone = $request->session()->get(self::SESSION_PHONE);

        if (! $phone) {
            return redirect()->route('client.whatsapp.login');
        }

        return view('auth.whatsapp-code', [
            'phoneMasked' => Phone::masked($phone),
        ]);
    }

    public function verify(Request $request)
    {
        $phone = $request->session()->get(self::SESSION_PHONE);

        if (! $phone) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'reset' => true, 'message' => 'Session expirée. Resaisis ton numéro.'], 422)
                : redirect()->route('client.whatsapp.login');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $resultat = $this->otp->verify($phone, $data['code'], PhoneVerification::PURPOSE_CLIENT_LOGIN);

        if (! $resultat['ok']) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $resultat['reason']], 422)
                : back()->withErrors(['code' => $resultat['reason']]);
        }

        $user = ClientAccount::find($phone);

        if ($user === null || ! $user->is_active) {
            $msg = 'Connexion impossible avec ce numéro. Contacte le support.';
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'reset' => true, 'message' => $msg], 403)
                : redirect()->route('client.whatsapp.login')->withErrors(['phone' => $msg]);
        }

        // Le code consommé ne doit pas pouvoir resservir à une autre session.
        PhoneVerification::where('phone', $phone)
            ->where('purpose', PhoneVerification::PURPOSE_CLIENT_LOGIN)
            ->delete();

        // Recevoir le code sur WhatsApp prouve la possession de la ligne : c'est
        // seulement ici que le numéro devient vérifié, jamais à la saisie du
        // vendeur.
        ClientAccount::markClaimed($user);

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->forget(self::SESSION_PHONE);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirect' => route('home')]);
        }

        return redirect()->intended(route('home'))
            ->with('success', 'Te voilà connecté. Tes cartes sont dans ton compte.');
    }

    public function resend(Request $request)
    {
        $phone = $request->session()->get(self::SESSION_PHONE);

        if (! $phone) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'reset' => true, 'message' => 'Session expirée.'], 422)
                : redirect()->route('client.whatsapp.login');
        }

        $envoi = $this->otp->sendOtp($phone, PhoneVerification::PURPOSE_CLIENT_LOGIN);

        if ($request->expectsJson()) {
            if ($envoi['cooldown']) {
                return response()->json(['ok' => true, 'cooldown' => true, 'seconds' => $envoi['seconds']]);
            }
            return $envoi['sent']
                ? response()->json(['ok' => true])
                : response()->json(['ok' => false, 'message' => "L'envoi WhatsApp a échoué. Réessaie dans un instant."], 502);
        }

        if ($envoi['cooldown']) {
            return back()->with('info', "Patiente encore {$envoi['seconds']} secondes.");
        }

        return $envoi['sent']
            ? back()->with('success', 'Nouveau code envoyé sur WhatsApp.')
            : back()->withErrors(['code' => "L'envoi WhatsApp a échoué. Réessaie dans un instant."]);
    }
}
