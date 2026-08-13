<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Reseller;
use App\Models\ResellerOrder;
use App\Models\User;
use App\Models\UserCard;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AdminDashboardStats
 * ===================
 * Source unique du tableau de bord admin.
 *
 * Une seule instance par page : chaque widget lui demande ses chiffres, elle
 * ne recalcule jamais la même chose deux fois (mémoïsation interne). Le but
 * explicite est d'écarter le N+1 — un dashboard qui interroge la base widget
 * par widget devient injouable dès que le catalogue grossit.
 *
 * FUSEAU HORAIRE
 * --------------
 * `config('app.timezone')` vaut `UTC` et le rester : le changer déplacerait
 * silencieusement toutes les dates de l'application. Les bornes de période sont
 * donc calculées en **Africa/Libreville** ici, puis converties en UTC pour
 * interroger la base. « Aujourd'hui » désigne ainsi la journée gabonaise, pas
 * la journée UTC — un écart d'une heure qui, à minuit passé, changeait le
 * chiffre du jour.
 *
 * PÉRIMÈTRE DU « REVENU »
 * -----------------------
 * Le revenu compte les commandes du canal en ligne (`orders`), définition
 * historique de cet écran, inchangée par la refonte. Les ventes revendeurs
 * (`reseller_orders`) et Carte Gabon (`merchant_card_purchases`) n'y entrent
 * pas — elles ont leurs propres écrans. Le classement des vendeurs, lui, lit
 * bien `reseller_orders` : c'est là que vit l'activité des revendeurs.
 */
class AdminDashboardStats
{
    public const TZ = 'Africa/Libreville';

    /** Presets du sélecteur de période. */
    public const PRESETS = [
        '7j'   => '7 jours',
        '30j'  => '30 jours',
        'mois' => 'Ce mois-ci',
    ];

    public const DEFAULT_PRESET = 'mois';

    private Carbon $from;
    private Carbon $to;
    private string $preset;
    private bool $custom;

    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(Carbon $from, Carbon $to, string $preset, bool $custom)
    {
        $this->from   = $from;
        $this->to     = $to;
        $this->preset = $preset;
        $this->custom = $custom;
    }

