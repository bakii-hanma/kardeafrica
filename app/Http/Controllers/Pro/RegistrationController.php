<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\CardOwner;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Inscription publique d'un compte pro/commerçant (= CardOwner).
 *
 * Étape 1 du parcours d'onboarding : création du compte (status=pending_otp) puis
 * envoi de l'OTP WhatsApp. La suite : OtpController (étape 2), KycController (étape 3).
 * Voir docs/PROJET_ETAT_ET_ROADMAP.md §3.
 */
class RegistrationController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /** Landing « Devenez partenaire ». */
    public function landing()
    {
        return view('pro.landing');
    }

    /** Étape 1 — formulaire de création de compte. */
    public function showRegister()
    {
        // Déjà connecté en tant que pro → on l'envoie vers son espace.
        if (Auth::guard('card_owner')->check()) {
            return redirect()->route('owner.dashboard');
        }
        return view('pro.register');
    }

    /** Étape 1 — traitement : crée le CardOwner + envoie l'OTP. */
    public function register(Request $request)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'contact_name'  => ['required', 'string', 'max:120'],
            'phone'         => ['required', 'string', 'max:30'],
            'email'         => ['required', 'email', 'max:190', 'unique:card_owners,email'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'business_name' => 'nom de l\'entreprise',
            'contact_name'  => 'nom du gérant',
        ]);

        $phoneE164 = OtpService::normalizeGabon($data['phone']);
        if (strlen($phoneE164) < 11) {
            return back()->withInput()->withErrors(['phone' => 'Numéro de téléphone invalide.']);
        }

        // M21 : status hors mass-assignment → affectation directe.
        $owner = new CardOwner([
            'business_name'   => $data['business_name'],
            'contact_name'    => $data['contact_name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'],
            'whatsapp_number' => $phoneE164,
            'password'        => $data['password'], // cast 'hashed'
        ]);
        $owner->status    = CardOwner::STATUS_PENDING_OTP;
        $owner->is_active = false; // pas encore d'accès tant que l'OTP + KYC ne sont pas faits
        $owner->save();

        // Envoi de l'OTP WhatsApp
        $result = $this->otp->sendOtp($phoneE164);
        if (!$result['sent'] && !$result['cooldown']) {
            Log::warning('OTP non envoyé à l\'inscription pro', ['owner_id' => $owner->id]);
        }

        // On mémorise le dossier en cours en session (pas encore connecté).
        $request->session()->put('pro.onboarding.owner_id', $owner->id);
        $request->session()->put('pro.onboarding.phone', $phoneE164);

        return redirect()->route('pro.verification.show')
            ->with('status', 'Un code de vérification a été envoyé sur votre WhatsApp.');
    }
}
