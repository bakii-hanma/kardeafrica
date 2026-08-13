<?php

namespace App\Http\Controllers;

use App\Models\MerchantCardPurchase;
use App\Support\ClientAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Révélation du code d'une Carte Gabon au client, une seule fois.
 *
 * Le revendeur ne voit plus le code ni le PIN : il envoie un lien au client, qui
 * l'ouvre sur SON téléphone. Le secret n'existe plus après cet affichage — le PIN
 * en clair est effacé, seul son condensat subsiste pour le contrôle au comptoir
 * du commerçant.
 *
 * La page est volontairement publique : le lien EST le secret. Il est à usage
 * unique, expire en 30 minutes, et seul son condensat est stocké — lire la base
 * ne permet pas de l'ouvrir.
 */
class CardRevealController extends Controller
{
    public function show(Request $request, MerchantCardPurchase $purchase, string $token)
    {
        // Déjà ouvert : on ne rejoue jamais un secret, même au bon porteur.
        if ($purchase->isRevealed()) {
            return $this->refus($purchase, 'deja_vu');
        }

        if (!$purchase->revealTokenMatches($token)) {
            Log::warning('CarteGabon: tentative d\'ouverture d\'un lien invalide ou expiré', [
                'purchase_id' => $purchase->id,
                'ip'          => $request->ip(),
            ]);

            return $this->refus($purchase, $purchase->reveal_expires_at ? 'expire' : 'invalide');
        }

        $secret = $purchase->revealOnce('whatsapp', $request->ip());

        // Course entre deux ouvertures simultanées : la seconde arrive trop tard.
        if ($secret === null) {
            return $this->refus($purchase, 'deja_vu');
        }

        // Ouvrir ce lien prouve la possession du numéro WhatsApp auquel il a été
        // envoyé — la même preuve que l'OTP. Le client est donc connecté à son
        // compte, où la carte l'attend déjà : c'est ce qui rend la promesse
        // « ta carte reste dans ton compte » tenable dès le premier contact.
        $this->connecterLeClient($request, $purchase);

        return response()
            ->view('cards.reveal', [
                'purchase' => $purchase->load('merchantCard:id,name,visual_url'),
                'code'     => $secret['code'],
                'pin'      => $secret['pin'],
                'connecte' => Auth::check(),
            ])
            // Un secret affiché une fois ne doit rester ni en cache navigateur,
            // ni dans un cache intermédiaire, ni dans l'historique de retour.
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Connecte le titulaire du numéro à son compte, si la carte y est rattachée.
     * Silencieux en cas d'absence de compte : la remise du code prime, elle ne
     * doit jamais échouer à cause de l'authentification.
     */
    private function connecterLeClient(Request $request, MerchantCardPurchase $purchase): void
    {
        $client = $purchase->user;

        if ($client === null || ! $client->is_active) {
            return;
        }

        ClientAccount::markClaimed($client);

        Auth::login($client, remember: true);
        $request->session()->regenerate();
    }

    private function refus(MerchantCardPurchase $purchase, string $raison)
    {
        return response()
            ->view('cards.reveal-refus', [
                'purchase' => $purchase->load('merchantCard:id,name'),
                'raison'   => $raison,
            ], 410)
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
