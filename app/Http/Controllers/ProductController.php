<?php

namespace App\Http\Controllers;

use App\Models\MerchantCard;
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

        // Cartes cadeaux LOCALES (Carte Gabon) — catalogue admin global.
        $gabonCards = MerchantCard::active()
            ->orderByDesc('total_sold')
            ->orderByDesc('activated_at')
            ->take(6)
            ->get();

        // Preuve sociale — compteurs branchés sur des données RÉELLES (pas de
        // chiffres inventés). "Cartes livrées" = cartes réellement remises au
        // client (UserCard) + cartes marchand encaissables générées.
        $cardsDelivered = \App\Models\UserCard::count()
            + \App\Models\MerchantCardPurchase::count();

        $homeStats = [
            'cards_delivered' => $cardsDelivered,
            'brands'          => max(300, is_array($cardTypes) ? count($cardTypes) : 0),
            // Délai de livraison affiché comme caractéristique produit (le code
            // arrive dès la confirmation du paiement), pas comme une mesure inventée.
            'delivery_label'  => '< 60 s',
        ];

        // Témoignages : à REMPLACER par de vrais avis clients avant la prod.
        // Structure éditable ici (ou à brancher sur une table `testimonials`).
        $testimonials = config('marketing.testimonials', []);

        return view('welcome', compact('cardTypes', 'categories', 'gabonCards', 'homeStats', 'testimonials'));
    }

    /**
     * Afficher la boutique avec tous les produits individuels
     */
    public function boutique(Request $request)
    {
        // LOT 2.1 — `/boutique?page=2` renvoyait silencieusement la page 1.
        // Ce n'est pas la pagination qui était cassée : le mode LIGNES (défaut)
        // n'en a pas, par conception. L'URL était donc trompeuse — elle promet
        // une page 2 et sert la même chose.
        // On redirige vers le mode LISTING, seul mode paginé, en conservant
        // TOUS les autres filtres.
        if ((int) $request->get('page', 1) > 1 && $request->get('view') !== 'all') {
            return redirect()->route('boutique', $request->query() + ['view' => 'all']);
        }

        $page = $request->get('page', 1);
        $perPage = 12;

        $allowedSorts = ['popular', 'price_asc', 'price_desc', 'newest', 'promo'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'popular';

        $regions = (array) $request->get('region', []);
        $regions = array_values(array_intersect($regions, ['europe', 'usa', 'africa', 'global']));

        // §4 — filtre pays PRODUIT (code de région-lock, ex. France = FR). Distinct
        // du filtre `country` historique (zones de devise africaines XOF/XAF).
        $loc = array_values(array_intersect(
            array_map('strtoupper', (array) $request->get('loc', [])),
            ['FR', 'US', 'GB', 'DE', 'IT', 'ES', 'BE', 'CA', 'EU']
        ));

        // P1 §C — filtre MARQUE dédié (?marque[]=Playstation), état unique avec
        // les pills et la sidebar. Nettoyage : chaînes non vides, max 10.
        $brands = collect((array) $request->get('marque', []))
            ->map(fn ($b) => trim((string) $b))
            ->filter()
            ->take(10)
            ->values()
            ->all();

        // P1 §5 — slider prix (FCFA de vente). Bornés/validés ; null = pas de contrainte.
        $priceMin = $request->filled('prix_min') ? max(0, (int) $request->get('prix_min')) : null;
        $priceMax = $request->filled('prix_max') ? max(0, (int) $request->get('prix_max')) : null;
        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }

        $filters = [
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
            // P1 §1 — listings dédoublonnés : UNE card par cardType (marque+région),
            // prix « à partir de » + mini-montants. Jamais PSN 10/20/50 en doublons.
            'group'        => true,
        ];

        $categories = $this->productService->getCategories();

        // P1 §2 — deux modes d'affichage :
        //  - LIGNES (défaut) : sections thématiques (règle CatalogRows), pas de
        //    pagination. La recherche LIBRE (non-marque) reste un listing plat.
        //  - LISTING (?view=all, « Voir tout » d'une ligne) : grille + pagination.
        $viewAll  = $request->get('view') === 'all';
        $rowsMode = !$viewAll
            && (empty($filters['search']) || \App\Support\CatalogRows::isBrandSearch($filters['search']));

        $rows = null;
        $rowsTruncated = false;
        if ($rowsMode) {
            // Toutes les cartes groupées filtrées — pas de pagination en mode
            // lignes, chaque ligne plafonne à 12 + Voir tout. perPage > total
            // groupé (~2 450) : à 2 000, les cartes en fin de tri (ex. Rewarble
            // ChatGPT) disparaissaient des lignes.
            $all  = $this->productService->getFilteredProducts($filters, 1, 5000);
            $rows = \App\Support\CatalogRows::build(
                $all['items'],
                \App\Support\CatalogRows::mode($filters),
                $categories,
            );
            // Plafond de LIGNES (un combo large peut produire 300+ marques) :
            // au-delà, bouton global « Voir toutes les cartes » → listing.
            if (count($rows) > 12) {
                $rows = array_slice($rows, 0, 12);
                $rowsTruncated = true;
            }
            $result = ['items' => [], 'total' => $all['total'], 'current_page' => 1, 'last_page' => 1, 'per_page' => $perPage];
        } else {
            $result = $this->productService->getFilteredProducts($filters, $page, $perPage);
        }

        $products = $result['items'];
        $pagination = [
            'total'        => $result['total'],
            'current_page' => $result['current_page'],
            'last_page'    => $result['last_page'],
            'per_page'     => $result['per_page'],
        ];

        // Map [cardTypeId => productsCount] pour afficher "X montants disponibles"
        // sur chaque carte du listing. Cache léger côté service.
        $cardTypeCounts = $this->productService->getCardTypeProductCounts();

        $search            = $filters['search'];
        $categoryId        = $filters['category'];
        $priceRange        = $filters['price_range'] ?? [];
        $selectedCountries = $filters['country'] ?? [];
        $selectedLoc       = $filters['country_code'];
        $selectedRegions   = $filters['region'];
        $selectedBrands    = $filters['brand'];
        $popularOnly       = $filters['popular_only'];

        // P1 §4/§5 — bornes du slider + compteurs facetés (tolérants aux pannes)
        try {
            $priceBounds = $this->productService->getPriceBounds();
            $facets      = $this->productService->getFacetCounts($filters);

            // LOT 2.4 — un rayon à 0 produit ne doit pas s'afficher : il donne
            // l'impression d'un catalogue en panne. Daywatch fait exception :
            // il porte des produits LOCAUX, absents des facettes afrikard, et
            // le client a demandé qu'il reste visible.
            $categories = array_values(array_filter(
                $categories,
                fn ($c) => (int) ($c['id'] ?? 0) === 5
                    || ($facets['categories'][$c['id'] ?? null] ?? 0) > 0,
            ));
        } catch (\Throwable $e) {
            $priceBounds = ['min' => 0, 'max' => 100000];
            $facets      = ['categories' => [], 'regions' => [], 'brands' => []];
        }

        return view('boutique', compact(
            'products', 'categories', 'search', 'categoryId',
            'priceRange', 'selectedCountries', 'selectedRegions',
            'popularOnly', 'pagination', 'sort', 'cardTypeCounts', 'selectedLoc',
            'rows', 'viewAll', 'rowsTruncated', 'selectedBrands',
            'priceMin', 'priceMax', 'priceBounds', 'facets'
        ));
    }

    /**
     * Afficher les produits par catégorie — redirige vers la boutique avec le filtre
     * pour profiter du même design / pagination.
     */
    public function category($categoryId, Request $request)
    {
        // 301 (audit SEO) : redirection permanente — consolide le signal sur
        // /boutique au lieu de laisser Google garder les anciennes URLs /category/N.
        return redirect()->route('boutique', array_merge(
            $request->except('page'),
            ['category' => $categoryId]
        ), 301);
    }

    /**
     * Afficher un type de carte avec ses produits
     */
    public function cardType($cardTypeId, $montant = null)
    {
        $cardType = $this->productService->getCardTypeById($cardTypeId);

        if (!$cardType) {
            abort(404, 'Type de carte non trouvé');
        }

        $products = $cardType['products'] ?? [];

        // P1 §1 — résolution de la VARIANTE depuis l'URL (/card-type/{id}/{montant}).
        // Montant inconnu → redirection vers la fiche racine (pas de 404 : le
        // catalogue évolue, les vieux liens partagés doivent retomber sur la carte).
        if ($montant !== null) {
            $selected = \App\Support\CatalogGrouping::resolveVariantByFace($products, (float) $montant);
            if (!$selected) {
                return redirect()->route('card-type.show', $cardTypeId);
            }
        } else {
            // Racine : variante par défaut (la moins chère — « à partir de »).
            $selected = \App\Support\CatalogGrouping::defaultVariant($products);
        }

        $categories = $this->productService->getCategories();

        // §9 — suggestions « Dans la même catégorie » : autres MARQUES uniquement
        // (jamais la même carte à d'autres montants), même région en priorité.
        $suggestions = $this->sameCategorySuggestions($cardType);

        // §9 secondaire — « la même carte dans d'autres régions » (Xbox EU, Xbox US…)
        $otherRegions = $this->otherRegionVariants($cardType);

        return view('card-type', [
            'cardType'          => $cardType,
            'categories'        => $categories,
            'selectedVariantId' => $selected['id'] ?? null,
            'suggestions'       => $suggestions,
            'otherRegions'      => $otherRegions,
        ]);
    }

    /**
     * P1 §9 — même MARQUE, autres régions/devises (ex. fiche Xbox FR →
     * Xbox EU, Xbox US). Section clairement séparée des suggestions catégorie.
     */
    private function otherRegionVariants(array $cardType, int $limit = 4): array
    {
        try {
            $brand = \App\Support\CatalogRows::brandKey($cardType['name'] ?? '');
            if ($brand === '') {
                return [];
            }

            $result = $this->productService->getFilteredProducts([
                'brand' => [$brand],
                'group' => true,
            ], 1, 24);

            $currentName = mb_strtolower(trim($cardType['name'] ?? ''));
            $currentCc   = strtoupper($cardType['countryCode'] ?? '');

            return collect($result['items'] ?? [])
                ->reject(fn ($p) => mb_strtolower(trim($p['cardType']['name'] ?? '')) === $currentName
                    && strtoupper($p['cardType']['countryCode'] ?? '') === $currentCc)
                ->take($limit)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * P1 §7 — autocomplétion de la recherche boutique (JSON).
     * Synonymes (« psn » → Playstation) + tolérance typo (« playstasion »).
     */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        try {
            $items = $this->productService->suggestCards($q, 8);
        } catch (\Throwable $e) {
            $items = [];
        }

        return response()->json(['items' => $items]);
    }

    /**
     * Autres marques de la même catégorie (fiche produit, anti-doublon P1 §9).
     * Même région priorisée ; jamais le cardType courant.
     */
    private function sameCategorySuggestions(array $cardType, int $limit = 4): array
    {
        try {
            $categoryId = $cardType['categories'][0]['id'] ?? null;
            if (!$categoryId) {
                return [];
            }

            $result = $this->productService->getFilteredProducts([
                'category' => $categoryId,
                'group'    => true,
            ], 1, 24);

            $currentId = $cardType['internalId'] ?? $cardType['id'] ?? null;
            $currentCc = strtoupper($cardType['countryCode'] ?? '');

            return collect($result['items'] ?? [])
                ->reject(fn ($p) => ($p['cardType']['internalId'] ?? $p['cardType']['id'] ?? null) == $currentId)
                // Même région d'abord, puis l'ordre « populaire » existant
                ->sortBy(fn ($p) => strtoupper($p['cardType']['countryCode'] ?? '') === $currentCc ? 0 : 1)
                ->take($limit)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
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

        // P1 §1 — /product/{id} est l'ANCIENNE fiche par montant : 301 permanent
        // vers l'URL de variante canonique /card-type/{ctId}/{montant}. Fallback
        // sur l'ancien rendu si la carte n'a pas de cardType résolvable.
        $ctId = $product['cardType']['internalId'] ?? $product['cardType']['id'] ?? null;
        $face = (float) ($product['minFaceValue'] ?? 0);
        if ($ctId && $face > 0) {
            $montant = fmod($face, 1.0) === 0.0 ? (string) (int) $face : rtrim(rtrim(number_format($face, 2, '.', ''), '0'), '.');
            return redirect()->route('card-type.variant', [$ctId, $montant], 301);
        }
        if ($ctId) {
            return redirect()->route('card-type.show', $ctId, 301);
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