    /**
     * Résout la période depuis la requête.
     *
     * `date_from`/`date_to` sont les paramètres qu'émet déjà le sélecteur de la
     * topbar (P1) et que consomment Commandes et Paiements : on parle le même
     * langage plutôt que d'en inventer un troisième.
     */
    public static function fromRequest(Request $request): self
    {
        $tz  = self::TZ;
        $now = Carbon::now($tz);

        $depuis = $request->query('date_from');
        $jusqu  = $request->query('date_to');

        if (filled($depuis) || filled($jusqu)) {
            $from = filled($depuis) ? Carbon::parse($depuis, $tz)->startOfDay() : $now->copy()->startOfMonth();
            $to   = filled($jusqu)  ? Carbon::parse($jusqu, $tz)->endOfDay()   : $now->copy()->endOfDay();

            // Bornes inversées : on remet dans l'ordre plutôt que de rendre une
            // page vide que l'utilisateur ne saurait pas expliquer.
            if ($from->greaterThan($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return new self($from, $to, 'custom', true);
        }

        $preset = (string) $request->query('periode', self::DEFAULT_PRESET);
        if (! array_key_exists($preset, self::PRESETS)) {
            $preset = self::DEFAULT_PRESET;
        }

        [$from, $to] = match ($preset) {
            '7j'  => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30j' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
        };

        return new self($from, $to, $preset, false);
    }

    // ------------------------------------------------------------------
    // Période
    // ------------------------------------------------------------------

    public function from(): Carbon { return $this->from->copy(); }
    public function to(): Carbon { return $this->to->copy(); }
    public function preset(): string { return $this->preset; }
    public function isCustom(): bool { return $this->custom; }

    /** Durée en jours pleins, minimum 1 (une plage d'un jour reste une période). */
    public function lengthInDays(): int
    {
        return max(1, $this->from->diffInDays($this->to) + 1);
    }

    /** Période précédente de MÊME durée, immédiatement avant. */
    public function previousFrom(): Carbon
    {
        return $this->from->copy()->subDays($this->lengthInDays())->startOfDay();
    }

    public function previousTo(): Carbon
    {
        return $this->from->copy()->subSecond();
    }

    /** Bornes UTC — la base stocke en UTC, la période se pense en heure locale. */
    private function utc(Carbon $d): Carbon
    {
        return $d->copy()->utc();
    }

    // ------------------------------------------------------------------
    // Revenu et comparaison
    // ------------------------------------------------------------------

    private function revenueBetween(Carbon $from, Carbon $to): float
    {
        return (float) Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->whereBetween('created_at', [$this->utc($from), $this->utc($to)])
            ->sum('total_amount');
    }

    public function revenue(): float
    {
        return $this->memo['revenue'] ??= $this->revenueBetween($this->from, $this->to);
    }

    public function revenuePrevious(): float
    {
        return $this->memo['revenue_prev'] ??= $this->revenueBetween($this->previousFrom(), $this->previousTo());
    }

    /**
     * Écart avec la période précédente.
     *
     * Un pourcentage n'a de sens que si la référence est non nulle : diviser
     * par zéro donnerait « +∞ % », ce que l'écran ne doit jamais afficher. On
     * rend alors un badge neutre.
     *
     * @return array{kind:'percent'|'first'|'flat', percent:?float, amount:float, up:bool, note:?string}
     */
    public function revenueDelta(): array
    {
        $courant   = $this->revenue();
        $precedent = $this->revenuePrevious();
        $ecart     = round($courant - $precedent, 2);

        // La comparaison porte-t-elle sur une période précédente complète ?
        $debut = $this->firstActivityAt();
        $note  = null;

        if ($debut && $debut->greaterThan($this->previousFrom())) {
            $jours = max(0, $debut->diffInDays($this->previousTo()) + 1);
            $note  = 'Période précédente partielle : '
                . $jours . ' jour' . ($jours > 1 ? 's' : '') . ' d\'activité disponibles sur '
                . $this->lengthInDays() . '.';
        }

        if ($precedent <= 0) {
            return [
                'kind'    => 'first',
                'percent' => null,
                'amount'  => $ecart,
                'up'      => $ecart >= 0,
                'note'    => $note ?? 'Aucun revenu sur la période précédente : comparaison impossible.',
            ];
        }

        return [
            'kind'    => 'percent',
            'percent' => round($ecart / $precedent * 100, 1),
            'amount'  => $ecart,
            'up'      => $ecart >= 0,
            'note'    => $note,
        ];
    }

    /** Date de la toute première commande payée, pour qualifier les comparaisons. */
    private function firstActivityAt(): ?Carbon
    {
        if (array_key_exists('first_activity', $this->memo)) {
            return $this->memo['first_activity'];
        }

        $date = Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)->min('created_at');

        return $this->memo['first_activity'] = $date ? Carbon::parse($date)->setTimezone(self::TZ) : null;
    }

    /** Commande la plus élevée de la période, ou null. */
    public function bestSale(): ?Order
    {
        return $this->memo['best_sale'] ??= Order::with('user')
            ->where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->whereBetween('created_at', [$this->utc($this->from), $this->utc($this->to)])
            ->orderByDesc('total_amount')
            ->first();
    }

    // ------------------------------------------------------------------
    // Compteurs
    // ------------------------------------------------------------------

    public function ordersCount(): int
    {
        return $this->memo['orders'] ??= Order::whereBetween('created_at', [$this->utc($this->from), $this->utc($this->to)])->count();
    }

    /** Commandes du jour gabonais — pas du jour UTC. */
    public function ordersToday(): int
    {
        if (isset($this->memo['orders_today'])) {
            return $this->memo['orders_today'];
        }

        $debut = Carbon::now(self::TZ)->startOfDay();
        $fin   = Carbon::now(self::TZ)->endOfDay();

        return $this->memo['orders_today'] = Order::whereBetween('created_at', [$this->utc($debut), $this->utc($fin)])->count();
    }

    /** @return array{active:int, total:int} */
    public function cards(): array
    {
        return $this->memo['cards'] ??= [
            'active' => UserCard::where('status', UserCard::STATUS_ACTIVE)->count(),
            'total'  => UserCard::count(),
        ];
    }

    /** @return array{active:int, total:int} */
    public function users(): array
    {
        return $this->memo['users'] ??= [
            'active' => User::where('is_active', true)->count(),
            'total'  => User::count(),
        ];
    }

    // ------------------------------------------------------------------
    // Répartition des paiements par canal
    // ------------------------------------------------------------------

    /**
     * Trois canaux : Mobile Money, carte bancaire, autre.
     *
     * La somme des pourcentages fait EXACTEMENT 100 : les arrondis individuels
     * laissent un reliquat, absorbé par le plus gros segment. Une barre qui
     * affiche 33 + 33 + 33 se remarque immédiatement.
     *
     * @return array{segments:array<int,array>, total:float, source:string}
     */
    public function paymentChannels(): array
    {
        if (isset($this->memo['channels'])) {
            return $this->memo['channels'];
        }

        $lignes = Payment::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->utc($this->from), $this->utc($this->to)])
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as n'))
            ->groupBy('payment_method')
            ->get();

