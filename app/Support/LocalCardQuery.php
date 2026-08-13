<?php

namespace App\Support;

use App\Models\MerchantCard;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * LocalCardQuery
 * ==============
 * Filtrage du catalogue « Carte Gabon », partagé par la vitrine publique
 * (/gabon) et l'écran de vente revendeur (/vendor/local-cards).
 *
 * Le revendeur vend exactement ces cartes : il doit pouvoir les chercher,
 * les filtrer et les trier comme le fait le client. Son écran n'avait
 * jusqu'ici ni recherche, ni catégorie, ni tri — juste la liste brute.
 */
class LocalCardQuery
{
    public const SORTS = [
        'newest'     => 'Récentes',
        'popular'    => 'Populaires',
        'price_asc'  => 'Prix croissant',
        'price_desc' => 'Prix décroissant',
    ];

    /** Tranches basées sur la plus petite dénomination de la carte. */
    public const PRICE_RANGES = [
        'under_5000'  => 'Moins de 5 000',
        '5000_15000'  => '5 000 – 15 000',
        '15000_50000' => '15 000 – 50 000',
        'over_50000'  => 'Plus de 50 000',
    ];

    /**
     * Charge utile complète d'un écran catalogue local.
     *
     * @return array{cards:LengthAwarePaginator, categories:array, search:string,
     *               categorySlug:string, priceRanges:array, sort:string,
     *               activeFiltersCount:int}
     */
    public function payload(Request $request, int $perPage = 24): array
    {
        $search       = trim((string) ($request->query('search') ?? $request->query('q') ?? ''));
        $categorySlug = (string) $request->query('category', '');
        $priceRanges  = array_values(array_intersect(
            (array) $request->query('price_range', []),
            array_keys(self::PRICE_RANGES)
        ));

        $sort = (string) $request->query('sort', 'newest');
        if (!array_key_exists($sort, self::SORTS)) $sort = 'newest';

        $query = MerchantCard::active()->with('owner:id,business_name,city');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categorySlug !== '' && array_key_exists($categorySlug, MerchantCard::CATEGORIES)) {
            $query->where('category', $categorySlug);
        }

        if ($sort === 'newest')  $query->orderByDesc('activated_at');
        if ($sort === 'popular') $query->orderByDesc('total_sold')->orderByDesc('activated_at');

        // Les dénominations sont stockées en JSON : le tri et le filtre par prix
        // ne peuvent pas se faire en SQL, on bascule en PHP pour ces cas.
        if (!empty($priceRanges) || in_array($sort, ['price_asc', 'price_desc'], true)) {
            $cards = $this->filterAndSortInMemory($query->get(), $priceRanges, $sort, $request, $perPage);
        } else {
            $cards = $query->paginate($perPage)->withQueryString();
        }

        return [
            'cards'              => $cards,
            'categories'         => MerchantCard::CATEGORIES,
            'search'             => $search,
            'categorySlug'       => $categorySlug,
            'currentCategory'    => $categorySlug !== '' ? (MerchantCard::CATEGORIES[$categorySlug] ?? null) : null,
            'priceRanges'        => $priceRanges,
            'priceRangeLabels'   => self::PRICE_RANGES,
            'sort'               => $sort,
            'sortLabel'          => self::SORTS[$sort],
            'sortOptions'        => self::SORTS,
            'activeFiltersCount' => ($search !== '' ? 1 : 0)
                + ($categorySlug !== '' ? 1 : 0)
                + count($priceRanges),
        ];
    }

    /** Plus petite dénomination d'une carte — la base du prix affiché. */
    public static function entryAmount(MerchantCard $card): float
    {
        return (float) (collect($card->denominations ?? [])->filter(fn ($d) => (float) $d > 0)->min() ?? 0);
    }

    private function filterAndSortInMemory($all, array $priceRanges, string $sort, Request $request, int $perPage): LengthAwarePaginator
    {
        $minOf = fn (MerchantCard $c) => self::entryAmount($c);

        if (!empty($priceRanges)) {
            $all = $all->filter(function (MerchantCard $c) use ($priceRanges, $minOf) {
                $min = $minOf($c);
                foreach ($priceRanges as $range) {
                    if ($range === 'under_5000'  && $min < 5000)                    return true;
                    if ($range === '5000_15000'  && $min >= 5000 && $min <= 15000)  return true;
                    if ($range === '15000_50000' && $min > 15000 && $min <= 50000)  return true;
                    if ($range === 'over_50000'  && $min > 50000)                   return true;
                }
                return false;
            });
        }

        if ($sort === 'price_asc')  $all = $all->sortBy($minOf)->values();
        if ($sort === 'price_desc') $all = $all->sortByDesc($minOf)->values();

        $page = max(1, (int) $request->query('page', 1));

        return new LengthAwarePaginator(
            $all->forPage($page, $perPage),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
