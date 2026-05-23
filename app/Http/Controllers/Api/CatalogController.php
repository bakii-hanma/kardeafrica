<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductApiService;
use Illuminate\Http\Request;

/**
 * API publique du catalogue pour l'app mobile (Expo).
 *
 * Wrap ProductApiService — bénéficie automatiquement de toutes les règles
 * appliquées côté web (UAE bloqué, région tag, popular_in_africa, multi-page
 * catalog, conversion FCFA, recherche server-side afrikard via paramètre name).
 *
 * Le mobile remplace ses appels directs à afrikard-api.duckdns.org par
 * ces endpoints, et hérite gratuitement des améliorations.
 */
class CatalogController extends Controller
{
    public function __construct(private ProductApiService $service)
    {
    }

    /**
     * GET /api/v1/catalog
     * Filtres : search, category, country[], region[], price_range[], popular, sort
     * Pagination : page (1-indexed), per_page (default 20, max 100)
     */
    public function index(Request $request)
    {
        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $page    = max(1, (int) $request->input('page', 1));

        $regions = (array) $request->input('region', []);
        $regions = array_values(array_intersect($regions, ['europe', 'usa', 'africa', 'global']));

        $allowedSorts = ['popular', 'price_asc', 'price_desc', 'newest'];
        $sort = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'popular';

        $filters = [
            'search'       => $request->input('search'),
            'category'     => $request->input('category'),
            'price_range'  => (array) $request->input('price_range', []),
            'country'      => (array) $request->input('country', []),
            'region'       => $regions,
            'popular_only' => $request->boolean('popular'),
            'sort'         => $sort,
        ];

        $result = $this->service->getFilteredProducts($filters, $page, $perPage);
        $counts = $this->service->getCardTypeProductCounts();

        return response()->json([
            'success' => true,
            'data'    => array_map(fn($p) => $this->slimItem($p, withDetails: false, counts: $counts), $result['items']),
            'meta'    => [
                'total'        => $result['total'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
                'per_page'     => $result['per_page'],
            ],
            'filters_applied' => array_filter([
                'search'   => $filters['search'],
                'category' => $filters['category'],
                'region'   => $regions,
                'popular'  => $filters['popular_only'],
                'sort'     => $sort,
            ]),
        ]);
    }

    /**
     * GET /api/v1/catalog/{id}
     * Détail d'un produit/cardType (avec ses siblings).
     */
    public function show(string $id)
    {
        $counts = $this->service->getCardTypeProductCounts();

        // Essai produit individuel d'abord
        $product = $this->service->getProductById($id);
        if ($product) {
            return response()->json([
                'success' => true,
                'data'    => $this->slimItem($product, withDetails: true, counts: $counts),
                'siblings' => array_map(
                    fn($p) => $this->slimItem($p, withDetails: false, counts: $counts),
                    $product['siblings'] ?? $product['products'] ?? []
                ),
            ]);
        }

        // Sinon essai cardType
        $cardType = $this->service->getCardTypeById($id);
        if ($cardType) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'id'                     => $cardType['id'] ?? null,
                    'name'                   => $cardType['name'] ?? null,
                    'logoUrl'                => $cardType['logoUrl'] ?? null,
                    'description'            => $cardType['description'] ?? null,
                    'terms'                  => $cardType['terms'] ?? null,
                    'redemptionInstructions' => $cardType['redemptionInstructions'] ?? null,
                    'countryCode'            => $cardType['countryCode'] ?? null,
                    'currencyCode'           => $cardType['currencyCode'] ?? null,
                    'region'                 => $cardType['region'] ?? null,
                    'popular_in_africa'      => $cardType['popular_in_africa'] ?? false,
                ],
                'products' => array_map(
                    fn($p) => $this->slimItem($p),
                    $cardType['products'] ?? []
                ),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Produit introuvable.'], 404);
    }

