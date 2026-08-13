<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PhoneVerification;
use App\Models\Reseller;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Réinitialisation du mot de passe REVENDEUR (guard vendor) par OTP WhatsApp.
 *
 * Jusqu'ici un revendeur ayant perdu son mot de passe dépendait entièrement
 * d'un administrateur : aucune route de récupération n'existait sous /vendor.
 *
 * Le revendeur s'identifie par son CODE VENDEUR (ce qu'il utilise pour se
 * connecter) ou par son numéro de téléphone — l'un ou l'autre, dans le même
 * champ. Le code part toujours sur le téléphone enregistré au compte.
 *
 * Réutilise OtpService (code bcrypt, TTL 10 min, 5 tentatives, cooldown 60 s)
 * avec un purpose DÉDIÉ : un OTP propriétaire ne peut pas servir ici.
 *
 * Anti-énumération : réponse et redirection identiques que le compte existe
 * ou non — seul l'envoi diffère.
 */
class PasswordResetController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /** Étape 1 — « ton code vendeur ou ton numéro ». */
    public function showRequest()
    {
        return view('vendor.auth.password-request');
    }

    /** Étape 1 (POST) — envoie le code si le compte existe ; réponse générique. */
    public function sendCode(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:40'],
        ], [
            'identifier.required' => 'Saisis ton code vendeur ou ton numéro de téléphone.',
        ]);

        $reseller = $this->findReseller($data['identifier']);
        $phone    = $reseller?->phone ? OtpService::normalizeGabon($reseller->phone) : null;

        if ($reseller && $phone) {
            $this->otp->sendOtp($phone, PhoneVerification::PURPOSE_VENDOR_RESET);
            $request->session()->put('vendor_reset_phone', $phone);
        } else {
            Log::info('Reset revendeur : identifiant inconnu ou sans téléphone (aucun envoi)', [
                'identifier' => $data['identifier'],
            ]);
            // Parcours identique : on mémorise l'identifiant saisi pour que
            // l'écran suivant s'affiche de la même façon.
            $request->session()->put('vendor_reset_phone', OtpService::normalizeGabon($data['identifier']));
        }

        return redirect()
            ->route('vendor.password.reset.show')
            ->with('status', "Si un compte correspond, un code vient d'être envoyé sur WhatsApp.");
    }

    /** Étape 2 — code + nouveau mot de passe. */
    public function showReset(Request $request)
    {
        $phone = $request->session()->get('vendor_reset_phone');
        if (!$phone) {
            return redirect()->route('vendor.password.request');
        }

        return view('vendor.auth.password-reset', [
            'phoneMasked' => strlen($phone) > 6
                ? substr($phone, 0, 4) . ' •• •• ' . substr($phone, -2)
                : '•• •• ••',
        ]);
    }

    /** Étape 2 (POST) — vérifie l'OTP puis change le mot de passe. */
    public function reset(Request $request)
    {
        $phone = $request->session()->get('vendor_reset_phone');
        if (!$phone) {
            return redirect()->route('vendor.password.request');
        }

        $data = $request->validate([
            'code'     => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $result = $this->otp->verify($phone, $data['code'], PhoneVerification::PURPOSE_VENDOR_RESET);
        if (!($result['ok'] ?? false)) {
            return back()->withErrors(['code' => $result['reason'] ?? 'Code invalide.']);
        }

        $reseller = Reseller::where('phone', $phone)->first();
        if (!$reseller) {
            // Numéro sans compte (parcours anti-énumération) : on termine sans rien changer.
            $request->session()->forget('vendor_reset_phone');
            return redirect()->route('vendor.login')
                ->with('success', 'Mot de passe mis à jour. Connecte-toi.');
        }

        $reseller->password = $data['password'];   // cast 'hashed'
        $reseller->save();

        // L'OTP consommé ne doit pas être rejouable pour un second reset.
        PhoneVerification::where('phone', $phone)
            ->where('purpose', PhoneVerification::PURPOSE_VENDOR_RESET)
            ->delete();

        $request->session()->forget('vendor_reset_phone');
        $request->session()->regenerate();

        Log::info('Reset revendeur : mot de passe réinitialisé', ['reseller_id' => $reseller->id]);

        return redirect()->route('vendor.login')
            ->with('success', 'Mot de passe mis à jour. Connecte-toi.');
    }

    /** Retrouve le compte par code vendeur OU par téléphone. */
    private function findReseller(string $identifier): ?Reseller
    {
        $identifier = trim($identifier);

        // Un code vendeur contient des lettres (KA-V-XXXX) ; un numéro non.
        if (preg_match('/[A-Za-z]/', $identifier)) {
            return Reseller::where('vendor_code', strtoupper($identifier))->first();
        }

        return Reseller::whereIn('phone', self::phoneCandidates($identifier))->first();
    }

    /**
     * Formes possibles d'un même numéro gabonais en base.
     *
     * Le dépôt mélange DEUX conventions : `OtpService::normalizeGabon` retire le
     * zéro initial (« 06 87 13 09 » → 2416871309) alors que plusieurs comptes
     * sont enregistrés avec (24106871309). Chercher une seule forme raterait la
     * moitié des comptes — on interroge donc les deux.
     *
     * @return array<int, string>
     */
    public static function phoneCandidates(string $raw): array
    {
        $normalized = OtpService::normalizeGabon($raw);
        $candidates = [$normalized];

        if (str_starts_with($normalized, '241')) {
            $rest = substr($normalized, 3);
            // Variante AVEC le zéro national conservé
            $candidates[] = '241' . ltrim($rest, '0');
            $candidates[] = '2410' . ltrim($rest, '0');
        }

        // Chiffres bruts, au cas où le compte aurait été saisi sans indicatif.
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if ($digits !== '') $candidates[] = $digits;

        return array_values(array_unique(array_filter($candidates)));
    }
}
