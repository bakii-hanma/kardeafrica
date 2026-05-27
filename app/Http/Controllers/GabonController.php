<?php

namespace App\Http\Controllers;

use App\Models\MerchantCard;
use App\Models\Reseller;
use Illuminate\Http\Request;

/**
 * GabonController
 * ===
 * Marketplace public des cartes-cadeau MARCHAND (= cartes créées par les
 * vendors approuvés KYC dans /vendor/cartes-cadeau).
 *
 * URLs :
 *   /gabon                          → landing
 *   /gabon/categorie/{slug}         → filtre par catégorie
 *   /gabon/marchand/{slug}          → profil d'un marchand + ses cartes
 *   /gabon/carte/{merchantCard}     → détail d'une carte + bouton acheter
 *
 * Toutes ces pages sont publiques (pas d'auth). Le bouton "Acheter" mène à
 * Phase 4 (futursowax) — pour l'instant route placeholder.
 */
class GabonController extends Controller
{
    /**
     * Sorts disponibles. Mêmes clés que sur /boutique pour la cohérence UX.
     */
    public const SORT_OPTIONS = [
        'newest'     => 'Récents',
        'popular'    => 'Populaires',
        'price_asc'  => 'Prix croissant',
        'price_desc' => 'Prix décroissant',
    ];

    /**
     * Tranches de prix (basées sur la plus petite dénomination de la carte).
     */
    public const PRICE_RANGES = [
        'under_5000'    => 'Moins de 5 000',
        '5000_15000'    => '5 000 – 15 000',
        '15000_50000'   => '15 000 – 50 000',
        'over_50000'    => 'Plus de 50 000',
    ];

