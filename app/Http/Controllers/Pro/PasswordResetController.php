<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\CardOwner;
use App\Models\PhoneVerification;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Réinitialisation du mot de passe PROPRIÉTAIRE (guard card_owner) par OTP
 * WhatsApp — dette notée depuis l'onboarding (seul l'admin pouvait le faire).
 *
 * Canal : le téléphone (vérifié à l'inscription via WHAPI), pas l'email (jamais
 * vérifié pour les pros). Réutilise OtpService (code bcrypt, TTL 10 min,
 * 5 tentatives, cooldown 60 s) avec un purpose DÉDIÉ — un OTP d'inscription ne
 * peut pas servir à un reset et inversement.
 *
 * Anti-énumération : la réponse est identique que le numéro existe ou non
 * (aucun envoi si inconnu, mais même message + même redirection).
 */
class PasswordResetController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /** Étape 1 — formulaire « votre numéro ». */
    public function showRequest()
    {
        return view('pro.password-request');
    }

    /** Étape 1 (POST) — envoie le code si le compte existe ; réponse générique. */
    public function sendCode(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $phone = OtpService::normalizeGabon($data['phone']);
        $owner = CardOwner::where('phone', $phone)->first();

        if ($owner) {
            $this->otp->sendOtp($phone, PhoneVerification::PURPOSE_OWNER_RESET);
        } else {
            Log::info('Reset pro : numéro inconnu (aucun envoi)', ['phone' => $phone]);
        }

        // Même parcours dans les deux cas (anti-énumération)
        $request->session()->put('pro_reset_phone', $phone);

        return redirect()
            ->route('pro.password.reset.show')
            ->with('status', 'Si un compte existe avec ce numéro, un code vient d\'être envoyé sur WhatsApp.');
    }

    /** Étape 2 — formulaire code + nouveau mot de passe. */
    public function showReset(Request $request)
    {
        $phone = $request->session()->get('pro_reset_phone');
        if (!$phone) {
            return redirect()->route('pro.password.request');
        }

        return view('pro.password-reset', [
            'phoneMasked' => substr($phone, 0, 4) . ' •• •• ' . substr($phone, -2),
        ]);
    }

    /** Étape 2 (POST) — vérifie l'OTP puis change le mot de passe. */
    public function reset(Request $request)
    {
        $phone = $request->session()->get('pro_reset_phone');
        if (!$phone) {
            return redirect()->route('pro.password.request');
        }

        $data = $request->validate([
            'code'     => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $result = $this->otp->verify($phone, $data['code'], PhoneVerification::PURPOSE_OWNER_RESET);
        if (!($result['ok'] ?? false)) {
            return back()->withErrors(['code' => $result['reason'] ?? 'Code invalide.']);
        }

        $owner = CardOwner::where('phone', $phone)->first();
        if (!$owner) {
            // Numéro sans compte (parcours anti-énumération) : on termine sans rien changer.
            $request->session()->forget('pro_reset_phone');
            return redirect()->route('owner.login')
                ->with('status', 'Mot de passe mis à jour. Connectez-vous.');
        }

        // cast 'hashed' du modèle : affectation directe suffit
        $owner->password = $data['password'];
        $owner->save();

        // L'OTP consommé ne doit pas être rejouable pour un second reset
        PhoneVerification::where('phone', $phone)
            ->where('purpose', PhoneVerification::PURPOSE_OWNER_RESET)
            ->delete();

        $request->session()->forget('pro_reset_phone');
        $request->session()->regenerate();

        Log::info('Reset pro : mot de passe réinitialisé', ['card_owner_id' => $owner->id]);

        return redirect()->route('owner.login')
            ->with('status', 'Mot de passe mis à jour. Connectez-vous.');
    }
}
