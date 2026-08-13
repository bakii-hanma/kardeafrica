<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\MerchantCard;
use App\Models\MerchantCardPurchase;
use App\Support\MerchantCardCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Vente de cartes locales (Carte Gabon) par un REVENDEUR — activation gated.
 *
 * Flux :
 *  1. `store`  : le revendeur réserve une carte (montant choisi) → purchase
 *     `inactive` + `pending`. Le code existe mais est INERTE (refusé au comptoir).
 *  2. `claim`  : « Récupérer la carte » — action ATOMIQUE qui, dans une seule
 *     transaction verrouillée : débite le wallet du revendeur (montant −
 *     commission revendeur, 4,5 % par défaut), marque payée + vendue
 *     (`sold_by_reseller_at`), active le code, incrémente les stats de la
 *     carte, puis révèle code + PIN. C'est la GARANTIE pour KardAfrica que la
 *     carte a été vendue avant que le code soit actif.
 *  3. `cancel` : abandon d'une réservation non récupérée (jamais débitée).
 *
 * Idempotence : re-cliquer « Récupérer » sur une carte déjà active ne re-débite
 * jamais (garde sous verrou). Un revendeur ne voit/manipule QUE ses ventes.
 */
class LocalCardController extends Controller
{
    /** Catalogue des cartes locales actives + les ventes du revendeur. */
    public function index(Request $request)
    {
        $reseller = Auth::guard('vendor')->user();

        // MÊME filtrage que la vitrine publique /gabon : recherche, catégorie,
        // tranches de prix et tris. Le revendeur vend ces cartes, il doit
        // pouvoir les retrouver comme le fait son client.
        $catalogue = app(\App\Support\LocalCardQuery::class)->payload($request, perPage: 12);

        // L'historique des ventes ne vit plus ici : il a été fusionné avec celui
        // des cartes digitales dans « Mes ventes » (voir VendorSalesFeed). Cet
        // écran redevient ce qu'il doit être : un catalogue.
        return view('vendor.local-cards.index', array_merge($catalogue, [
            'reseller'    => $reseller,
            'salesCount'  => MerchantCardPurchase::where('reseller_id', $reseller->id)->count(),
            'defaultRate' => MerchantCardCode::DEFAULT_RESELLER_RATE,
        ]));
    }

    /** Réserve une carte (naît inactive — aucun débit ici). */
    public function store(Request $request)
    {
        $reseller = Auth::guard('vendor')->user();

        $data = $request->validate([
            'merchant_card_id' => ['required', 'integer', 'exists:merchant_cards,id'],
            'amount'           => ['required', 'numeric', 'min:1'],
            'buyer_name'       => ['nullable', 'string', 'max:150'],
            'buyer_phone'      => ['nullable', 'string', 'max:30'],
        ]);

        $card = MerchantCard::active()->find($data['merchant_card_id']);
        if (!$card) {
            return back()->withErrors(['merchant_card_id' => 'Cette carte n\'est pas (ou plus) disponible à la vente.']);
        }

        try {
            $purchase = MerchantCardCode::createReservationForReseller(
                $card,
                (float) $data['amount'],
                $reseller,
                $data['buyer_name'] ?? null,
                $data['buyer_phone'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => 'Montant non autorisé pour cette carte.']);
        }

        return redirect()
            ->route('vendor.local-cards.show', $purchase)
            ->with('success', 'Carte réservée. Encaissez le client puis « Récupérer la carte » pour activer le code.');
    }

    /** Détail d'une vente. Le code/PIN ne sont visibles qu'une fois la carte active. */
    public function show(MerchantCardPurchase $purchase)
    {
        $reseller = Auth::guard('vendor')->user();
        abort_if($purchase->reseller_id !== $reseller->id, 403);

        $purchase->load('merchantCard:id,name,visual_url,vendor_commission_rate');

        // Montant dû à KardAfrica à la récupération = montant − commission revendeur.
        $due = round((float) $purchase->amount - (float) $purchase->vendor_commission_amount, 2);

        return view('vendor.local-cards.show', [
            'reseller' => $reseller,
            'purchase' => $purchase,
            'due'      => $due,
        ]);
    }

