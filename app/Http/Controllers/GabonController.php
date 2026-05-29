<?php

namespace App\Http\Controllers;

use App\Models\MerchantCard;
use Illuminate\Http\Request;

/**
 * GabonController
 * ===
 * Marketplace public des cartes-cadeau locales (= catalogue créé par l'admin
 * dans /admin/merchant-cards). Aucune carte n'appartient à un marchand : c'est
 * un catalogue global Kardafrica.
 *
 * URLs :
 *   /gabon                          → landing (toolbar + filtres + grille)
 *   /gabon/categorie/{slug}         → filtre par catégorie
 *   /gabon/carte/{merchantCard}     → détail d'une carte + bouton acheter
 */
class GabonController extends Controller
{
    public const SORT_OPTIONS = [
        'newest'     => 'Récents',
        'popular'    => 'Populaires',
        'price_asc'  => 'Prix croissant',
        'price_desc' => 'Prix décroissant',
    ];

    /** Tranches de prix (basées sur la plus petite dénomination de la carte). */
    public const PRICE_RANGES = [
        'under_5000'    => 'Moins de 5 000',
        '5000_15000'    => '5 000 – 15 000',
        '15000_50000'   => '15 000 – 50 000',
        'over_50000'    => 'Plus de 50 000',
    ];

    /** Landing /gabon — toolbar + filtres + grille (style boutique) */
    public function index(Request $request)
    {
        $search       = trim((string) ($request->query('search') ?? $request->query('q') ?? ''));
        $categorySlug = (string) $request->query('category', '');
        $priceRanges  = array_values(array_filter((array) $request->query('price_range', [])));
        $sort         = $request->query('sort', 'newest');
        if (!array_key_exists($sort, self::SORT_OPTIONS)) $sort = 'newest';

        $query = MerchantCard::active();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categorySlug !== '' && array_key_exists($categorySlug, MerchantCard::CATEGORIES)) {
            $query->where('category', $categorySlug);
        }

        if ($sort === 'newest') {
            $query->orderByDesc('activated_at');
        } elseif ($sort === 'popular') {
            $query->orderByDesc('total_sold')->orderByDesc('activated_at');
        }

        // price_* sorts + price_range filters → traités en PHP (denominations = JSON)
        if (!empty($priceRanges) || in_array($sort, ['price_asc', 'price_desc'], true)) {
            $all   = $query->get();
            $minOf = fn (MerchantCard $c) => collect($c->denominations ?? [])->min() ?? 0;

            if (!empty($priceRanges)) {
                $all = $all->filter(function ($c) use ($priceRanges, $minOf) {
                    $min = $minOf($c);
                    foreach ($priceRanges as $range) {
                        if ($range === 'under_5000'  && $min <  5000)                  return true;
                        if ($range === '5000_15000'  && $min >= 5000  && $min <= 15000) return true;
                        if ($range === '15000_50000' && $min > 15000  && $min <= 50000) return true;
                        if ($range === 'over_50000'  && $min > 50000)                   return true;
                    }
                    return false;
                });
            }

            if ($sort === 'price_asc')  $all = $all->sortBy($minOf)->values();
            if ($sort === 'price_desc') $all = $all->sortByDesc($minOf)->values();

            $perPage = 24;
            $page    = max(1, (int) $request->query('page', 1));
            $cards = new \Illuminate\Pagination\LengthAwarePaginator(
                $all->forPage($page, $perPage),
                $all->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $cards = $query->paginate(24)->withQueryString();
        }

        $activeFiltersCount = ($search !== '' ? 1 : 0)
            + ($categorySlug !== '' ? 1 : 0)
            + count($priceRanges);

        return view('gabon.index', [
            'cards'              => $cards,
            'categories'         => MerchantCard::CATEGORIES,
            'search'             => $search,
            'categorySlug'       => $categorySlug,
            'currentCategory'    => $categorySlug !== '' ? MerchantCard::CATEGORIES[$categorySlug] ?? null : null,
            'priceRanges'        => $priceRanges,
            'priceRangeLabels'   => self::PRICE_RANGES,
            'sort'               => $sort,
            'sortLabel'          => self::SORT_OPTIONS[$sort],
            'sortOptions'        => self::SORT_OPTIONS,
            'activeFiltersCount' => $activeFiltersCount,
        ]);
    }

    /** /gabon/categorie/{slug} — toutes les cartes d'une catégorie */
    public function category(Request $request, string $slug)
    {
        abort_unless(array_key_exists($slug, MerchantCard::CATEGORIES), 404);

        $cards = MerchantCard::active()
            ->where('category', $slug)
            ->latest('activated_at')
            ->paginate(24)
            ->withQueryString();

        return view('gabon.category', [
            'cards'        => $cards,
            'categorySlug' => $slug,
            'categoryName' => MerchantCard::CATEGORIES[$slug],
            'categories'   => MerchantCard::CATEGORIES,
        ]);
    }

    /** /gabon/carte/{merchantCard} — détail + bouton acheter */
    public function card(MerchantCard $merchantCard)
    {
        abort_unless($merchantCard->is_active, 404);

        $otherCards = MerchantCard::active()
            ->where('id', '!=', $merchantCard->id)
            ->take(4)
            ->get();

        return view('gabon.card', [
            'card'       => $merchantCard,
            'otherCards' => $otherCards,
            'categories' => MerchantCard::CATEGORIES,
        ]);
    }
}
