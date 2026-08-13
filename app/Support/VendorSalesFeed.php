<?php

namespace App\Support;

use App\Models\MerchantCardPurchase;
use App\Models\Reseller;
use App\Models\ResellerOrder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * VendorSalesFeed
 * ===============
 * Flux unique des ventes d'un revendeur : cartes digitales (`reseller_orders`)
 * et Carte Gabon (`merchant_card_purchases`) dans une seule liste triée.
 *
 * Le revendeur avait deux historiques séparés — l'un sur « Mes ventes », l'autre
 * en bas de l'écran des cartes locales. Il ne pouvait donc pas répondre à la
 * question la plus banale de son comptoir : « qu'est-ce que j'ai vendu
 * aujourd'hui ? » sans additionner deux écrans de tête.
 *
 * Choix d'implémentation : UNION SQL sur les seuls identifiants, puis hydratation
 * de la page courante. Fusionner en PHP aurait obligé à charger l'historique
 * complet à chaque affichage — un revendeur actif dépasse le millier de ventes
 * par an.
 *
 * Les deux commissions restent distinctes jusque dans l'affichage : celle des
 * cartes digitales est créditée au portefeuille, celle des Cartes Gabon est
 * gardée en espèces au comptoir. Les additionner n'aurait aucun sens comptable
 * (même règle que `VendorStats`).
 */
class VendorSalesFeed
{
    public const TYPES = [
        'all'     => 'Toutes',
        'digital' => 'Cartes digitales',
        'local'   => 'Carte Gabon',
    ];

    /**
     * Vocabulaire de statut commun aux deux sources.
     * Les deux tables n'ont pas les mêmes états : une commande digitale est
     * « livrée », une Carte Gabon est « active ». Le filtre raisonne donc en
     * intentions, pas en valeurs de colonne — chaque ligne garde par ailleurs
     * son libellé exact dans le tableau.
     */
    public const BUCKETS = [
        'all'       => 'Toutes',
        'completed' => 'Livrées',
        'pending'   => 'À traiter',
        'failed'    => 'Échec',
        'cancelled' => 'Annulées',
        'refunded'  => 'Remboursées',
    ];

    private const DIGITAL_STATUSES = [
        'completed' => [ResellerOrder::STATUS_COMPLETED],
        'pending'   => [ResellerOrder::STATUS_PENDING, ResellerOrder::STATUS_PROCESSING],
        'failed'    => [ResellerOrder::STATUS_FAILED],
        'cancelled' => [ResellerOrder::STATUS_CANCELLED],
        'refunded'  => [ResellerOrder::STATUS_REFUNDING, ResellerOrder::STATUS_REFUNDED],
    ];

    private const LOCAL_STATUSES = [
        // Une carte récupérée est utilisable : c'est l'équivalent d'une livraison.
        'completed' => [
            MerchantCardPurchase::STATUS_ACTIVE,
            MerchantCardPurchase::STATUS_PARTIALLY_USED,
            MerchantCardPurchase::STATUS_FULLY_USED,
        ],
        'pending'   => [MerchantCardPurchase::STATUS_INACTIVE],
        'failed'    => [],   // n'existe pas au comptoir : la remise est immédiate
        'cancelled' => [MerchantCardPurchase::STATUS_CANCELLED, MerchantCardPurchase::STATUS_EXPIRED],
        'refunded'  => [],
    ];

    /** Libellé + couleurs par statut réel, source par source. */
    private const DIGITAL_BADGES = [
        'pending'    => ['En attente',    '#FEF3C7', '#B45309'],
        'processing' => ['En cours',      '#DBEAFE', '#1D4ED8'],
        'completed'  => ['Livrée',        '#D1FAE5', '#047857'],
        'cancelled'  => ['Annulée',       '#E2E8F0', '#475569'],
        'failed'     => ['Échec',         '#FEE2E2', '#BE123C'],
        'refunding'  => ['Remb. en cours','#EDE9FE', '#6D28D9'],
        'refunded'   => ['Remboursée',    '#EDE9FE', '#7C3AED'],
    ];

