<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\CardOwner;
use App\Services\OtpService;
use Illuminate\Http\Request;

/**
 * Étape 2 du parcours d'onboarding pro : vérification du code OTP WhatsApp.
 */
class OtpController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /** Récupère le dossier en cours (session) ou null. */
    private function currentOwner(Request $request): ?CardOwner
    {
        $id = $request->session()->get('pro.onboarding.owner_id');
        return $id ? CardOwner::find($id) : null;
    }

    public function show(Request $request)
    {
        $owner = $this->currentOwner($request);
        if (!$owner) {
            return redirect()->route('pro.register.show')
                ->withErrors(['phone' => 'Session expirée, recommence l\'inscription.']);
        }
        // Déjà vérifié → on avance au KYC.
        if ($owner->status !== CardOwner::STATUS_PENDING_OTP) {
            return redirect()->route('pro.kyc.show');
        }

        return view('pro.verify', [
            'phoneMasked' => $this->mask($request->session()->get('pro.onboarding.phone', '')),
        ]);
    }

    public function verify(Request $request)
    {
        $owner = $this->currentOwner($request);
        if (!$owner) {
            return redirect()->route('pro.register.show')
                ->withErrors(['phone' => 'Session expirée, recommence l\'inscription.']);
        }

        $request->validate(['code' => ['required', 'string', 'size:6']]);
        $phone = $request->session()->get('pro.onboarding.phone');

        $res = $this->otp->verify($phone, $request->input('code'));
        if (!$res['ok']) {
            return back()->withErrors(['code' => $res['reason']]);
        }

        // OTP validé → on avance l'état du compte.
        if ($owner->status === CardOwner::STATUS_PENDING_OTP) {
            $owner->status = CardOwner::STATUS_OTP_VERIFIED;
            $owner->save();
        }

        return redirect()->route('pro.kyc.show')
            ->with('status', 'Numéro vérifié. Complète ton dossier pour activer ton accès.');
    }

    /** Renvoi d'un nouveau code (route throttlée). */
    public function resend(Request $request)
    {
        $phone = $request->session()->get('pro.onboarding.phone');
        if (!$phone) {
            return redirect()->route('pro.register.show');
        }

        $res = $this->otp->sendOtp($phone);
        if ($res['cooldown']) {
            return back()->withErrors(['code' => "Patiente {$res['seconds']}s avant de redemander un code."]);
        }
        return back()->with('status', $res['sent']
            ? 'Un nouveau code a été envoyé sur ton WhatsApp.'
            : 'Impossible d\'envoyer le code pour le moment. Réessaie dans un instant.');
    }

    private function mask(string $phone): string
    {
        if (strlen($phone) < 4) return $phone;
        return substr($phone, 0, 5) . str_repeat('•', max(0, strlen($phone) - 7)) . substr($phone, -2);
    }
}
