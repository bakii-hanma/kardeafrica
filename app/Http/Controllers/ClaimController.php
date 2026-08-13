<?php

namespace App\Http\Controllers;

use App\Models\ResellerOrder;
use App\Support\ClientAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Récupération des cartes digitales achetées chez un revendeur.
 *
 * AVANT : `/claim/{token}` était permanent et réutilisable, et le revendeur avait
 * le QR sous les yeux sur son écran de commande. Rien ne l'empêchait de le
 * scanner lui-même — sur des commandes six fois plus grosses qu'une Carte Gabon.
 *
 * MAINTENANT : le lien part sur le WhatsApp du client, expire, et ne s'ouvre
 * qu'une fois. L'ouvrir prouve la possession de la ligne — la même preuve que
 * l'OTP — et connecte donc le client à son compte, où les cartes restent
 * consultables aussi longtemps qu'il le souhaite.
 */
class ClaimController extends Controller
{
    public function show(Request $request, ResellerOrder $order, string $token)
    {
        if ($order->claimed_at !== null) {
            return $this->refus($order, 'deja_ouvert');
        }

        if (! $order->claimTokenMatches($token)) {
            Log::warning('Claim: tentative d\'ouverture d\'un lien invalide ou expiré', [
                'order_id' => $order->id,
                'ip'       => $request->ip(),
            ]);

            return $this->refus($order, $order->claim_expires_at ? 'expire' : 'invalide');
        }

        // Course entre deux ouvertures simultanées : la seconde arrive trop tard.
        if (! $order->consumeClaimLink('whatsapp', $request->ip())) {
            return $this->refus($order, 'deja_ouvert');
        }

        $this->connecterLeClient($request, $order);

        $order->load(['items', 'cards', 'reseller:id,name,vendor_code']);

        return response()
            ->view('claim.show', ['order' => $order, 'connecte' => Auth::check()])
            // Des codes affichés ne doivent rester ni en cache navigateur, ni dans
            // un cache intermédiaire, ni dans un index.
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Ancien format de lien, distribué avant la sécurisation. Ces liens étaient
     * éternels : ils ne doivent plus rien ouvrir, mais méritent une explication
     * plutôt qu'un 404 sec.
     */
    public function legacy(string $token)
    {
        return response()->view('claim.expired', ['order' => null, 'legacy' => true], 410);
    }

    private function connecterLeClient(Request $request, ResellerOrder $order): void
    {
        $client = $order->user;

        if ($client === null || ! $client->is_active) {
            return;
        }

        ClientAccount::markClaimed($client);

        Auth::login($client, remember: true);
        $request->session()->regenerate();
    }

    private function refus(ResellerOrder $order, string $raison)
    {
        return response()
            ->view('claim.expired', ['order' => $order, 'raison' => $raison, 'legacy' => false], 410)
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