    private const LOCAL_BADGES = [
        'inactive'       => ['À récupérer',        '#FEF3C7', '#B45309'],
        'active'         => ['Active',             '#D1FAE5', '#047857'],
        'partially_used' => ['Utilisée en partie', '#DBEAFE', '#1D4ED8'],
        'fully_used'     => ['Épuisée',            '#E2E8F0', '#475569'],
        'cancelled'      => ['Annulée',            '#FEE2E2', '#B91C1C'],
        'expired'        => ['Expirée',            '#E2E8F0', '#475569'],
    ];

    /**
     * Charge utile complète de l'écran « Mes ventes ».
     *
     * @return array{rows:LengthAwarePaginator, type:string, bucket:string,
     *               search:string, typeCounts:array<string,int>}
     */
    public function payload(Request $request, Reseller $reseller, int $perPage = 15): array
    {
        [$type, $bucket, $search] = $this->criteria($request);

        $page = DB::query()
            ->fromSub($this->union($reseller, $type, $bucket, $search, $request), 'ventes')
            ->orderByDesc('sold_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'rows'       => $page->setCollection($this->hydrate($page->items())),
            'type'       => $type,
            'bucket'     => $bucket,
            'search'     => $search,
            'typeCounts' => [
                'digital' => $reseller->orders()->count(),
                'local'   => MerchantCardPurchase::where('reseller_id', $reseller->id)->count(),
            ],
        ];
    }

    /**
     * Toutes les lignes correspondant aux filtres, par paquets — pour l'export.
     * Le curseur évite de charger un historique entier en mémoire.
     *
     * @return \Generator<int, array>
     */
    public function lazyRows(Request $request, Reseller $reseller, int $chunk = 500): \Generator
    {
        [$type, $bucket, $search] = $this->criteria($request);

        $keys = DB::query()
            ->fromSub($this->union($reseller, $type, $bucket, $search, $request), 'ventes')
            ->orderBy('sold_at')
            ->cursor();

        foreach ($keys->chunk($chunk) as $paquet) {
            foreach ($this->hydrate($paquet->all()) as $row) {
                yield $row;
            }
        }
    }

    // ------------------------------------------------------------------
    // Interne
    // ------------------------------------------------------------------

    /** @return array{0:string,1:string,2:string} */
    private function criteria(Request $request): array
    {
        $type = (string) $request->query('type', 'all');
        if (!array_key_exists($type, self::TYPES)) $type = 'all';

        // `status` reste le nom du paramètre : les liens du dashboard et des
        // alertes pointent déjà dessus (?status=failed, ?status=pending).
        $bucket = (string) $request->query('status', 'all');
        if (!array_key_exists($bucket, self::BUCKETS)) $bucket = 'all';

        return [$type, $bucket, trim((string) $request->query('search', ''))];
    }

