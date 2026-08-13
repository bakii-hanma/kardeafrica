<?php

namespace App\Support;

use App\Models\MerchantCardPurchase;
use App\Models\Order;
use App\Models\Reseller;
use App\Models\ResellerOrder;
use Illuminate\Support\Carbon;

/**
 * VendorStats
 * ===========
 * Source unique des chiffres de l'espace vendeur.
 *
 * Écrite pour corriger trois défauts constatés à l'audit :
 *
 *  1. les commissions étaient sommées SANS filtrer le statut — un panier
 *     E-Billing abandonné ou une commande remboursée gonflait le total ;
 *  2. « volume cumulé » avait deux définitions contradictoires : somme des
 *     commandes livrées côté dashboard, colonne `total_volume` côté profil —
 *     laquelle n'est jamais décrémentée au remboursement ;
 *  3. les ventes de Cartes Gabon étaient totalement absentes : un revendeur
 *     qui ne fait que du comptoir voyait un tableau de bord à zéro.
 *
 * Règles retenues, valables partout :
 *  — le VOLUME ne compte que ce qui a été livré : commandes `completed` +
 *    cartes locales réellement récupérées (donc payées et activées) ;
 *  — la COMMISSION digitale ne compte que les commandes `completed`. Elle est
 *    tenue séparée de la marge sur cartes locales : la première est créditée au
 *    portefeuille de commissions, la seconde est encaissée en espèces par le
 *    revendeur (il ne reverse que le montant net). Les additionner n'aurait
 *    aucun sens comptable.
 */
