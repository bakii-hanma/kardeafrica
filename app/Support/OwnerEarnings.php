<?php

namespace App\Support;

use App\Models\CardOwner;
use App\Models\MerchantCardPurchase;
use App\Models\MerchantCardRedemption;
use App\Models\MerchantSettlement;
use Illuminate\Support\Carbon;

/**
 * OwnerEarnings
 * =============
 * Source unique des chiffres d'un commerçant Carte Gabon.
 *
 * QUAND LE COMMERÇANT EST-IL PAYÉ ?
 * ---------------------------------
 * **Un versement par semaine, le lundi suivant l'achat de la carte.** Le
 * déclencheur est donc la VENTE, pas la consommation : le commerçant est réglé
 * même si le client n'est pas encore passé chercher sa marchandise.
 *
 * C'est un choix commercial assumé — encaisser vite est ce qui décide un
 * restaurateur à publier une carte — et il déplace le risque sur KardAfrica :
 * une carte remboursée après règlement devra être reprise sur un versement
 * ultérieur.
 *
 * Une vente du mardi et une vente du dimanche partent donc au même versement,
 * celui du lundi qui suit. Une vente faite un lundi attend le lundi d'après :
 * « le lundi SUIVANT l'achat ».
 *
 * Trois montants restent distincts, là où l'écran n'en montrait qu'un :
 *  — `grossSold()` : ce que les CLIENTS ont payé, que le commerçant ne touche
 *    jamais ;
 *  — `dueNet()` : ce qui est exigible, versements de lundi déjà échus ;
 *  — `upcomingNet()` : ce qui partira au prochain lundi.
 *
 * `remaining_balance` cumulé n'est PAS un revenu : c'est la **dette** du
 * commerçant, la marchandise qu'il doit encore servir. `liability()` la nomme
 * pour ce qu'elle est.
 */
class OwnerEarnings
{
    public function __construct(private CardOwner $owner) {}

    public static function for(CardOwner $owner): self
    {
        return new self($owner);
    }

    /** Identifiants des cartes du commerçant, orphelines comprises. */
    private function cardIds()
    {
        return $this->owner->cards()->pluck('id');
    }

    // ------------------------------------------------------------------
    // Ventes
    // ------------------------------------------------------------------

    private function paidPurchases(?Carbon $from = null, ?Carbon $to = null)
    {
        $q = MerchantCardPurchase::whereIn('merchant_card_id', $this->cardIds())
            ->where('payment_status', MerchantCardPurchase::PAYMENT_PAID);

        if ($from) $q->where('paid_at', '>=', $from);
        if ($to)   $q->where('paid_at', '<=', $to);

        return $q;
    }

    /** Ce que les CLIENTS ont payé. Le commerçant n'y touche jamais directement. */
    public function grossSold(?Carbon $from = null, ?Carbon $to = null): float
    {
        return (float) $this->paidPurchases($from, $to)->sum('amount');
    }

    /** Sa part : 85 % du montant encaissé, tous canaux confondus. */
    public function earnedNet(?Carbon $from = null, ?Carbon $to = null): float
    {
        return (float) $this->paidPurchases($from, $to)->sum('owner_net_amount');
    }

    public function salesCount(?Carbon $from = null, ?Carbon $to = null): int
    {
        return $this->paidPurchases($from, $to)->count();
    }

    // ------------------------------------------------------------------
    // Consommation au comptoir
    // ------------------------------------------------------------------

    private function redemptions(?Carbon $from = null, ?Carbon $to = null)
    {
        $ids = $this->cardIds();

        $q = MerchantCardRedemption::whereHas('purchase', fn ($x) => $x->whereIn('merchant_card_id', $ids));

        if ($from) $q->where('redeemed_at', '>=', $from);
        if ($to)   $q->where('redeemed_at', '<=', $to);

        return $q;
    }

    /** Valeur faciale réellement servie au comptoir. */
    public function redeemedGross(?Carbon $from = null, ?Carbon $to = null): float
    {
        return (float) $this->redemptions($from, $to)->sum('amount_used');
    }

    public function redemptionsCount(?Carbon $from = null, ?Carbon $to = null): int
    {
        return $this->redemptions($from, $to)->count();
    }

    // ------------------------------------------------------------------
    // Échéancier : un versement par semaine, le lundi suivant l'achat
    // ------------------------------------------------------------------