    /**
     * GET /api/v1/currency-rates
     * Renvoie les taux FCFA actuels (EUR/USD/AED éditables admin) + le palier
     * d'arrondi. Le mobile cache en mémoire pour rester aligné sur l'admin
     * dans le cas dégradé où l'API catalogue tombe (fallback afrikard direct).
     */
    public function currencyRates()
    {
        return response()->json([
            'success'    => true,
            'rates'      => \App\Support\Money::currentRates(),
            'round_step' => \App\Support\Money::roundStep(),
        ]);
    }

    /**
     * GET /api/v1/categories
     * Liste fixe des catégories (entertainment, gaming, music, shopping, daywatch, travel).
     */
    public function categories()
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getCategories(),
        ]);
    }

    /**
     * GET /api/v1/catalog/popular
     * Top des cartes les plus populaires (Apple, Netflix, Spotify, Steam, etc.)
     * pour la home page mobile.
     */
    public function popular(Request $request)
    {
        $limit = min(50, max(1, (int) $request->input('limit', 12)));
        $cardTypes = $this->service->getCardTypes($limit);

        return response()->json([
            'success' => true,
            'data'    => array_map(function ($ct) {
                return [
                    'id'                => $ct['id'] ?? null,
                    'name'              => $ct['name'] ?? null,
                    'logoUrl'           => $ct['logoUrl'] ?? null,
                    'countryCode'       => $ct['countryCode'] ?? null,
                    'currencyCode'      => $ct['currencyCode'] ?? null,
                    'region'            => $ct['region'] ?? null,
                    'popular_in_africa' => $ct['popular_in_africa'] ?? false,
                    'products_count'    => count($ct['products'] ?? []),
                    'min_price_fcfa'    => $this->minPriceFcfa($ct['products'] ?? []),
                ];
            }, $cardTypes),
        ]);
    }

    /**
     * Allège un item du catalogue pour le réseau mobile : on ne renvoie que
     * ce dont l'app a besoin pour afficher / payer.
     */
    private function slimItem(array $p, bool $withDetails = false, array $counts = []): array
    {
        $ct = $p['cardType'] ?? [];
        $price = $p['price'] ?? [];
        $minNative = (int) ($p['minFaceValue'] ?? $price['min'] ?? 0);
        $cur       = $price['currencyCode'] ?? null;
        $ctId      = $ct['id'] ?? null;

        $out = [
            'id'                => $p['id'] ?? null,
            'name'              => $p['name'] ?? null,
            'price_fcfa'        => $minNative > 0 && $cur
                ? \App\Support\Money::toFcfa($minNative, $cur)
                : 0,
            'native_value'      => $minNative,
            'native_currency'   => $cur,
            'modifiedDate'      => $p['modifiedDate'] ?? null,
            'cardType'          => [
                'id'                => $ctId,
                'name'              => $ct['name'] ?? null,
                'logoUrl'           => $ct['logoUrl'] ?? null,
                'countryCode'       => $ct['countryCode'] ?? null,
                'region'            => $ct['region'] ?? null,
                'popular_in_africa' => $ct['popular_in_africa'] ?? false,
                'categories'        => $ct['categories'] ?? [],
                // Nombre total de montants disponibles pour ce cardType
                // (= autres siblings de la même marque/région). Utilisé par
                // l'app pour afficher "X montants disponibles" sur chaque carte.
                'products_count'    => $ctId !== null ? ($counts[$ctId] ?? 1) : 1,
            ],
        ];

        if ($withDetails) {
            $out['cardType']['description']            = $ct['description']            ?? null;
            $out['cardType']['terms']                  = $ct['terms']                  ?? null;
            $out['cardType']['redemptionInstructions'] = $ct['redemptionInstructions'] ?? null;
        }

        return $out;
    }

    private function minPriceFcfa(array $products): int
    {
        $min = PHP_INT_MAX;
        foreach ($products as $p) {
            $native = (int) ($p['minFaceValue'] ?? $p['price']['min'] ?? 0);
            $cur    = $p['price']['currencyCode'] ?? null;
            if ($native > 0 && $cur) {
                $fcfa = (int) \App\Support\Money::toFcfa($native, $cur);
                if ($fcfa > 0 && $fcfa < $min) $min = $fcfa;
            }
        }
        return $min === PHP_INT_MAX ? 0 : $min;
    }
}
