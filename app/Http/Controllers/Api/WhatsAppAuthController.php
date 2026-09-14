<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneVerification;
use App\Services\OtpService;
use App\Support\ClientAccount;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Auth mobile par numéro WhatsApp + code OTP.
 *
 * Miroir API du parcours web `ClientWhatsAppLoginController` : connexion et
 * inscription sont le même geste. Le client saisit son numéro, reçoit un code
 * à 6 chiffres sur WhatsApp, le saisit, et obtient un token Sanctum. Le compte
 * est créé au premier envoi (inerte tant que l'OTP n'a pas prouvé la ligne).
 */
class WhatsAppAuthController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /**
     * Étape 1 — le client donne son numéro, on lui envoie un code WhatsApp.
     * Crée le compte s'il n'existe pas encore (findOrCreate).
     */
    public function send(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $phone = Phone::accountKey($request->input('phone'));

        if ($phone === null) {
            return response()->json([
                'status'  => 'error',
                'message' => "Numéro incomplet ou ambigu. Vérifie l'indicatif du pays et le numéro.",
            ], 422);
        }

        $user = ClientAccount::findOrCreate($phone, null, ClientAccount::VIA_ONLINE);

        if ($user === null) {
            return response()->json([
                'status'  => 'error',
                'message' => "Ce numéro ne permet pas d'identifier un compte.",
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Connexion impossible avec ce numéro. Contacte le support.',
            ], 403);
        }

        $envoi = $this->otp->sendOtp($phone, PhoneVerification::PURPOSE_CLIENT_LOGIN);

        if ($envoi['cooldown']) {
            return response()->json([
                'status'   => 'cooldown',
                'message'  => "Un code vient d'être envoyé. Patiente {$envoi['seconds']} secondes.",
                'seconds'  => $envoi['seconds'],
            ], 429);
        }

        if (! $envoi['sent']) {
            Log::warning('API WhatsApp login: envoi OTP en échec', ['phone' => Phone::masked($phone)]);

            return response()->json([
                'status'  => 'error',
                'message' => "L'envoi WhatsApp a échoué. Vérifie que ce numéro a bien WhatsApp, ou réessaie.",
            ], 502);
        }

        return response()->json([
            'status'       => 'success',
            'message'      => 'Code envoyé sur WhatsApp.',
            'phone_masked' => Phone::masked($phone),
        ]);
    }

    /**
     * Étape 2 — le client saisit le code ; on le vérifie et on renvoie un token.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'code'  => ['required', 'string', 'size:6'],
        ]);

        $phone = Phone::accountKey($request->input('phone'));

        if ($phone === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Numéro invalide.',
            ], 422);
        }

        $resultat = $this->otp->verify($phone, $request->input('code'), PhoneVerification::PURPOSE_CLIENT_LOGIN);

        if (! $resultat['ok']) {
            return response()->json([
                'status'  => 'error',
                'message' => $resultat['reason'],
            ], 422);
        }

        $user = ClientAccount::find($phone);

        if ($user === null || ! $user->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Connexion impossible avec ce numéro. Contacte le support.',
            ], 403);
        }

        // Le code consommé ne doit pas resservir.
        PhoneVerification::where('phone', $phone)
            ->where('purpose', PhoneVerification::PURPOSE_CLIENT_LOGIN)
            ->delete();

        // Recevoir le code prouve la possession de la ligne → numéro vérifié.
        ClientAccount::markClaimed($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Connecté.',
            'data'    => [
                'user'  => $user->load('profile'),
                'token' => $token,
            ],
        ]);
    }
}