    /** Date de versement d'un achat : le lundi qui suit strictement sa date. */
    public static function payoutDateFor(Carbon $paidAt): Carbon
    {
        return $paidAt->copy()->startOfDay()->next(Carbon::MONDAY);
    }

    /**
     * Dernier lundi échu, bornant ce qui est exigible aujourd'hui.
     * Un achat est réglé si un lundi s'est écoulé depuis : sa date doit donc
     * être antérieure à ce lundi.
     */
    private function derniereEcheance(): Carbon
    {
        $aujourdhui = today();

        return $aujourdhui->isMonday()
            ? $aujourdhui->copy()
            : $aujourdhui->copy()->previous(Carbon::MONDAY);
    }

    /** Prochain lundi de versement. */
    public function nextPayoutDate(): Carbon
    {
        return today()->next(Carbon::MONDAY);
    }

    /**
     * Ce qui est exigible : la part nette des ventes dont le lundi de versement
     * est passé.
     */
    public function dueNet(): float
    {
        return (float) $this->paidPurchases()
            ->whereDate('paid_at', '<', $this->derniereEcheance())
            ->sum('owner_net_amount');
    }

    /** Ce qui partira au prochain lundi — les ventes de la semaine en cours. */
    public function upcomingNet(): float
    {
        return (float) $this->paidPurchases()
            ->whereDate('paid_at', '>=', $this->derniereEcheance())
            ->sum('owner_net_amount');
    }

    /**
     * Récapitulatif du lundi : tous les commerçants à payer, en une requête.
     *
     * Calculer `outstanding()` commerçant par commerçant ferait deux requêtes
     * chacun — sur une place de marché, la liste du lundi doit rester tenable.
     * Les deux agrégats sont donc joints en sous-requêtes.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function payoutRun(bool $onlyDue = true): \Illuminate\Support\Collection
    {
        $echeance = today()->isMonday() ? today() : today()->copy()->previous(Carbon::MONDAY);

        $exigible = \DB::table('merchant_card_purchases as p')
            ->join('merchant_cards as mc', 'mc.id', '=', 'p.merchant_card_id')
            ->where('p.payment_status', MerchantCardPurchase::PAYMENT_PAID)
            ->whereDate('p.paid_at', '<', $echeance)
            ->groupBy('mc.card_owner_id')
            ->select('mc.card_owner_id', \DB::raw('SUM(p.owner_net_amount) as total'));

        $aVenir = \DB::table('merchant_card_purchases as p')
            ->join('merchant_cards as mc', 'mc.id', '=', 'p.merchant_card_id')
            ->where('p.payment_status', MerchantCardPurchase::PAYMENT_PAID)
            ->whereDate('p.paid_at', '>=', $echeance)
            ->groupBy('mc.card_owner_id')
            ->select('mc.card_owner_id', \DB::raw('SUM(p.owner_net_amount) as total'));

        $verse = \DB::table('merchant_settlements')
            ->groupBy('card_owner_id')
            ->select('card_owner_id',
                \DB::raw('SUM(amount) as total'),
                \DB::raw('MAX(settled_at) as dernier'));

        $lignes = \DB::table('card_owners as o')
            ->leftJoinSub($exigible, 'du', 'du.card_owner_id', '=', 'o.id')
            ->leftJoinSub($aVenir, 'av', 'av.card_owner_id', '=', 'o.id')
            ->leftJoinSub($verse, 'v', 'v.card_owner_id', '=', 'o.id')
            ->select(
                'o.id', 'o.business_name', 'o.contact_name', 'o.phone', 'o.city',
                \DB::raw('COALESCE(du.total, 0) as exigible'),
                \DB::raw('COALESCE(av.total, 0) as a_venir'),
                \DB::raw('COALESCE(v.total, 0) as verse'),
                \DB::raw('COALESCE(du.total, 0) - COALESCE(v.total, 0) as solde'),
                'v.dernier as dernier_versement',
            )
            ->orderByDesc('solde')
            ->get();

        // Un solde nul ou négatif n'a rien à faire dans une liste de paiements :
        // il ferait douter de tous les autres.
        return $onlyDue ? $lignes->filter(fn ($l) => (float) $l->solde > 0)->values() : $lignes;
    }

    // ------------------------------------------------------------------
    // Reversements
    // ------------------------------------------------------------------

    public function settled(?Carbon $from = null, ?Carbon $to = null): float
    {
        $q = MerchantSettlement::where('card_owner_id', $this->owner->id);

        if ($from) $q->where('settled_at', '>=', $from);
        if ($to)   $q->where('settled_at', '<=', $to);

        return (float) $q->sum('amount');
    }

    /** Solde à recevoir : exigible moins déjà versé. Peut être négatif (avance). */
    public function outstanding(): float
    {
        return round($this->dueNet() - $this->settled(), 2);
    }