    /**
     * « Récupérer la carte » — débit + preuve de vente + activation, atomique.
     */
    public function claim(MerchantCardPurchase $purchase)
    {
        $reseller = Auth::guard('vendor')->user();
        abort_if($purchase->reseller_id !== $reseller->id, 403);

        try {
            DB::transaction(function () use ($purchase, $reseller) {
                // Verrou pessimiste : deux clics simultanés se sérialisent ici.
                $locked = MerchantCardPurchase::whereKey($purchase->id)->lockForUpdate()->first();

                // Idempotence : déjà récupérée → rien à faire (surtout pas re-débiter).
                if ($locked->status === MerchantCardPurchase::STATUS_ACTIVE
                    && $locked->payment_status === MerchantCardPurchase::PAYMENT_PAID) {
                    return;
                }

                if ($locked->status !== MerchantCardPurchase::STATUS_INACTIVE
                    || $locked->payment_status !== MerchantCardPurchase::PAYMENT_PENDING) {
                    throw new \RuntimeException('Cette vente ne peut plus être récupérée (annulée ou état invalide).');
                }

                // Dû à KardAfrica = montant − commission revendeur (marge conservée
                // par le revendeur qui a encaissé le client au montant plein).
                $due = round((float) $locked->amount - (float) $locked->vendor_commission_amount, 2);

                // Débit wallet (lockForUpdate interne + transaction journalisée).
                // Lève « Solde insuffisant. » → rollback complet, carte reste inactive.
                $reseller->debit(
                    $due,
                    "Carte locale « {$locked->merchantCard?->name} » #{$locked->id} (montant {$locked->amount} − commission)",
                    'local-card-' . $locked->id,
                );

                $locked->update([
                    'payment_status'      => MerchantCardPurchase::PAYMENT_PAID,
                    'payment_ref'         => 'local-card-' . $locked->id,
                    'status'              => MerchantCardPurchase::STATUS_ACTIVE,
                    'paid_at'             => now(),
                    'delivered_at'        => now(),
                    'sold_by_reseller_at' => now(),
                ]);

                // Stats de la carte — uniquement à la vente PROUVÉE.
                $locked->merchantCard?->increment('total_sold');
                $locked->merchantCard?->increment('total_revenue', (float) $locked->amount);

                Log::info('LocalCard: carte récupérée par le revendeur (activée + débitée)', [
                    'purchase_id' => $locked->id,
                    'reseller_id' => $reseller->id,
                    'amount'      => (float) $locked->amount,
                    'debited'     => $due,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['claim' => $e->getMessage()]);
        }

        // Notification WhatsApp au PROPRIÉTAIRE de la carte — hors transaction
        // (un échec d'envoi ne doit jamais annuler le débit). Dédupliquée.
        $this->notifyOwnerOfSale($purchase->fresh(['merchantCard.owner']), $reseller);

        return redirect()
            ->route('vendor.local-cards.show', $purchase)
            ->with('success', 'Carte récupérée et activée — remettez le code au client.');
    }

    /**
     * Prévient le propriétaire qu'une de ses cartes vient d'être vendue au
     * comptoir (montant + sa part nette + revendeur). Jamais de code/PIN.
     */
    /**
     * Envoie au client un lien à usage unique vers son code.
     *
     * Le message ne contient JAMAIS le code ni le PIN : un message WhatsApp est
     * hébergé chez Meta, sauvegardé en clair dans la plupart des sauvegardes
     * téléphone, et transférable. Il ne porte qu'un lien qui expire en 30 min et
     * ne s'ouvre qu'une fois.
     */
    public function sendCode(Request $request, MerchantCardPurchase $purchase)
    {
        $reseller = Auth::guard('vendor')->user();
        abort_if($purchase->reseller_id !== $reseller->id, 403);

        if ($purchase->status !== MerchantCardPurchase::STATUS_ACTIVE) {
            return back()->withErrors(['code' => 'La carte doit d\'abord être récupérée et activée.']);
        }

        if ($purchase->isRevealed()) {
            return back()->withErrors(['code' => 'Le client a déjà consulté son code : il ne peut plus être réaffiché.']);
        }

        if ($purchase->reveal_sends >= MerchantCardPurchase::REVEAL_MAX_SENDS) {
            return back()->withErrors(['code' => 'Limite d\'envois atteinte. Contacte le support pour débloquer cette vente.']);
        }

        $request->validate([
            'buyer_phone_country'  => ['nullable', 'string', 'size:2'],
            'buyer_phone_national' => ['required_without:buyer_phone', 'nullable', 'string', 'max:30'],
            'buyer_phone'          => ['required_without:buyer_phone_national', 'nullable', 'string', 'max:30'],
        ]);

        // Le pays est choisi explicitement à la saisie : recomposé ici, le
        // numéro n'a plus rien d'ambigu — ni pour WHAPI, ni comme clé de compte.
        $phone = \App\Support\PhoneInput::fromRequest($request, 'buyer_phone');

        if ($phone === null) {
            return back()->withErrors(['code' => 'Numéro incomplet : vérifie l\'indicatif et le numéro.']);
        }

        // Garde-fou anti-détournement : le revendeur ne doit pas pouvoir se
        // faire envoyer le code à lui-même. C'est le contournement évident du
        // dispositif, et il est silencieux sans ce contrôle.
        if (\App\Support\Phone::sameLine($phone, $reseller->phone)) {
            Log::warning('CarteGabon: tentative d\'envoi du code au revendeur lui-même', [
                'purchase_id' => $purchase->id,
                'reseller_id' => $reseller->id,
            ]);

            return back()->withErrors([
                'code' => 'Ce numéro est le tien. Le code doit partir sur le téléphone du client.',
            ]);
        }

        // Le compte du client est ouvert maintenant, pas à l'ouverture du lien :
        // la carte doit déjà y être quand il arrive. Un numéro trop ambigu pour
        // servir de clé n'empêche pas la vente — la carte reste simplement non
        // rattachée, et le client la récupérera en se connectant plus tard.
        $client = \App\Support\ClientAccount::findOrCreate(
            $phone,
            $purchase->buyer_name,
            \App\Support\ClientAccount::VIA_COUNTER,
        );

        if ($client) {
            $purchase->forceFill(['user_id' => $client->id])->save();
        }

        $token = $purchase->issueRevealToken();
        $lien  = route('card.reveal', ['purchase' => $purchase->id, 'token' => $token]);

        $message = app(\App\Services\WhatsAppNotifier::class)->text(
            $phone,
            "🎁 *KardAfrica* — ta carte « {$purchase->merchantCard?->name} »\n\n"
            . 'Montant : *' . number_format((float) $purchase->amount, 0, ',', ' ') . " FCFA*\n\n"
            . "Ouvre ce lien pour voir ton code et ton PIN :\n{$lien}\n\n"
            . '⚠️ Le lien expire dans ' . MerchantCardPurchase::REVEAL_TTL_MINUTES
            . " minutes et ne s'ouvre qu'*une seule fois*. Fais une capture d'écran.",
            [
                'category'  => \App\Models\WhatsAppMessage::CAT_TRANSACTIONAL,
                'dedup_key' => "local-reveal-{$purchase->id}-{$purchase->reveal_sends}",
                'context'   => ['merchant_card_reveal', $purchase->id],
            ],
        );

        if ($message === null) {
            return back()->withErrors([
                'code' => "L'envoi WhatsApp a échoué. Vérifie le numéro, ou utilise l'affichage au comptoir.",
            ]);
        }

        $purchase->forceFill([
            'buyer_phone'    => $purchase->buyer_phone ?: $phone,
            'reveal_sent_at' => now(),
            'reveal_sent_to' => $phone,
            'reveal_sends'   => $purchase->reveal_sends + 1,
        ])->save();

        return back()->with('success', 'Lien envoyé sur le WhatsApp du client. Il expire dans '
            . MerchantCardPurchase::REVEAL_TTL_MINUTES . ' minutes.');
    }

    /**
     * Repli : le client n'a pas WhatsApp. Le code s'affiche une seule fois sur
     * l'appareil du revendeur, tourné vers le client.
     *
     * Ce chemin réintroduit sciemment le risque que le dispositif écarte — il
     * existe parce que sans lui, un client sans WhatsApp ne peut pas être servi.
     * Il est donc journalisé, compté, et distingué du canal normal.
     */
    public function revealHere(MerchantCardPurchase $purchase)
    {
        $reseller = Auth::guard('vendor')->user();
        abort_if($purchase->reseller_id !== $reseller->id, 403);

        if ($purchase->status !== MerchantCardPurchase::STATUS_ACTIVE) {
            return back()->withErrors(['code' => 'La carte doit d\'abord être récupérée et activée.']);
        }

        $secret = $purchase->revealOnce('comptoir', request()->ip());

        if ($secret === null) {
            return back()->withErrors(['code' => 'Ce code a déjà été affiché : il ne peut plus être réaffiché.']);
        }

        Log::warning('CarteGabon: code affiché au comptoir (canal de repli)', [
            'purchase_id' => $purchase->id,
            'reseller_id' => $reseller->id,
            'amount'      => (float) $purchase->amount,
        ]);

        // Le secret ne transite qu'en session flash : il n'est jamais réécrit en base.
        return back()->with('secret_once', $secret);
    }


    private function notifyOwnerOfSale(MerchantCardPurchase $purchase, $reseller): void
    {
        $owner = $purchase->merchantCard?->owner;
        $phone = $owner?->whatsapp_number ?: $owner?->phone;
        if (empty($phone)) {
            return;
        }

        $amount = number_format((float) $purchase->amount, 0, ',', ' ');
        $net    = number_format((float) $purchase->owner_net_amount, 0, ',', ' ');

        app(\App\Services\WhatsAppNotifier::class)->text(
            $phone,
            "💳 *KardAfrica* — nouvelle vente !\n\n"
            . "Votre carte « {$purchase->merchantCard->name} » vient d'être vendue au comptoir "
            . "par {$reseller->name} ({$reseller->vendor_code}).\n\n"
            . "Montant : *{$amount} FCFA*\nVotre part nette : *{$net} FCFA*\n\n"
            . "Le client la présentera chez vous avec son code + PIN.",
            [
                'category'  => \App\Models\WhatsAppMessage::CAT_TRANSACTIONAL,
                'dedup_key' => "local-sale-owner-{$purchase->id}",
                'context'   => ['merchant_card_sale', $purchase->id],
            ],
        );
    }

    /** Annule une réservation jamais récupérée (aucun débit n'a eu lieu). */
    public function cancel(MerchantCardPurchase $purchase)
    {
        $reseller = Auth::guard('vendor')->user();
        abort_if($purchase->reseller_id !== $reseller->id, 403);

        $updated = MerchantCardPurchase::whereKey($purchase->id)
            ->where('status', MerchantCardPurchase::STATUS_INACTIVE)
            ->where('payment_status', MerchantCardPurchase::PAYMENT_PENDING)
            ->update(['status' => MerchantCardPurchase::STATUS_CANCELLED]);

        return redirect()
            ->route('vendor.local-cards.index')
            ->with($updated ? 'success' : 'error', $updated
                ? 'Réservation annulée.'
                : 'Impossible d\'annuler : la carte a déjà été récupérée.');
    }
}
