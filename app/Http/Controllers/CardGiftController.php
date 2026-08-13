<?php

namespace App\Http\Controllers;

use App\Models\MerchantCardPurchase;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppNotifier;
use App\Support\ClientAccount;
use App\Support\Phone;
use App\Support\PhoneInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Offrir une Carte Gabon à quelqu'un.
 *
 * Une carte au porteur se donne déjà en dictant son code — mais le donneur garde
 * alors le sien, et personne ne sait qui la détient. Le transfert explicite
 * change de titulaire pour de bon : la carte quitte le compte du donneur, et le
 * destinataire la reçoit sur son propre WhatsApp, par le même lien à usage unique
 * que pour un achat.
 *
 * Rien n'est copié : c'est un déplacement. Une carte offerte disparaît de « mes
 * cartes » chez le donneur — sinon deux personnes croiraient détenir le même
 * solde et l'une des deux se présenterait pour rien chez le commerçant.
 */
class CardGiftController extends Controller
{
    public function store(Request $request, MerchantCardPurchase $purchase)
    {
        $donneur = Auth::user();

        abort_if($purchase->user_id !== $donneur->id, 403);

        $request->validate([
            'recipient_phone_country'  => ['nullable', 'string', 'size:2'],
            'recipient_phone_national' => ['required', 'string', 'max:30'],
            'recipient_name'           => ['nullable', 'string', 'max:150'],
        ]);

        if ($erreur = $this->raisonDeRefus($purchase)) {
            return back()->withErrors(['gift' => $erreur]);
        }

        // Sélecteur resté sur Gabon avec un numéro étranger : le numéro composé
        // serait un gabonais qui n'existe pas, et la carte partirait dans le vide.
        if (PhoneInput::tooLongForCountry($request, 'recipient_phone')) {
            return back()->withErrors([
                'gift' => 'Ce numéro est trop long pour le pays choisi. Vérifie l\'indicatif.',
            ]);
        }

        $phone = PhoneInput::accountKeyFromRequest($request, 'recipient_phone');

        if ($phone === null) {
            return back()->withErrors([
                'gift' => 'Numéro incomplet ou ambigu. Vérifie l\'indicatif du pays et le numéro.',
            ]);
        }

        // Comparaison STRICTE, pas la permissive `sameLine` : ici la question est
        // l'identité, pas la fraude. S'offrir sa propre carte est inoffensif —
        // refuser à tort un cadeau légitime, non.
        if (Phone::same($phone, $donneur->phone)) {
            return back()->withErrors(['gift' => 'C\'est ton propre numéro : la carte est déjà à toi.']);
        }

        $beneficiaire = ClientAccount::findOrCreate(
            $phone,
            $request->input('recipient_name'),
            ClientAccount::VIA_ONLINE,
        );

        if ($beneficiaire === null) {
            return back()->withErrors(['gift' => 'Ce numéro ne permet pas d\'ouvrir un compte.']);
        }

        $token = DB::transaction(function () use ($purchase, $beneficiaire, $donneur) {
            /** @var MerchantCardPurchase $verrou */
            $verrou = MerchantCardPurchase::whereKey($purchase->getKey())->lockForUpdate()->first();

            // Deux envois simultanés : le second ne doit pas offrir une carte
            // qui a déjà changé de mains.
            if ($verrou->user_id !== $donneur->id) {
                return null;
            }

            $verrou->forceFill([
                'user_id'     => $beneficiaire->id,
                'buyer_name'  => $beneficiaire->name,
                'buyer_phone' => $beneficiaire->phone,
                // Le destinataire doit pouvoir ouvrir son propre lien : la remise
                // précédente ne le concerne pas.
                'revealed_at' => null,
            ])->save();

            $purchase->setRawAttributes($verrou->getAttributes(), true);

            return $verrou->issueRevealToken();
        });

        if ($token === null) {
            return back()->withErrors(['gift' => 'Cette carte a déjà été transférée.']);
        }

        $lien = route('card.reveal', ['purchase' => $purchase->id, 'token' => $token]);

        app(WhatsAppNotifier::class)->text(
            $phone,
            "🎁 *KardAfrica* — on t'offre une carte !\n\n"
            . ($donneur->name ? "{$donneur->name} t'offre " : "Tu as reçu ")
            . 'une carte « ' . ($purchase->merchantCard?->name ?? 'Carte Gabon') . " »\n"
            . 'Montant : *' . number_format((float) $purchase->remaining_balance, 0, ',', ' ') . " FCFA*\n\n"
            . "Ouvre ce lien pour voir ton code :\n{$lien}\n\n"
            . '⚠️ Le lien expire dans ' . MerchantCardPurchase::REVEAL_TTL_MINUTES . ' minutes.',
            [
                'category'  => WhatsAppMessage::CAT_TRANSACTIONAL,
                'dedup_key' => "card-gift-{$purchase->id}-{$beneficiaire->id}",
                'context'   => ['merchant_card_gift', $purchase->id],
            ],
        );

        Log::info('CarteGabon: carte offerte', [
            'purchase_id' => $purchase->id,
            'de'          => $donneur->id,
            'vers'        => $beneficiaire->id,
        ]);

        return back()->with('success',
            'Carte offerte ! ' . Phone::masked($phone) . ' vient de recevoir le lien sur WhatsApp.');
    }

    /** Pourquoi cette carte ne peut pas être offerte, ou null si elle le peut. */
    private function raisonDeRefus(MerchantCardPurchase $purchase): ?string
    {
        if ((float) $purchase->remaining_balance <= 0) {
            return 'Cette carte est épuisée : il n\'y a plus rien à offrir.';
        }

        if ($purchase->expires_at?->isPast()) {
            return 'Cette carte a expiré.';
        }

        if ($purchase->status !== MerchantCardPurchase::STATUS_ACTIVE
            && $purchase->status !== MerchantCardPurchase::STATUS_PARTIALLY_USED) {
            return 'Cette carte n\'est pas dans un état qui permet de l\'offrir.';
        }

        if (! $purchase->secretIsReadable()) {
            return 'Le code de cette carte n\'est plus lisible : elle ne peut pas être transférée.';
        }

        return null;
    }
}
