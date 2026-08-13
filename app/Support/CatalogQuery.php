<?php

namespace App\Support;

use App\Services\ProductApiService;
use Illuminate\Http\Request;

/**
 * CatalogQuery
 * ============
 * Traitement UNIQUE des filtres du catalogue, partagé par la boutique publique
 * (/boutique) et l'écran de vente revendeur (/vendor/sell).
 *
 * Les deux écrans divergeaient : la boutique gérait marques, régions, pays
 * produit, curseur de prix, « Top Afrique », groupement par cardType et
 * compteurs facetés, quand l'espace vendeur n'avait que recherche, catégorie,
 * tranche de prix et pays. Un revendeur ne pouvait donc pas filtrer comme un
 * client — alors qu'il vend le même catalogue.
 *
 * Cette classe ne rend rien : elle prépare la charge utile de la vue.
 */
class CatalogQuery
{
    public const SORTS = ['popular', 'price_asc', 'price_desc', 'newest', 'promo'];

    /** Régions proposées au filtre. */
    private const REGIONS = ['europe', 'usa', 'africa', 'global'];

    /** Codes pays « produit » (verrou de région d'une carte). */
    private const LOCS = ['FR', 'US', 'GB', 'DE', 'IT', 'ES', 'BE', 'CA', 'EU'];

    public function __construct(private ProductApiService $service) {}

    /**
     * Construit le jeu de filtres à partir de la requête. Toutes les entrées
     * sont validées ici : une valeur hors liste est ignorée, jamais propagée.
     */
    public function filters(Request $request): array
    {
        $sort = in_array($request->get('sort'), self::SORTS, true)
            ? $request->get('sort')
            : 'popular';

        $regions = array_values(array_intersect((array) $request->get('region', []), self::REGIONS));

        $loc = array_values(array_intersect(
            array_map('strtoupper', (array) $request->get('loc', [])),
            self::LOCS
        ));

        $brands = collect((array) $request->get('marque', []))
            ->map(fn ($b) => trim((string) $b))
            ->filter()
            ->take(10)
            ->values()
            ->all();

        $priceMin = $request->filled('prix_min') ? max(0, (int) $request->get('prix_min')) : null;
        $priceMax = $request->filled('prix_max') ? max(0, (int) $request->get('prix_max')) : null;
        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }

        return [
            'search'       => $request->get('search'),
            'category'     => $request->get('category'),
            'price_range'  => $request->get('price_range'),
            'price_min'    => $priceMin,
            'price_max'    => $priceMax,
            'country'      => $request->get('country'),
            'country_code' => $loc,
            'region'       => $regions,
            'brand'        => $brands,
            'popular_only' => $request->boolean('popular'),
            'sort'         => $sort,
            // Une card par cardType (marque + région) : jamais PSN 10/20/50 en
            // triplons, avec prix « à partir de » et mini-montants.
            'group'        => true,
        ];
    }

    /**
     * Charge utile complète d'un écran catalogue : produits, pagination,
     * facettes, bornes du curseur et compteurs de montants.
     *
     * @param bool $withRows true = mode LIGNES thématiques (boutique publique),
     *                       false = grille paginée simple (écran vendeur).
     */
    public function payload(Request $request, int $perPage = 24, bool $withRows = true): array
    {
        $filters    = $this->filters($request);
        $categories = $this->service->getCategories();
        $page       = max(1, (int) $request->get('page', 1));

        $viewAll  = $request->get('view') === 'all';
        $rowsMode = $withRows && !$viewAll
            && (empty($filters['search']) || CatalogRows::isBrandSearch($filters['search']));

        $rows          = null;
        $rowsTruncated = false;

        if ($rowsMode) {
            // Pas de pagination en mode lignes : chaque ligne plafonne à 12 +
            // « Voir tout ». perPage large pour ne perdre aucune marque en fin de tri.
            $all  = $this->service->getFilteredProducts($filters, 1, 5000);
            $rows = CatalogRows::build($all['items'], CatalogRows::mode($filters), $categories);

            if (count($rows) > 12) {
                $rows = array_slice($rows, 0, 12);
                $rowsTruncated = true;
            }

            $result = [
                'items' => [], 'total' => $all['total'],
                'current_page' => 1, 'last_page' => 1, 'per_page' => $perPage,
            ];
        } else {
            $result = $this->service->getFilteredProducts($filters, $page, $perPage);
        }

        // Facettes et bornes : tolérantes à une panne du catalogue — un écran
        // sans compteurs vaut mieux qu'un écran en erreur.
        try {
            $priceBounds = $this->service->getPriceBounds();
            $facets      = $this->service->getFacetCounts($filters);
        } catch (\Throwable) {
            $priceBounds = ['min' => 0, 'max' => 100000];
            $facets      = ['categories' => [], 'regions' => [], 'brands' => []];
        }

        return [
            'products'          => $result['items'],
            'pagination'        => [
                'total'        => $result['total'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
                'per_page'     => $result['per_page'],
            ],
            'categories'        => $categories,
            'rows'              => $rows,
            'rowsTruncated'     => $rowsTruncated,
            'viewAll'           => $viewAll,
            'cardTypeCounts'    => $this->service->getCardTypeProductCounts(),
            'priceBounds'       => $priceBounds,
            'facets'            => $facets,
            // Décomposé pour les vues, qui raisonnent en « états sélectionnés ».
            'search'            => $filters['search'],
            'categoryId'        => $filters['category'],
            'priceRange'        => $filters['price_range'] ?? [],
            'priceMin'          => $filters['price_min'],
            'priceMax'          => $filters['price_max'],
            'selectedCountries' => $filters['country'] ?? [],
            'selectedLoc'       => $filters['country_code'],
            'selectedRegions'   => $filters['region'],
            'selectedBrands'    => $filters['brand'],
            'popularOnly'       => $filters['popular_only'],
            'sort'              => $filters['sort'],
        ];
    }
}