        $buckets = [
            'mobile' => ['key' => 'mobile', 'label' => 'Mobile Money',   'color' => 'teal',   'amount' => 0.0, 'count' => 0],
            'card'   => ['key' => 'card',   'label' => 'Carte bancaire', 'color' => 'blue',   'amount' => 0.0, 'count' => 0],
            'other'  => ['key' => 'other',  'label' => 'Autre',          'color' => 'violet', 'amount' => 0.0, 'count' => 0],
        ];

        foreach ($lignes as $ligne) {
            $cle = self::channelBucket((string) $ligne->payment_method);
            $buckets[$cle]['amount'] += (float) $ligne->total;
            $buckets[$cle]['count']  += (int) $ligne->n;
        }

        $total = array_sum(array_column($buckets, 'amount'));

        $segments = array_values($buckets);

        if ($total > 0) {
            foreach ($segments as $i => $s) {
                $segments[$i]['percent'] = round($s['amount'] / $total * 100, 1);
            }

            // Reliquat d'arrondi sur le plus gros segment.
            $plusGros = array_keys($segments, max($segments))[0] ?? 0;
            $somme    = array_sum(array_column($segments, 'percent'));
            $segments[$plusGros]['percent'] = round($segments[$plusGros]['percent'] + (100 - $somme), 1);
        } else {
            foreach ($segments as $i => $s) {
                $segments[$i]['percent'] = 0.0;
            }
        }