    /** Landing /gabon — toolbar + filtres + grille (style boutique) */
    public function index(Request $request)
    {
        // ============================================================
        // Lecture des params (compat 'q' ET 'search', plus 'category', 'city',
        // 'price_range', 'sort')
        // ============================================================
        $search        = trim((string) ($request->query('search') ?? $request->query('q') ?? ''));
        $categorySlug  = (string) $request->query('category', '');
        $cities        = array_values(array_filter((array) $request->query('city', [])));
        $priceRanges   = array_values(array_filter((array) $request->query('price_range', [])));
        $sort          = $request->query('sort', 'newest');
        if (!array_key_exists($sort, self::SORT_OPTIONS)) $sort = 'newest';

        // ============================================================
        // Query principale
        // ============================================================
        $query = MerchantCard::active()
            ->with('reseller:id,name,business_name,slug,city,logo_url');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('reseller', function ($r) use ($search) {
                      $r->where('business_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                  });
            });
        }

        if ($categorySlug !== '' && array_key_exists($categorySlug, MerchantCard::CATEGORIES)) {
            $query->where(function ($q) use ($categorySlug) {
                $q->where('category', $categorySlug)
                  ->orWhereHas('reseller', fn ($r) => $r->where('business_type', $categorySlug));
            });
        }

        if (!empty($cities)) {
            $query->whereHas('reseller', fn ($r) => $r->whereIn('city', $cities));
        }

        // Tri DB (sauf price_*, qu'on traite en PHP car denominations est JSON)
        if ($sort === 'newest') {
            $query->orderByDesc('activated_at');
        } elseif ($sort === 'popular') {
            $query->orderByDesc('total_sold')->orderByDesc('activated_at');
        }
        // Pour price_asc/price_desc, on fetch d'abord, on trie en PHP plus bas.

        // Pour les filtres prix on aurait besoin d'un index JSON SQL, mais à
        // notre échelle on récupère et filtre en PHP — c'est plus simple et
        // toujours rapide (<1000 cartes prévues à court terme).
        if (!empty($priceRanges) || in_array($sort, ['price_asc', 'price_desc'], true)) {
            $all = $query->get();

            $minOf = fn (\App\Models\MerchantCard $c) => collect($c->denominations ?? [])->min() ?? 0;

            if (!empty($priceRanges)) {
                $all = $all->filter(function ($c) use ($priceRanges, $minOf) {
                    $min = $minOf($c);
                    foreach ($priceRanges as $range) {
                        if ($range === 'under_5000'   && $min <  5000)                 return true;
                        if ($range === '5000_15000'   && $min >= 5000  && $min <=15000) return true;
                        if ($range === '15000_50000'  && $min > 15000  && $min <=50000) return true;
                        if ($range === 'over_50000'   && $min > 50000)                  return true;
                    }
                    return false;
                });
            }

            if ($sort === 'price_asc') {
                $all = $all->sortBy($minOf)->values();
            } elseif ($sort === 'price_desc') {
                $all = $all->sortByDesc($minOf)->values();
            }

            // Pagination manuelle
            $perPage = 24;
            $page    = max(1, (int) $request->query('page', 1));
            $items   = $all->forPage($page, $perPage);

            $cards = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $all->count(),
                $perPage,
                $page,
                [
                    'path'  => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $cards = $query->paginate(24)->withQueryString();
        }

        // ============================================================
        // Données auxiliaires (sidebar / pills)
        // ============================================================
        $featuredMerchants = Reseller::where('kyc_status', 'approved')
            ->where('is_active', true)
            ->whereHas('merchantCards', fn ($q) => $q->where('is_active', true))
            ->withCount(['merchantCards' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('merchant_cards_count', 'desc')
            ->take(6)
            ->get();

        $availableCities = Reseller::where('kyc_status', 'approved')
            ->whereNotNull('city')
            ->whereHas('merchantCards', fn ($q) => $q->where('is_active', true))
            ->select('city')->distinct()->orderBy('city')->pluck('city');

        $activeFiltersCount = ($search !== '' ? 1 : 0)
            + ($categorySlug !== '' ? 1 : 0)
            + count($cities)
            + count($priceRanges);

        return view('gabon.index', [
            'cards'              => $cards,
            'featuredMerchants'  => $featuredMerchants,
            'availableCities'    => $availableCities,
            'categories'         => MerchantCard::CATEGORIES,
            'search'             => $search,
            'categorySlug'       => $categorySlug,
            'currentCategory'    => $categorySlug !== '' ? MerchantCard::CATEGORIES[$categorySlug] ?? null : null,
            'selectedCities'     => $cities,
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

        // Filtre via la relation reseller.business_type (= "catégorie du marchand")
        // mais aussi via merchant_card.category (= catégorie spécifique à la carte)
        $cards = MerchantCard::active()
            ->where(function ($q) use ($slug) {
                $q->where('category', $slug)
                  ->orWhereHas('reseller', fn ($r) => $r->where('business_type', $slug));
            })
            ->with('reseller:id,name,business_name,slug,city,logo_url')
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

    /** /gabon/marchand/{slug} — profil + cartes du marchand */
    public function merchant(string $slug)
    {
        $merchant = Reseller::where('slug', $slug)
            ->where('kyc_status', 'approved')
            ->where('is_active', true)
            ->firstOrFail();

        $cards = $merchant->merchantCards()
            ->where('is_active', true)
            ->latest('activated_at')
            ->get();

        return view('gabon.merchant', [
            'merchant'   => $merchant,
            'cards'      => $cards,
            'categories' => MerchantCard::CATEGORIES,
        ]);
    }

    /** /gabon/carte/{merchantCard} — détail + bouton acheter */
    public function card(MerchantCard $merchantCard)
    {
        // Seules les cartes actives d'un marchand approuvé sont publiquement consultables
        abort_unless(
            $merchantCard->is_active
            && $merchantCard->reseller
            && $merchantCard->reseller->kyc_status === 'approved'
            && $merchantCard->reseller->is_active,
            404
        );

        $merchantCard->load('reseller');

        // Suggestions : autres cartes du même marchand
        $otherCards = $merchantCard->reseller->merchantCards()
            ->where('is_active', true)
            ->where('id', '!=', $merchantCard->id)
            ->take(4)
            ->get();

        return view('gabon.card', [
            'card'       => $merchantCard,
            'merchant'   => $merchantCard->reseller,
            'otherCards' => $otherCards,
            'categories' => MerchantCard::CATEGORIES,
        ]);
    }
}