    private function union(Reseller $reseller, string $type, string $bucket, string $search, ?Request $request = null)
    {
        $digital = DB::table('reseller_orders')
            ->selectRaw("'digital' as source, id, created_at as sold_at")
            ->where('reseller_id', $reseller->id);

        $local = DB::table('merchant_card_purchases')
            ->selectRaw("'local' as source, id, created_at as sold_at")
            ->where('reseller_id', $reseller->id);

        if ($bucket !== 'all') {
            $digital->whereIn('status', self::DIGITAL_STATUSES[$bucket]);
            // Un tableau vide ne doit rien laisser passer : `whereIn([])` génère
            // bien `0 = 1`, la source est alors simplement absente du résultat.
            $local->whereIn('status', self::LOCAL_STATUSES[$bucket]);
        }

        // Bornes de date : l'export comptable les passe en URL (?from=&to=).
        foreach ([$digital, $local] as $q) {
            if ($request?->filled('from')) $q->whereDate('created_at', '>=', $request->query('from'));
            if ($request?->filled('to'))   $q->whereDate('created_at', '<=', $request->query('to'));
        }

        if ($search !== '') {
            $digital->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
            $local->where(function ($q) use ($search) {
                $q->where('unique_code', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('buyer_phone', 'like', "%{$search}%");
            });
        }

        return match ($type) {
            'digital' => $digital,
            'local'   => $local,
            default   => $digital->unionAll($local),
        };
    }

    /**
     * Transforme les clés d'une page en lignes affichables, en deux requêtes.
     *
     * @param  array<int, object>  $keys
     */
    private function hydrate(array $keys): \Illuminate\Support\Collection
    {
        $keys = collect($keys);

        $orders = $keys->where('source', 'digital')->pluck('id');
        $orders = $orders->isEmpty()
            ? collect()
            : ResellerOrder::with('items')->whereIn('id', $orders)->get()->keyBy('id');

        $locals = $keys->where('source', 'local')->pluck('id');
        $locals = $locals->isEmpty()
            ? collect()
            : MerchantCardPurchase::with('merchantCard:id,name')->whereIn('id', $locals)->get()->keyBy('id');

        return $keys->map(function ($k) use ($orders, $locals) {
            return $k->source === 'digital'
                ? (($o = $orders->get($k->id)) ? $this->digitalRow($o) : null)
                : (($p = $locals->get($k->id)) ? $this->localRow($p) : null);
        })->filter()->values();
    }

    private function digitalRow(ResellerOrder $o): array
    {
        [$label, $bg, $fg] = self::DIGITAL_BADGES[$o->status] ?? [ucfirst($o->status), '#E2E8F0', '#475569'];
        $qte = (int) $o->items->sum('quantity');

        return [
            'type'            => 'digital',
            'type_label'      => 'Carte digitale',
            'reference'       => '#' . $o->order_number,
            'date'            => $o->created_at,
            'customer'        => $o->customer_name ?: null,
            'phone'           => $o->customer_phone,
            'detail'          => $qte . ' carte' . ($qte > 1 ? 's' : ''),
            'items'           => $o->items->pluck('name')->implode(' + '),
            'amount'          => (float) $o->total_amount,
            // La commission n'est acquise qu'une fois la carte livrée : l'afficher
            // sur un panier abandonné laisserait croire à un gain.
            'commission'      => $o->status === ResellerOrder::STATUS_COMPLETED ? (float) $o->commission_earned : null,
            'commission_kind' => 'wallet',
            'status'          => $o->status,
            'status_label'    => $label,
            'status_bg'       => $bg,
            'status_fg'       => $fg,
            'payment'         => $o->payment_method === 'cash' ? 'Espèces' : 'E-Billing',
            'url'             => route('vendor.orders.show', $o),
            'todo'            => in_array($o->status, [ResellerOrder::STATUS_FAILED, ResellerOrder::STATUS_PENDING], true),
        ];
    }

    private function localRow(MerchantCardPurchase $p): array
    {
        [$label, $bg, $fg] = self::LOCAL_BADGES[$p->status] ?? [ucfirst($p->status), '#E2E8F0', '#475569'];
        $recuperee = $p->sold_by_reseller_at !== null;

        return [
            'type'            => 'local',
            'type_label'      => 'Carte Gabon',
            'reference'       => $p->unique_code,
            'date'            => $p->created_at,
            'customer'        => $p->buyer_name ?: null,
            'phone'           => $p->buyer_phone,
            'detail'          => $p->merchantCard?->name ?? 'Carte Gabon',
            'items'           => $p->merchantCard?->name ?? 'Carte Gabon',
            'amount'          => (float) $p->amount,
            // Marge gardée en espèces : acquise seulement si la carte a été
            // réellement récupérée au comptoir.
            'commission'      => $recuperee ? (float) $p->vendor_commission_amount : null,
            'commission_kind' => 'cash',
            'status'          => $p->status,
            'status_label'    => $label,
            'status_bg'       => $bg,
            'status_fg'       => $fg,
            'payment'         => 'Comptoir',
            'url'             => route('vendor.local-cards.show', $p),
            'todo'            => $p->status === MerchantCardPurchase::STATUS_INACTIVE,
        ];
    }
}