    // ------------------------------------------------------------------
    // Engagement du commerçant
    // ------------------------------------------------------------------

    /**
     * Marchandise que le commerçant doit encore servir.
     * Ce n'est PAS un revenu : l'ancien écran l'affichait dans le même langage
     * visuel que les montants gagnés.
     */
    public function liability(): float
    {
        return (float) MerchantCardPurchase::whereIn('merchant_card_id', $this->cardIds())
            ->where('payment_status', MerchantCardPurchase::PAYMENT_PAID)
            ->whereIn('status', [
                MerchantCardPurchase::STATUS_ACTIVE,
                MerchantCardPurchase::STATUS_PARTIALLY_USED,
            ])
            ->sum('remaining_balance');
    }

    // ------------------------------------------------------------------
    // Séries pour les graphiques
    // ------------------------------------------------------------------

    /**
     * Ce qui a été servi au comptoir jour par jour.
     * Les jours creux sont présents à 0 : les omettre comprimerait le temps et
     * donnerait une fausse impression de régularité.
     *
     * @return array<int, array{date:Carbon, day:string, label:string, amount:float, today:bool}>
     */
    public function dailyRedeemed(int $days = 14): array
    {
        $start = today()->copy()->subDays($days - 1);

        $lignes = $this->redemptions($start->copy()->startOfDay())->get(['redeemed_at', 'amount_used']);

        $jours = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
        $out   = [];

        for ($i = 0; $i < $days; $i++) {
            $jour = $start->copy()->addDays($i);
            $cle  = $jour->toDateString();

            $out[] = [
                'date'   => $jour,
                'day'    => $jour->format('j'),
                // Construit ici : la locale applicative est `en`, translatedFormat
                // rendrait des jours anglais dans une interface française.
                'label'  => $jours[$jour->dayOfWeek] . ' ' . $jour->format('j'),
                'amount' => (float) $lignes
                    ->filter(fn ($r) => $r->redeemed_at?->toDateString() === $cle)
                    ->sum('amount_used'),
                'today'  => $jour->isToday(),
            ];
        }

        return $out;
    }

    /**
     * Cartes qui rapportent le plus, mesurées sur ce qui est SERVI — pas sur ce
     * qui est vendu. Une carte très vendue mais jamais consommée ne dit rien de
     * l'activité du comptoir.
     *
     * @return array<int, array{name:string, amount:float, count:int}>
     */
    public function topCards(int $limit = 5): array
    {
        $totaux = [];

        $this->redemptions()
            ->with('purchase.merchantCard:id,name')
            ->chunk(500, function ($lot) use (&$totaux) {
                foreach ($lot as $debit) {
                    $nom = $debit->purchase?->merchantCard?->name ?: 'Carte';
                    $totaux[$nom] ??= ['name' => $nom, 'amount' => 0.0, 'count' => 0];
                    $totaux[$nom]['amount'] += (float) $debit->amount_used;
                    $totaux[$nom]['count']++;
                }
            });

        usort($totaux, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        return array_slice(array_values($totaux), 0, $limit);
    }

    /**
     * Vue d'ensemble du tableau de bord.
     *
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $aujourdhui = today()->startOfDay();

        return [
            'cards_total'    => $this->cardIds()->count(),
            'cards_active'   => $this->owner->cards()->where('is_active', true)->count(),

            'sales_count'    => $this->salesCount(),
            'gross_sold'     => $this->grossSold(),
            'earned_net'     => $this->earnedNet(),

            'redeemed_gross' => $this->redeemedGross(),
            'redeem_count'   => $this->redemptionsCount(),
            'redeemed_today' => $this->redeemedGross($aujourdhui),
            'redeem_today'   => $this->redemptionsCount($aujourdhui),

            'due_net'        => $this->dueNet(),
            'upcoming_net'   => $this->upcomingNet(),
            'next_payout'    => $this->nextPayoutDate(),
            'settled'        => $this->settled(),
            'outstanding'    => $this->outstanding(),

            'liability'      => $this->liability(),

            'daily'          => $this->dailyRedeemed(14),
            'top_cards'      => $this->topCards(5),
        ];
    }
}
