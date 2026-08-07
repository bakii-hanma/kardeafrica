<?php

namespace App\Http\Controllers;

use App\Services\ProductApiService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductApiService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Afficher la page d'accueil avec les types de cartes
     */
    public function home()
    {
        // Liste curée EU/FR : Apple, Netflix, Steam, PSN, Nintendo, Xbox,
        // Spotify, Google Play, Roblox (demande produit "cartes Mr Franck").
        $cardTypes = $this->productService->getFeaturedCardTypes();

        // Repli : si le catalogue afrikard ne renvoie pas encore les marques
        // curées (cache froid), on affiche le top marques générique.
        if (empty($cardTypes)) {
            $cardTypes = $this->productService->getCardTypes(12);
        }

        $categories = $this->productService->getCategories();

        return view('welcome', compact('cardTypes', 'categories'));
    }

    /**
     * Afficher la boutique avec tous les produits individuels
     */
    public function boutique(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 12;

        $allowedSorts = ['popular', 'price_asc', 'price_desc', 'newest'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'popular';

        $regions = (array) $request->get('region', []);
        $regions = array_values(array_intersect($regions, ['europe', 'usa', 'africa', 'global']));

        $filters = [
            'search'       => $request->get('search'),
            'category'     => $request->get('category'),
            'price_range'  => $request->get('price_range'),
            'country'      => $request->get('country'),
            'region'       => $regions,
            'popular_only' => $request->boolean('popular'),
            'sort'         => $sort,
        ];

        $result = $this->productService->getFilteredProducts($filters, $page, $perPage);

        $products = $result['items'];
        $pagination = [
            'total'        => $result['total'],
            'current_page' => $result['current_page'],
            'last_page'    => $result['last_page'],
            'per_page'     => $result['per_page'],
        ];

        $categories = $this->productService->getCategories();

        // Map [cardTypeId => productsCount] pour afficher "X montants disponibles"
        // sur chaque carte du listing. Cache léger côté service.
        $cardTypeCounts = $this->productService->getCardTypeProductCounts();

        $search            = $filters['search'];
        $categoryId        = $filters['category'];
        $priceRange        = $filters['price_range'] ?? [];
        $selectedCountries = $filters['country'] ?? [];
        $selectedRegions   = $filters['region'];
        $popularOnly       = $filters['popular_only'];

        return view('boutique', compact(
            'products', 'categories', 'search', 'categoryId',
            'priceRange', 'selectedCountries', 'selectedRegions',
            'popularOnly', 'pagination', 'sort', 'cardTypeCounts'
        ));
    }

    /**
     * Afficher les produits par catégorie — redirige vers la boutique avec le filtre
     * pour profiter du même design / pagination.
     */
    public function category($categoryId, Request $request)
    {
        return redirect()->route('boutique', array_merge(
            $request->except('page'),
            ['category' => $categoryId]
        ));
    }

    /**
     * Afficher un type de carte avec ses produits
     */
    public function cardType($cardTypeId)
    {
        $cardType = $this->productService->getCardTypeById($cardTypeId);
        
        if (!$cardType) {
            abort(404, 'Type de carte non trouvé');
        }

        $categories = $this->productService->getCategories();

        return view('card-type', compact('cardType', 'categories'));
    }

    /**
     * Afficher les détails d'un produit individuel
     */
    public function show($productId)
    {
        $product = $this->productService->getProductById($productId);

        if (!$product) {
            abort(404, 'Produit non trouvé');
        }

        // getProductById renvoie toutes les dénominations de la même carte (même
        // type) dans $product['products']. On les capture AVANT de filtrer.
        $siblings  = $product['products'] ?? [];
        $currentId = $product['id'] ?? $productId;
        $brandLogo = $product['cardType']['logoUrl'] ?? ($product['logoUrl'] ?? null);

        // La fiche n'affiche QUE la carte sélectionnée (plus la grille de toutes
        // les dénominations).
        if (!empty($siblings)) {
            $selected = collect($siblings)->firstWhere('id', $currentId);
            $product['products'] = $selected ? [$selected] : [collect($siblings)->first()];
        }

        // Cartes similaires = les AUTRES dénominations de la même carte (même
        // type), telles qu'elles étaient sélectionnables avant, désormais placées
        // en section « Produits similaires ». Le template attend internalId/name/
        // logoUrl : on mappe l'id du sibling + le logo de la marque (partagé).
        $similarProducts = collect($siblings)
            ->reject(fn ($s) => ($s['id'] ?? null) == $currentId)
            ->map(fn ($s) => [
                'internalId' => $s['id'] ?? null,
                'name'       => $s['name'] ?? '',
                'logoUrl'    => $brandLogo,
                'price'      => $s['price'] ?? null,
            ])
            ->filter(fn ($s) => !empty($s['internalId']))
            ->values()
            ->all();

        return view('products.show', compact('product', 'similarProducts'));
    }

    /**
     * API pour récupérer les produits (AJAX)
     */
    public function apiProducts(Request $request)
    {
        $pageIndex = $request->get('page', 0);
        $pageSize = $request->get('size', 20);
        $search = $request->get('search');
        $categoryId = $request->get('category');

        try {
            if ($search) {
                $products = $this->productService->searchProducts($search, $pageIndex, $pageSize);
            } elseif ($categoryId) {
                $products = $this->productService->getProductsByCategory($categoryId, $pageIndex, $pageSize);
            } else {
                $catalog = $this->productService->getCatalog($pageIndex, $pageSize);
                $products = $catalog['items'] ?? [];
            }

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits'
            ], 500);
        }
    }

    /**
     * API pour récupérer les catégories
     */
    public function apiCategories()
    {
        try {
            $categories = $this->productService->getCategories();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des catégories'
            ], 500);
        }
    }

    /**
     * Recherche de produits — délègue à la boutique (qui gère déjà filtres,
     * pagination, tri, et hit la même recherche server-side afrikard).
     * Évite de maintenir deux UIs en double.
     */
    public function search(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        return redirect()->route('boutique', $query !== '' ? ['search' => $query] : []);
    }
} 