        return $this->memo['channels'] = [
            'segments' => $segments,
            'total'    => $total,
            'source'   => 'payments.payment_method',
        ];
    }

    /** Classe une méthode de paiement dans l'un des trois canaux. */
    public static function channelBucket(?string $methode): string
    {
        $m = mb_strtolower(trim((string) $methode));

        foreach (['airtel', 'moov', 'mobile', 'momo', 'om', 'wave', 'ebilling', 'e-billing'] as $motif) {
            if ($m !== '' && str_contains($m, $motif)) {
                return 'mobile';
            }
        }

        foreach (['card', 'carte', 'visa', 'master', 'cb', 'stripe', 'bank'] as $motif) {
            if ($m !== '' && str_contains($m, $motif)) {
                return 'card';
            }
        }

        return 'other';
    }

    // ------------------------------------------------------------------
    // Séries
    // ------------------------------------------------------------------

    /**
     * Dynamique des ventes : période courante et période précédente, alignées
     * point à point pour être superposables.
     *
     * La granularité suit la plage : une journée se lit par heures, un mois par
     * jours, un trimestre par semaines. Tracer 90 points sur 300 px ne montre
     * rien.
     *
     * @return array{points:array<int,array>, granularity:string, max:float, total:float}
     */
    public function salesSeries(): array
    {
        if (isset($this->memo['series'])) {
            return $this->memo['series'];
        }

        $jours = $this->lengthInDays();

        [$granularite, $pas] = match (true) {
            $jours <= 1  => ['hour', 'heure'],
            $jours <= 45 => ['day', 'jour'],
            default      => ['week', 'semaine'],
        };

        $courant   = $this->bucketise($this->from, $this->to, $granularite);
        $precedent = $this->bucketise($this->previousFrom(), $this->previousTo(), $granularite);

        $labels = array_keys($courant);
        $avant  = array_values($precedent);

        $points = [];
        foreach (array_values($courant) as $i => $valeur) {
            $points[] = [
                'label'    => $labels[$i],
                'current'  => $valeur,
                // Les deux séries n'ont pas forcément le même nombre de seaux
                // (mois de 30 et 31 jours) : les points manquants valent 0.
                'previous' => $avant[$i] ?? 0.0,
            ];
        }

        $max = 0.0;
        foreach ($points as $p) {
            $max = max($max, $p['current'], $p['previous']);
        }

        return $this->memo['series'] = [
            'points'      => $points,
            'granularity' => $pas,
            'max'         => $max,
            'total'       => array_sum(array_column($points, 'current')),
        ];
    }

    /**
     * Somme des commandes payées par seau de temps, seaux vides inclus.
     * Une seule requête groupée, jamais une par jour.
     *
     * @return array<string, float>
     */
    private function bucketise(Carbon $from, Carbon $to, string $granularite): array
    {
        $lignes = Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->whereBetween('created_at', [$this->utc($from), $this->utc($to)])
            ->get(['created_at', 'total_amount']);

        $seaux = [];
        $curseur = $from->copy();

        while ($curseur->lessThanOrEqualTo($to)) {
            $seaux[$this->bucketKey($curseur, $granularite)] = 0.0;
            $curseur = match ($granularite) {
                'hour' => $curseur->addHour(),
                'week' => $curseur->addWeek(),
                default => $curseur->addDay(),
            };
        }

        foreach ($lignes as $ligne) {
            $cle = $this->bucketKey(Carbon::parse($ligne->created_at)->setTimezone(self::TZ), $granularite);

            if (array_key_exists($cle, $seaux)) {
                $seaux[$cle] += (float) $ligne->total_amount;
            }
        }

        return $seaux;
    }

    private function bucketKey(Carbon $d, string $granularite): string
    {
        return match ($granularite) {
            'hour' => $d->format('H') . 'h',
            'week' => 'S' . $d->isoWeek(),
            default => $d->format('j/m'),
        };
    }

    /**
     * Commandes des 6 derniers mois, mois courant marqué incomplet.
     * Une requête groupée, pas six.
     *
     * @return array<int, array{label:string, count:int, partial:bool}>
     */
    public function ordersByMonth(int $mois = 6): array
    {
        if (isset($this->memo['by_month'])) {
            return $this->memo['by_month'];
        }

        $debut = Carbon::now(self::TZ)->startOfMonth()->subMonths($mois - 1);

        $lignes = Order::where('created_at', '>=', $this->utc($debut))
            ->get(['created_at'])
            ->groupBy(fn ($o) => Carbon::parse($o->created_at)->setTimezone(self::TZ)->format('Y-m'))
            ->map->count();

        $noms = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
        $out  = [];

        for ($i = 0; $i < $mois; $i++) {
            $m = $debut->copy()->addMonths($i);

            $out[] = [
                'label'   => $noms[(int) $m->format('n')],
                'count'   => (int) ($lignes[$m->format('Y-m')] ?? 0),
                // Le mois en cours est incomplet : le comparer aux autres à
                // l'identique laisserait croire à une chute.
                'partial' => $m->isSameMonth(Carbon::now(self::TZ)),
            ];
        }

        return $this->memo['by_month'] = $out;
    }

    // ------------------------------------------------------------------
    // Classement
    // ------------------------------------------------------------------

    /**
     * Top vendeurs sur la période, mesuré sur les commandes LIVRÉES : une
     * commande abandonnée ne fait pas d'un revendeur un bon vendeur.
     *
     * Deux requêtes au total, quel que soit le nombre de vendeurs.
     *
     * @return array{rows:Collection, fallback:bool}
     */
    public function topSellers(int $limite = 5): array
    {
        if (isset($this->memo['top_sellers'])) {
            return $this->memo['top_sellers'];
        }

        $lignes = ResellerOrder::where('status', ResellerOrder::STATUS_COMPLETED)
            ->whereBetween('created_at', [$this->utc($this->from), $this->utc($this->to)])
            ->select('reseller_id', DB::raw('SUM(total_amount) as revenu'), DB::raw('COUNT(*) as commandes'))
            ->groupBy('reseller_id')
            ->orderByDesc('revenu')
            ->take($limite)
            ->get();

        if ($lignes->isEmpty()) {
            return $this->memo['top_sellers'] = ['rows' => $this->topCards($limite), 'fallback' => true];
        }

        $vendeurs = Reseller::whereIn('id', $lignes->pluck('reseller_id'))->get(['id', 'name', 'vendor_code'])->keyBy('id');

        $rows = $lignes->map(fn ($l) => [
            'name'     => $vendeurs[$l->reseller_id]->name ?? 'Vendeur',
            'sub'      => $vendeurs[$l->reseller_id]->vendor_code ?? '',
            'amount'   => (float) $l->revenu,
            'count'    => (int) $l->commandes,
            'url'      => route('admin.resellers.show', $l->reseller_id),
        ]);

        return $this->memo['top_sellers'] = ['rows' => $rows, 'fallback' => false];
    }

    /** Repli quand aucun vendeur n'a vendu : les cartes les plus vendues. */
    private function topCards(int $limite): Collection
    {
        return \App\Models\ResellerOrderItem::whereHas('order', function ($q) {
                $q->where('status', ResellerOrder::STATUS_COMPLETED)
                  ->whereBetween('created_at', [$this->utc($this->from), $this->utc($this->to)]);
            })
            ->select('brand', DB::raw('SUM(total_price) as revenu'), DB::raw('COUNT(*) as n'))
            ->groupBy('brand')
            ->orderByDesc('revenu')
            ->take($limite)
            ->get()
            ->map(fn ($l) => [
                'name'   => $l->brand ?: 'Carte',
                'sub'    => '',
                'amount' => (float) $l->revenu,
                'count'  => (int) $l->n,
                'url'    => null,
            ]);
    }

    // ------------------------------------------------------------------
    // Versements en attente
    // ------------------------------------------------------------------

    /**
     * @return array{amount:float, count:int}
     *
     * Délégué à `AdminBadges` : la navigation affiche déjà ce compteur, il n'y
     * a aucune raison de rejouer la requête pour le widget.
     */
    public function pendingSettlements(): array
    {
        return AdminBadges::make()->pendingSettlements();
    }

    // ------------------------------------------------------------------
    // Commandes récentes
    // ------------------------------------------------------------------

    public function recentOrders(int $limite = 10): Collection
    {
        return $this->memo['recent'] ??= Order::with('user')
            ->whereBetween('created_at', [$this->utc($this->from), $this->utc($this->to)])
            ->latest()
            ->take($limite)
            ->get();
    }

    /** Libellé lisible de la période, pour l'en-tête. */
    public function label(): string
    {
        if ($this->custom) {
            return $this->from->format('d/m/Y') . ' → ' . $this->to->format('d/m/Y');
        }

        return self::PRESETS[$this->preset];
    }
}