class VendorStats
{
    /** Initiales des jours, dimanche = 0 (ordre de Carbon::dayOfWeek). */
    private const JOURS = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];

    public function __construct(private Reseller $reseller) {}

    public static function for(Reseller $reseller): self
    {
        return new self($reseller);
    }

    // ------------------------------------------------------------------
    // Volume
    // ------------------------------------------------------------------

    /** Chiffre d'affaires livré sur une période (null = depuis toujours). */
    public function volume(?Carbon $from = null, ?Carbon $to = null): float
    {
        return $this->ordersVolume($from, $to) + $this->localVolume($from, $to);
    }

    /** Nombre de ventes conclues sur une période (digitales + Carte Gabon). */
    public function salesCount(?Carbon $from = null, ?Carbon $to = null): int
    {
        return $this->ordersQuery(ResellerOrder::STATUS_COMPLETED, $from, $to)->count()
            + $this->localQuery($from, $to)->count();
    }

    private function ordersVolume(?Carbon $from, ?Carbon $to): float
    {
        return (float) $this->ordersQuery(ResellerOrder::STATUS_COMPLETED, $from, $to)->sum('total_amount');
    }

    private function localVolume(?Carbon $from, ?Carbon $to): float
    {
        return (float) $this->localQuery($from, $to)->sum('amount');
    }

    // ------------------------------------------------------------------
    // Commissions — les deux natures restent distinctes
    // ------------------------------------------------------------------

    /** Commission sur cartes digitales : commandes LIVRÉES uniquement. */
    public function digitalCommission(?Carbon $from = null, ?Carbon $to = null): float
    {
        return (float) $this->ordersQuery(ResellerOrder::STATUS_COMPLETED, $from, $to)->sum('commission_earned');
    }

    /** Marge conservée sur les Cartes Gabon récupérées au comptoir. */
    public function localCommission(?Carbon $from = null, ?Carbon $to = null): float
    {
        return (float) $this->localQuery($from, $to)->sum('vendor_commission_amount');
    }

    // ------------------------------------------------------------------
    // Comptages d'état
    // ------------------------------------------------------------------

    /** Commandes dont le paiement n'est pas confirmé. */
    public function awaitingPayment(): int
    {
        return $this->reseller->orders()->where('status', ResellerOrder::STATUS_PENDING)->count();
    }

    /** Commandes payées, en cours de livraison. */
    public function delivering(): int
    {
        return $this->reseller->orders()->where('status', ResellerOrder::STATUS_PROCESSING)->count();
    }

    /** Total de commandes digitales, tous statuts (indicateur d'activité). */
    public function ordersTotal(): int
    {
        return $this->reseller->orders()->count();
    }

    // ------------------------------------------------------------------
    // Alertes actionnables
    // ------------------------------------------------------------------

    /**
     * Ce qui demande une action du revendeur, dans l'ordre d'urgence.
     * Liste vide = rien à signaler (le bloc n'est alors pas affiché).
     *
     * @return array<int, array{key:string,label:string,url:string,tone:string,count:int}>
     */
    public function alerts(): array
    {
        $out = [];

        // 1. Commandes du site à encaisser — un client attend au comptoir.
        $cashToCollect = Order::where('cash_reseller_id', $this->reseller->id)
            ->where('payment_method', Order::PAYMENT_METHOD_CASH_RESELLER)
            ->where('payment_status', Order::PAYMENT_STATUS_PENDING)
            ->count();
        if ($cashToCollect > 0) {
            $out[] = [
                'key'   => 'cash_to_collect',
                'count' => $cashToCollect,
                'label' => $cashToCollect . ' client' . ($cashToCollect > 1 ? 's' : '')
                    . ' à encaisser au comptoir',
                'url'   => route('vendor.cash.index'),
                'tone'  => 'urgent',
            ];
        }

        // 2. Livraisons en échec — le client a payé, la carte n'est pas partie.
        $failed = $this->reseller->orders()->where('status', ResellerOrder::STATUS_FAILED)->count();
        if ($failed > 0) {
            $out[] = [
                'key'   => 'failed',
                'count' => $failed,
                'label' => $failed . ' livraison' . ($failed > 1 ? 's' : '') . ' en échec à relancer',
                'url'   => route('vendor.orders', ['status' => 'failed']),
                'tone'  => 'urgent',
            ];
        }

        // 3. Ventes cash à confirmer avant expiration du blocage de fonds.
        $expiring = $this->reseller->orders()
            ->where('payment_method', 'cash')
            ->where('status', ResellerOrder::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->count();
        if ($expiring > 0) {
            $out[] = [
                'key'   => 'cash_expiring',
                'count' => $expiring,
                'label' => $expiring . ' vente' . ($expiring > 1 ? 's' : '')
                    . ' en espèces à confirmer avant expiration',
                'url'   => route('vendor.orders', ['status' => 'pending']),
                'tone'  => 'warn',
            ];
        }

        // 4. Cartes locales réservées mais jamais récupérées : le code reste
        //    inerte et la vente n'est pas acquise.
        $unclaimed = MerchantCardPurchase::where('reseller_id', $this->reseller->id)
            ->whereNull('sold_by_reseller_at')
            ->count();
        if ($unclaimed > 0) {
            $out[] = [
                'key'   => 'unclaimed',
                'count' => $unclaimed,
                'label' => $unclaimed . ' carte' . ($unclaimed > 1 ? 's' : '')
                    . ' Gabon réservée' . ($unclaimed > 1 ? 's' : '') . ' à récupérer',
                'url'   => route('vendor.local-cards.index'),
                'tone'  => 'warn',
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // Séries pour les graphiques du tableau de bord
    // ------------------------------------------------------------------

    /**
     * Volume livré jour par jour, pour lire le rythme de vente.
     * Les jours sans vente sont présents à 0 : sans eux, le graphique
     * comprimerait le temps et donnerait une fausse impression de régularité.
     *
     * @return array<int, array{date:Carbon, label:string, amount:float}>
     */
    public function dailySeries(int $days = 14): array
    {
        $start = today()->copy()->subDays($days - 1);

        $orders = $this->reseller->orders()
            ->where('status', ResellerOrder::STATUS_COMPLETED)
            ->where('created_at', '>=', $start->copy()->startOfDay())
            ->get(['created_at', 'total_amount']);

        $locals = MerchantCardPurchase::where('reseller_id', $this->reseller->id)
            ->whereNotNull('sold_by_reseller_at')
            ->where('sold_by_reseller_at', '>=', $start->copy()->startOfDay())
            ->get(['sold_by_reseller_at', 'amount']);

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();

            $out[] = [
                'date'   => $day,
                // Libellé construit ici et non via translatedFormat() : la locale
                // de l'application est `en`, un jour anglais dans une UI française.
                'label'  => self::JOURS[$day->dayOfWeek] . ' ' . $day->format('j'),
                'day'    => $day->format('j'),
                'today'  => $day->isToday(),
                'amount' => (float) $orders->filter(fn ($o) => $o->created_at->toDateString() === $key)->sum('total_amount')
                          + (float) $locals->filter(fn ($p) => $p->sold_by_reseller_at?->toDateString() === $key)->sum('amount'),
            ];
        }

        return $out;
    }

    /**
     * Répartition du volume entre cartes digitales et Carte Gabon.
     * Le revendeur vend deux choses très différentes : savoir laquelle porte
     * son chiffre oriente ce qu'il pousse au comptoir.
     *
     * @return array{digital:float, local:float, total:float}
     */
    public function channelSplit(): array
    {
        $digital = (float) $this->ordersQuery(ResellerOrder::STATUS_COMPLETED, null, null)->sum('total_amount');
        $local   = (float) $this->localQuery()->sum('amount');

        return ['digital' => $digital, 'local' => $local, 'total' => $digital + $local];
    }

    /**
     * Marques les plus vendues, digital et Carte Gabon confondus.
     * Indique quoi mettre en avant — et quoi arrêter de pousser.
     *
     * @return array<int, array{name:string, amount:float, count:int}>
     */
    public function topBrands(int $limit = 5): array
    {
        $totaux = [];

        // Digital : la marque est portée par la ligne de commande.
        $this->reseller->orders()
            ->where('status', ResellerOrder::STATUS_COMPLETED)
            ->with('items:id,reseller_order_id,brand,name,total_price')
            ->get()
            ->each(function ($order) use (&$totaux) {
                foreach ($order->items as $item) {
                    $nom = trim((string) ($item->brand ?: explode(' ', (string) $item->name)[0])) ?: 'Autre';
                    $totaux[$nom] ??= ['name' => $nom, 'amount' => 0.0, 'count' => 0];
                    $totaux[$nom]['amount'] += (float) $item->total_price;
                    $totaux[$nom]['count']++;
                }
            });

        // Carte Gabon : la « marque » est le commerçant.
        MerchantCardPurchase::where('reseller_id', $this->reseller->id)
            ->whereNotNull('sold_by_reseller_at')
            ->with('merchantCard:id,name')
            ->get()
            ->each(function ($p) use (&$totaux) {
                $nom = $p->merchantCard?->name ?: 'Carte Gabon';
                $totaux[$nom] ??= ['name' => $nom, 'amount' => 0.0, 'count' => 0];
                $totaux[$nom]['amount'] += (float) $p->amount;
                $totaux[$nom]['count']++;
            });

        usort($totaux, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        return array_slice(array_values($totaux), 0, $limit);
    }

    // ------------------------------------------------------------------
    // Vue d'ensemble du dashboard
    // ------------------------------------------------------------------

    public function dashboard(): array
    {
        $today     = today();
        $yesterday = today()->copy()->subDay();

        $volumeToday     = $this->volume($today);
        $volumeYesterday = $this->volume($yesterday, $yesterday->copy()->endOfDay());

        return [
            'volume_today'      => $volumeToday,
            'volume_yesterday'  => $volumeYesterday,
            'volume_trend'      => self::trend($volumeToday, $volumeYesterday),
            'sales_today'       => $this->salesCount($today),
            'volume_total'      => $this->volume(),
            'orders_total'      => $this->ordersTotal(),
            'orders_awaiting'   => $this->awaitingPayment(),
            'orders_delivering' => $this->delivering(),
            'alerts'            => $this->alerts(),
            // Séries des trois graphiques (voir la vue du tableau de bord).
            'daily_series'      => $this->dailySeries(14),
            'channel_split'     => $this->channelSplit(),
            'top_brands'        => $this->topBrands(5),
        ];
    }

    /**
     * Variation en % entre deux périodes. null quand la référence est nulle :
     * on n'affiche pas « +100 % » à la première vente, ça ne veut rien dire.
     */
    public static function trend(float $current, float $previous): ?int
    {
        if ($previous <= 0) return null;
        return (int) round((($current - $previous) / $previous) * 100);
    }

    // ------------------------------------------------------------------
    // Constructeurs de requêtes
    // ------------------------------------------------------------------

    private function ordersQuery(?string $status, ?Carbon $from, ?Carbon $to)
    {
        $q = $this->reseller->orders();
        if ($status) $q->where('status', $status);
        if ($from)   $q->where('created_at', '>=', $from->copy()->startOfDay());
        if ($to)     $q->where('created_at', '<=', $to);
        return $q;
    }

    /**
     * Cartes locales RÉELLEMENT vendues : `sold_by_reseller_at` est posé à la
     * récupération, c'est-à-dire au moment où le wallet est débité et le code
     * activé. Une simple réservation ne compte pas.
     */
    private function localQuery(?Carbon $from = null, ?Carbon $to = null)
    {
        $q = MerchantCardPurchase::where('reseller_id', $this->reseller->id)
            ->whereNotNull('sold_by_reseller_at');
        if ($from) $q->where('sold_by_reseller_at', '>=', $from->copy()->startOfDay());
        if ($to)   $q->where('sold_by_reseller_at', '<=', $to);
        return $q;
    }
}
