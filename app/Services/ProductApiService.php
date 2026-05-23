<?php

namespace App\Services;

use App\Models\DaywatchProduct;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductApiService
{
    private $baseUrl;
    private $cacheDuration = 3600; // 1 heure

    /**
     * Pays autorisés pour l'affichage (France, Global, US uniquement)
     */
    private $allowedCountries = ['FR', 'US', 'GLOBAL', 'GL'];
    private $allowedCountryNames = ['france', 'global', 'united states', 'usa', 'états-unis', 'etats-unis'];

    /**
     * Pays bloqués : cartes inutilisables en Afrique (UAE / Émirats Arabes Unis).
     * Détectés par brand.countryCode='AE', currencyCode='AED' ou nom contenant "UAE"/"Emirates".
     */
    private $blockedCountries = ['AE'];
    private $blockedCurrencies = ['AED'];

    /**
     * Mapping pays → région pour l'affichage et le filtre.
     */
    private $regionMap = [
        // Europe
        'FR' => 'europe', 'BE' => 'europe', 'DE' => 'europe', 'IT' => 'europe',
        'ES' => 'europe', 'PT' => 'europe', 'NL' => 'europe', 'GB' => 'europe',
        'IE' => 'europe', 'LU' => 'europe', 'CH' => 'europe', 'AT' => 'europe',
        'DK' => 'europe', 'SE' => 'europe', 'NO' => 'europe', 'FI' => 'europe',
        'PL' => 'europe', 'CZ' => 'europe', 'GR' => 'europe', 'HU' => 'europe',
        'RO' => 'europe', 'BG' => 'europe', 'SK' => 'europe', 'SI' => 'europe',
        'HR' => 'europe', 'EE' => 'europe', 'LV' => 'europe', 'LT' => 'europe',
        'MT' => 'europe', 'CY' => 'europe', 'EU' => 'europe',
        // Amérique du Nord
        'US' => 'usa', 'CA' => 'usa',
        // Afrique
        'ZA' => 'africa', 'NG' => 'africa', 'KE' => 'africa', 'MA' => 'africa',
        'TN' => 'africa', 'EG' => 'africa', 'DZ' => 'africa', 'GH' => 'africa',
        'SN' => 'africa', 'CI' => 'africa', 'CM' => 'africa', 'GA' => 'africa',
        // Mondial
        'GLC' => 'global', 'WW' => 'global', 'GLOBAL' => 'global', 'GL' => 'global',
    ];

    /**
     * Marques considérées les plus populaires en Afrique.
     * Mises en avant sur la page d'accueil + filtre dédié dans la boutique.
     */
    private $popularBrands = [
        'steam', 'psn', 'playstation', 'xbox', 'roblox', 'riot',
        'league of legends', 'valorant', 'epic games', 'epic', 'fortnite',
        'netflix', 'spotify', 'apple', 'itunes', 'app store', 'google play',
    ];

    public function __construct()
    {
        $this->baseUrl = config('services.product_api.base_url', 'https://afrikard-api.duckdns.org/api/v1');
    }

    /**
     * Test rapide de la connectivité afrikard.
     * Renvoie un tableau structuré pour l'admin (JSON-friendly).
     */
    public function ping(int $timeout = 8): array
    {
        $start = microtime(true);
        try {
            $res = Http::timeout($timeout)->get("{$this->baseUrl}/catalog", [
                'pageIndex' => 0,
                'pageSize'  => 1,
            ]);

            $elapsed = (int) round((microtime(true) - $start) * 1000);

            if ($res->successful()) {
                $body = $res->json();
                $itemsCount = is_array($body['items'] ?? null) ? count($body['items']) : null;
                return [
                    'ok'         => true,
                    'status'     => $res->status(),
                    'latency_ms' => $elapsed,
                    'message'    => 'API afrikard joignable. Tu peux relancer les commandes.',
                    'items'      => $itemsCount,
                    'checked_at' => now()->toDateTimeString(),
                ];
            }

            return [
                'ok'         => false,
                'status'     => $res->status(),
                'latency_ms' => $elapsed,
                'message'    => "API afrikard répond avec un statut HTTP {$res->status()}.",
                'checked_at' => now()->toDateTimeString(),
            ];
        } catch (\Throwable $e) {
            $elapsed = (int) round((microtime(true) - $start) * 1000);
            $msg = $e->getMessage();
            $kind = match (true) {
                str_contains($msg, 'Could not resolve host') => 'DNS — l\'hôte ne se résout pas',
                str_contains($msg, 'Connection timed out')
                    || str_contains($msg, 'Failed to connect')
                    || str_contains($msg, 'Timeout was reached') => 'Timeout réseau (le serveur ne répond pas)',
                str_contains($msg, 'SSL')                  => 'Erreur SSL/TLS',
                default                                    => 'Erreur réseau',
            };
            return [
                'ok'         => false,
                'status'     => null,
                'latency_ms' => $elapsed,
                'message'    => $kind,
                'detail'     => $msg,
                'checked_at' => now()->toDateTimeString(),
            ];
        }
    }

    /**
     * Vérifier si un produit appartient à un pays autorisé
     */
    private function isAllowedCountry($product): bool
    {
        // Vérifier par country.name
        $countryName = strtolower($product['country']['name'] ?? '');
        if ($countryName && in_array($countryName, $this->allowedCountryNames)) {
            return true;
        }

        // Vérifier par brand.countryCode ou cardType.countryCode
        $countryCode = strtoupper($product['brand']['countryCode'] ?? $product['cardType']['countryCode'] ?? '');
        if ($countryCode && in_array($countryCode, $this->allowedCountries)) {
            return true;
        }

        // Vérifier par country.isoCode ou country.code (au cas où)
        $isoCode = strtoupper($product['country']['isoCode'] ?? $product['country']['code'] ?? '');
        if ($isoCode && in_array($isoCode, $this->allowedCountries)) {
            return true;
        }

        return false;
    }

    /**
     * Récupérer le catalogue complet (première page seulement, conservé pour
     * la rétrocompatibilité). Pour TOUT le catalogue paginé, utiliser
     * fetchAllCatalogPages().
     */
    public function getCatalog($pageIndex = 0, $pageSize = 100)
    {
        $cacheKey = "catalog_page_{$pageIndex}_size_{$pageSize}";

        // Si on a deja un catalog non-null en cache, on le renvoie
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/catalog", [
                'pageIndex' => $pageIndex,
                'pageSize'  => $pageSize,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Cache::put($cacheKey, $data, $this->cacheDuration);
                return $data;
            }

            Log::warning('API catalogue HTTP ' . $response->status());
            return null;
        } catch (\Exception $e) {
            Log::error('Erreur API catalogue: ' . $e->getMessage());
            // On NE met PAS de null en cache : retry au prochain appel
            return null;
        }
    }

    /**
     * Récupère TOUT le catalogue afrikard en itérant sur les pages.
     * - Skippe les pages qui renvoient HTTP 500 (l'API afrikard a des bugs
     *   sur certains pageIndex, notamment 1, 2, 13).
     * - Dédoublonne par `id`.
     * - S'arrête après 3 pages consécutives vides ou en erreur.
     * - Limite dure à 50 pages pour éviter une boucle infinie.
     *
     * Sans cette méthode, l'app ne récupérait QUE 198 produits (page 0)
     * alors que l'API en a ~3700+ — toutes les cartes européennes (Fnac,
     * Darty, FNAC Belgium...) sont sur les pages suivantes.
     */
    public function fetchAllCatalogPages(int $pageSize = 100, int $maxPages = 50, ?int $timeBudgetSec = null): array
    {
        $cacheKey = "catalog_all_pages_v3_robust_size_{$pageSize}";

        // Lecture cache manuelle pour pouvoir filtrer les cache "partiels"
        // (un fetch foireux de 39 items ne doit pas polluer le cache 1h).
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && count($cached) >= 500) {
                return $cached;
            }
            // Sinon : cache fragment trop petit → on l'oublie et refetch
            Cache::forget($cacheKey);
        }

        // En CLI on a tout notre temps (limite enlevée). En HTTP on étend à 180s
        // car l'itération afrikard prend ~70s à froid — sinon timeout fatal sous
        // max_execution_time PHP (souvent 60s).
        if (function_exists('set_time_limit')) {
            @set_time_limit(app()->runningInConsole() ? 0 : 180);
        }

        // Budget mur (HTTP-safe). En CLI (commande catalog:warm) on passe null → pas de limite.
        $deadline = $timeBudgetSec !== null ? microtime(true) + $timeBudgetSec : null;

        $all = [];
        $seenIds = [];
        $consecutiveEmpty = 0;

        for ($i = 0; $i < $maxPages; $i++) {
            // Stop dès que le budget mur est dépassé : on rendra ce qu'on a, sans cacher.
            if ($deadline !== null && microtime(true) > $deadline) {
                Log::info("afrikard catalog: time budget reached at pageIndex={$i} (got " . count($all) . ' items)');
                break;
            }

            try {
                $response = Http::timeout(8)->get("{$this->baseUrl}/catalog", [
                    'pageIndex' => $i,
                    'pageSize'  => $pageSize,
                ]);

                if (!$response->successful()) {
                    Log::info("afrikard pageIndex={$i} → HTTP {$response->status()} (skip)");
                    // On NE break PAS sur les 500 : pages 1, 2, 13 sont known-flaky
                    // sur l'API afrikard. On continue d'essayer les suivantes.
                    continue;
                }

                $body  = $response->json();
                $items = $body['items'] ?? [];

                if (empty($items)) {
                    $consecutiveEmpty++;
                    // 5 pages vides d'affilée = fin du catalogue, on s'arrête
                    if ($consecutiveEmpty >= 5) break;
                    continue;
                }

                $consecutiveEmpty = 0;
                foreach ($items as $item) {
                    $id = $item['id'] ?? null;
                    if ($id === null || isset($seenIds[$id])) continue;
                    if ($this->isBlockedProduct($item)) continue;
                    $seenIds[$id] = true;
                    $all[]        = $item;
                }
            } catch (\Throwable $e) {
                Log::warning("afrikard pageIndex={$i} exception : " . $e->getMessage());
                continue;
            }
        }

        $timedOut = $deadline !== null && microtime(true) > $deadline;

        // Garde-fou : si on a moins de 500 produits, considérer que l'API a foiré.
        // On NE met PAS en cache pour que le prochain hit retente — sinon un échec
        // transitoire pollue le cache pendant 1h et casse les pages détail (404).
        // Si on a beaucoup de produits MAIS qu'on s'est arrêté sur timeout, on cache
        // pour 60s seulement : utilisateurs suivants servis vite, refresh rapide
        // pour récupérer le catalogue complet sans bloquer.
        if (count($all) >= 500) {
            $ttl = $timedOut ? 60 : $this->cacheDuration;
            Cache::put($cacheKey, $all, $ttl);
            Log::info('afrikard catalog: cached ' . count($all) . " products (ttl={$ttl}s, timedOut=" . ($timedOut ? 'yes' : 'no') . ')');
        } else {
            Log::warning('afrikard catalog: only ' . count($all) . ' items fetched — NOT caching (will retry next call)');
        }

        return $all;
    }

    /**
     * Recherche server-side directement sur afrikard via le paramètre `name`.
     * Beaucoup plus fiable que de filtrer le cache local car l'API renvoie
     * des résultats même pour les marques absentes du cache.
     */
    public function searchProductsViaApi(string $query, int $pageSize = 50): array
    {
        $query = trim($query);
        if ($query === '') return [];

        $cacheKey = 'api_search_v1_' . md5(strtolower($query)) . "_size_{$pageSize}";

        return Cache::remember($cacheKey, 1800, function () use ($query, $pageSize) {
            try {
                $response = Http::timeout(15)->get("{$this->baseUrl}/catalog", [
                    'name'      => $query,
                    'pageIndex' => 0,
                    'pageSize'  => $pageSize,
                ]);

                if (!$response->successful()) {
                    Log::warning("afrikard search '{$query}' → HTTP {$response->status()}");
                    return [];
                }

                $items = $response->json()['items'] ?? [];
                $filtered = array_filter($items, fn($p) => !$this->isBlockedProduct($p));
                return array_map(fn($p) => $this->processCatalogItem($p), array_values($filtered));
            } catch (\Throwable $e) {
                Log::warning("afrikard search '{$query}' exception : " . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Récupère uniquement la valeur native + currency pour un productId donné.
     * Cache catalogue d'abord, puis API ciblée. Utilisé à la création de
     * commande pour qu'on puisse stocker la valeur native sur l'OrderItem
     * et n'avoir PLUS besoin du catalogue au moment de la livraison.
     *
     * Retourne ['value' => int, 'currency' => string] ou null si introuvable.
     */
    public function lookupNativeValue(string|int $productId): ?array
    {
        $productId = (string) $productId;

        // 1. Cache catalogue
        $all = $this->fetchAndProcessAllProducts();
        foreach ($all as $p) {
            if ((string) ($p['id'] ?? '') === $productId) {
                $value = (int) round($p['minFaceValue'] ?? $p['price']['min'] ?? 0);
                $cur   = $p['price']['currencyCode'] ?? null;
                if ($value > 0) return ['value' => $value, 'currency' => $cur];
            }
        }

        // 2. API ciblée
        $light = $this->getProductByIdLight($productId);
        if ($light) {
            $value = (int) round($light['minFaceValue'] ?? $light['price']['min'] ?? 0);
            $cur   = $light['price']['currencyCode'] ?? null;
            if ($value > 0) return ['value' => $value, 'currency' => $cur];
        }

        return null;
    }

    /**
     * Lookup ciblé d'un produit par son ID via l'API afrikard. Utile quand le
     * cache catalogue est vide (juste après un déploiement, ou après outage)
     * pour récupérer la valeur native (EUR/USD/...) d'un produit spécifique
     * SANS recharger les 5800 items du catalogue complet.
     *
     * Retourne le produit traité (slim, avec cardType normalisé) ou null.
     */
    public function getProductByIdLight(string|int $productId): ?array
    {
        $productId = (string) $productId;
        if ($productId === '') return null;

        $cacheKey = "product_light_v1_{$productId}";

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) return $cached;
        }

        try {
            $response = Http::timeout(8)->get("{$this->baseUrl}/catalog", [
                'productId' => $productId,
                'pageSize'  => 5,
            ]);
            if (!$response->successful()) {
                Log::warning("afrikard productId={$productId} → HTTP {$response->status()}");
                return null;
            }
            $items = $response->json()['items'] ?? [];
            // Match strict sur l'ID (l'API peut renvoyer plusieurs items)
            foreach ($items as $item) {
                if ((string) ($item['id'] ?? '') !== $productId) continue;
                if ($this->isBlockedProduct($item)) return null;
                $processed = $this->processCatalogItem($item);
                Cache::put($cacheKey, $processed, $this->cacheDuration);
                return $processed;
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning("afrikard productId={$productId} exception : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Détermine si un produit doit être masqué :
     * cartes UAE (Émirats) — devise AED, pays AE, ou nom contenant UAE/Emirates.
     * Ces cartes ne sont pas utilisables en Afrique.
     */
    private function isBlockedProduct(array $product): bool
    {
        $cc = strtoupper($product['brand']['countryCode'] ?? $product['country']['isoCode'] ?? $product['cardType']['countryCode'] ?? '');
        if ($cc && in_array($cc, $this->blockedCountries, true)) return true;

        $cur = strtoupper($product['price']['currencyCode'] ?? '');
        if ($cur && in_array($cur, $this->blockedCurrencies, true)) return true;

        $brandName = strtolower($product['brand']['name'] ?? '');
        if (str_contains($brandName, 'uae') || str_contains($brandName, 'emirates')) return true;

        return false;
    }

    /**
     * Normalise un item brut afrikard : map brand → cardType, tag région, popularité.
     * Extrait de fetchAndProcessAllProducts() pour pouvoir réutiliser la même
     * logique sur les résultats de recherche server-side.
     */
    private function processCatalogItem(array $product): array
    {
        if (!isset($product['brand'])) return $product;

        $brand = $product['brand'];
        $brandName   = strtolower($brand['name'] ?? '');
        $productName = strtolower($product['name'] ?? '');

        // Tag région d'après le countryCode de la marque
        $cc = strtoupper($brand['countryCode'] ?? $product['country']['isoCode'] ?? '');

        // Construit un cardType SLIM : on garde uniquement ce qu'utilisent les vues
        // de listing (boutique, welcome, search). Les champs lourds (description,
        // terms, redemptionInstructions, ~6 KB par produit) sont retirés du cache
        // catalogue pour économiser ~70 % de la mémoire. Les pages détail qui en
        // ont besoin doivent passer par fetchRichCardType().
        $cardType = [
            'id'           => $brand['id'] ?? null,
            'internalId'   => $brand['id'] ?? null,
            'name'         => $brand['name'] ?? null,
            'logoUrl'      => $brand['logoUrl'] ?? null,
            'countryCode'  => $brand['countryCode'] ?? null,
            'currencyCode' => $brand['currencyCode'] ?? null,
            'region'       => $this->regionMap[$cc] ?? 'other',
            'region_code'  => $cc ?: null,
        ];

        // Tag popularité Afrique sans Collection (allouer un Collection pour 16
        // mots-clés × 5807 produits = beaucoup d'overhead inutile)
        $popular = false;
        foreach ($this->popularBrands as $kw) {
            if (str_contains($brandName, $kw) || str_contains($productName, $kw)) {
                $popular = true;
                break;
            }
        }
        $cardType['popular_in_africa'] = $popular;

        $product['cardType'] = $cardType;
        $categories = [];

        foreach ($this->getCategories() as $cat) {
            $match = match ((int) $cat['id']) {
                1 => collect(['netflix', 'disney', 'hulu', 'tv', 'movie', 'film', 'cinema', 'stream', 'subscription', 'plus', 'premium', 'twitch', 'crunchyroll', 'canal', 'dstv', 'showmax', 'startimes', 'youtube', 'prime', 'hbo', 'paramount', 'peacock', 'roku'])
                    ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k)),
                2 => collect(['playstation', 'xbox', 'nintendo', 'steam', 'roblox', 'minecraft', 'fortnite', 'pubg', 'game', 'gaming', 'fire', 'diamond', 'uc', 'credits', 'valorant', 'league', 'legend', 'fifa', 'nba', 'call of duty', 'cod', 'mobile legends', 'coins', 'points', 'card', 'code', 'brawl', 'clash', 'royale', 'apex', 'overwatch', 'blizzard', 'ea', 'ubisoft', 'psn', 'store'])
                    ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k)),
                3 => collect(['spotify', 'apple', 'itunes', 'music', 'deezer', 'shazam', 'song', 'audio', 'sound', 'tidal', 'napster', 'amazon music', 'youtube music', 'pandora'])
                    ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k)),
                4 => collect(['amazon', 'ebay', 'walmart', 'bestbuy', 'target', 'shopping', 'store', 'google play', 'apple store', 'gift', 'voucher', 'coupon', 'nike', 'adidas', 'shein', 'zalando', 'asos', 'fnac', 'darty', 'cdiscount', 'rakuten', 'alibaba', 'aliexpress', 'jumia', 'konga', 'sephora', 'decathlon', 'billetterie'])
                    ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k)),
                5 => collect(['daywatch', 'day watch'])
                    ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k)),
                6 => collect(['uber', 'airbnb', 'booking', 'expedia', 'travel', 'flight', 'hotel', 'train', 'bus', 'trip', 'bolt', 'yango', 'taxi', 'ride', 'fly', 'lyft', 'grab'])
                    ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k)),
                default => false,
            };

            if ($match) $categories[] = $cat;
        }

        $product['cardType']['categories'] = $categories;

        // Drop le bloc `brand` original (~6 KB par produit) maintenant qu'on a
        // copié ce qu'il fallait dans cardType. Drop aussi `country` lourd.
        // Économie : ~12 KB → ~1 KB par produit pour le cache catalogue.
        unset($product['brand'], $product['country']);

        return $product;
    }

    /**
     * Récupérer les produits par catégorie
     */
    public function getProductsByCategory($categoryId, $pageIndex = 0, $pageSize = 50)
    {
        // Daywatch (id=5) est servi depuis la BDD locale, pas l'API afrikard
        if ((int) $categoryId === 5) {
            return DaywatchProduct::where('is_active', true)
                ->orderBy('sort_order')
                ->skip($pageIndex * $pageSize)
                ->take($pageSize)
                ->get()
                ->map(fn($p) => $p->toCatalogItem())
                ->all();
        }

        $cacheKey = "category_{$categoryId}_page_{$pageIndex}_size_{$pageSize}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($categoryId, $pageIndex, $pageSize) {
            try {
                $allProducts = $this->getAllProducts(0, 99999); // Fetch all to filter locally
                
                return collect($allProducts)->filter(function ($product) use ($categoryId) {
                    $brandName = strtolower($product['cardType']['name'] ?? '');
                    $productName = strtolower($product['name'] ?? '');
                    
                    // Map category IDs to keywords/brands (Same logic as mobile app)
                    if ($categoryId == 1) { // Divertissement
                        return collect(['netflix', 'disney', 'hulu', 'tv', 'movie', 'film', 'cinema', 'stream', 'subscription', 'plus', 'premium', 'twitch', 'crunchyroll', 'canal', 'dstv', 'showmax', 'startimes', 'youtube', 'prime'])
                            ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k));
                    } elseif ($categoryId == 2) { // Jeux Vidéo
                        return collect(['playstation', 'xbox', 'nintendo', 'steam', 'roblox', 'minecraft', 'fortnite', 'pubg', 'game', 'gaming', 'fire', 'diamond', 'uc', 'credits', 'valorant', 'league', 'legend', 'fifa', 'nba', 'call of duty', 'cod', 'mobile legends', 'coins', 'points', 'card', 'code', 'brawl', 'clash', 'royale', 'apex', 'overwatch', 'blizzard', 'ea', 'ubisoft'])
                            ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k));
                    } elseif ($categoryId == 3) { // Musique
                        return collect(['spotify', 'apple', 'itunes', 'music', 'deezer', 'shazam', 'song', 'audio', 'sound', 'tidal', 'napster', 'amazon music', 'youtube music'])
                            ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k));
                    } elseif ($categoryId == 4) { // Shopping
                        return collect(['amazon', 'ebay', 'walmart', 'bestbuy', 'target', 'shopping', 'store', 'google play', 'apple store', 'gift', 'voucher', 'coupon', 'nike', 'adidas', 'shein', 'zalando', 'asos', 'fnac', 'darty', 'cdiscount', 'rakuten', 'alibaba', 'aliexpress', 'jumia', 'konga'])
                            ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k));
                    } elseif ($categoryId == 5) { // Daywatch
                        return collect(['daywatch', 'day watch', 'watch', 'security', 'monitoring', 'surveillance', 'protection'])
                            ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k));
                    } elseif ($categoryId == 6) { // Voyage
                        return collect(['uber', 'airbnb', 'booking', 'expedia', 'travel', 'flight', 'hotel', 'train', 'bus', 'trip', 'bolt', 'yango', 'taxi', 'ride', 'fly'])
                            ->contains(fn($k) => str_contains($brandName, $k) || str_contains($productName, $k));
                    }
                    
                    return false;
                })->values()->slice($pageIndex * $pageSize, $pageSize)->all();
            } catch (\Exception $e) {
                Log::error('Erreur filtrage par catégorie: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Récupérer les produits populaires (premiers produits de chaque catégorie)
     */
    public function getPopularProducts($limit = 12)
    {
        // Simplifié: retourne juste les premiers produits
        return $this->getAllProducts(0, $limit);
    }

    /**
     * Rechercher des produits
     */
    public function searchProducts($query, $pageIndex = 0, $pageSize = 50)
    {
        return $this->searchIndividualProducts($query, $pageIndex, $pageSize);
    }

    /**
     * Récupérer les catégories disponibles
     */
    public function getCategories()
    {
        return [
            ['id' => 1, 'name' => 'Divertissement', 'icon' => 'film', 'emoji' => '🎬'],
            ['id' => 2, 'name' => 'Jeux Vidéo', 'icon' => 'gamepad-2', 'emoji' => '🎮'],
            ['id' => 3, 'name' => 'Musique', 'icon' => 'music', 'emoji' => '🎵'],
            ['id' => 4, 'name' => 'Shopping', 'icon' => 'shopping-cart', 'emoji' => '🛍️'],
            ['id' => 5, 'name' => 'Daywatch', 'icon' => 'tv', 'emoji' => '📺'],
            ['id' => 6, 'name' => 'Voyage', 'icon' => 'map', 'emoji' => '✈️'],
        ];
    }

    /**
     * Récupérer un produit par ID
     */
    public function getProductById($productId)
    {
        // Daywatch (BDD locale)
        if (is_string($productId) && str_starts_with($productId, 'daywatch_')) {
            $localId = (int) substr($productId, 9);
            $dw = DaywatchProduct::find($localId);
            if (!$dw) return null;
            $item = $dw->toCatalogItem();
            $item['siblings'] = [$item];
            $item['categories'] = [['id' => 5, 'name' => 'Daywatch']];
            return $item;
        }

        $cacheKey = "product_v3_{$productId}";

        // Read-only cache check (skip si null en cache pour ne pas figer un 404 transitoire)
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) return $cached;
            Cache::forget($cacheKey);
        }

        try {
            $allProducts = $this->getAllProducts(0, 99999);
            $product = collect($allProducts)->firstWhere('id', $productId);

            if (!$product) return null; // pas de mise en cache du null

                // Find siblings (same brand)
                $brandId = $product['cardType']['id'] ?? null;
                $brandName = $product['cardType']['name'] ?? null;
                $siblings = [];

                if ($brandId) {
                    $siblings = collect($allProducts)->filter(function($p) use ($brandId) {
                        return isset($p['cardType']['id']) && $p['cardType']['id'] == $brandId;
                    })->values()->all();
                } 
                
                // Fallback to name match if ID didn't find siblings or only found self
                if (count($siblings) <= 1 && $brandName) {
                    $siblingsByName = collect($allProducts)->filter(function($p) use ($brandName) {
                        return isset($p['cardType']['name']) && $p['cardType']['name'] === $brandName;
                    })->values()->all();
                    
                    if (count($siblingsByName) > count($siblings)) {
                        $siblings = $siblingsByName;
                    }
                }
                
                // If still empty, use self
                if (empty($siblings)) {
                    $siblings = [$product];
                }

                // Ensure the current product is in the list if not found
                if (collect($siblings)->where('id', $productId)->isEmpty()) {
                    $siblings[] = $product;
                }
                
                // Sort by price
                $siblings = collect($siblings)->sortBy(function($p) {
                    return $p['price']['min'] ?? 0;
                })->values()->all();

                $product['products'] = $siblings;

                // Enrichit le produit avec les champs lourds (description, terms,
                // redemptionInstructions) qui sont retirés du cache catalogue slim.
                // Les vues products/show et card-type/show les attendent.
                $brandId = $product['cardType']['id'] ?? null;
                if ($brandId) {
                    $rich = $this->getCardTypeById($brandId);
                    if ($rich) {
                        $product['description']            = $rich['description']            ?? null;
                        $product['terms']                  = $rich['terms']                  ?? null;
                        $product['redemptionInstructions'] = $rich['redemptionInstructions'] ?? null;
                        // Aussi sur le cardType pour les vues qui lisent là-bas
                        $product['cardType']['description']            = $rich['description']            ?? null;
                        $product['cardType']['terms']                  = $rich['terms']                  ?? null;
                        $product['cardType']['redemptionInstructions'] = $rich['redemptionInstructions'] ?? null;
                    }
                }

                // Cache uniquement les hits valides — pas les misses
                Cache::put($cacheKey, $product, $this->cacheDuration);
                return $product;
        } catch (\Exception $e) {
            Log::error('Erreur produit par ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer tous les produits (traités mais non paginés).
     * Utilise fetchAllCatalogPages() pour itérer sur TOUTES les pages
     * d'afrikard (sans cette itération, on ne récupérait que ~200 produits
     * sur les ~3700+ disponibles, manquant la quasi-totalité des cartes
     * européennes — Fnac, Darty, FNAC Belgium, Fnac Billetterie, etc.).
     */
    private function fetchAndProcessAllProducts()
    {
        $cacheKey = 'processed_all_products_v5_slim';

        // Lecture manuelle pour pouvoir filtrer un cache "vide" pollué par un
        // outage afrikard (sinon empty array reste 1h en cache → toutes les
        // livraisons cartes échouent par lookup values manquant).
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && count($cached) >= 500) {
                return $cached;
            }
            Cache::forget($cacheKey);
        }

        try {
            // En CLI (warm cache) : pas de budget temps. En HTTP : budget 45s
            // pour rester sous max_execution_time même si réglé à 60s.
            $budget = app()->runningInConsole() ? null : 45;
            $items = $this->fetchAllCatalogPages(100, 50, $budget);
            if (empty($items)) return [];

            $processed = array_map(fn($p) => $this->processCatalogItem($p), $items);

            // Ne cache QUE si on a un dataset réaliste — pareil que fetchAllCatalogPages.
            if (count($processed) >= 500) {
                Cache::put($cacheKey, $processed, $this->cacheDuration);
            } else {
                Log::warning('processed_all_products: only ' . count($processed) . ' items, NOT caching');
            }
            return $processed;
        } catch (\Exception $e) {
            Log::error('Erreur traitement produits: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les produits filtrés avec pagination.
     * Quand un terme de recherche est fourni, on utilise la recherche
     * server-side d'afrikard (param `name`) en source primaire — sinon on
     * raterait toutes les marques absentes des ~200 premiers items du cache.
     */
    public function getFilteredProducts($filters = [], $page = 1, $perPage = 12)
    {
        $searchTerm = trim((string) ($filters['search'] ?? ''));

        if ($searchTerm !== '') {
            // Recherche server-side + fallback cache local, déjà dédoublonné
            $allProducts = $this->searchIndividualProducts($searchTerm, 0, 500);
        } else {
            $allProducts = $this->fetchAndProcessAllProducts();
        }

        // Inject Daywatch products (catégorie 5) — toujours présents indépendamment de l'API afrikard
        $daywatchItems = DaywatchProduct::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($p) {
                $item = $p->toCatalogItem();
                // Attache la catégorie Daywatch sur cardType pour que le filtre `category` fonctionne
                $item['cardType']['categories']         = [['id' => 5, 'name' => 'Daywatch']];
                $item['cardType']['region']             = 'africa';
                $item['cardType']['popular_in_africa'] = true; // produit local Kardafrica → mis en avant
                return $item;
            })
            ->all();

        // Si on est en mode recherche, on ne réinjecte Daywatch QUE s'il matche le terme
        if ($searchTerm !== '') {
            $term = strtolower($searchTerm);
            $daywatchItems = array_filter($daywatchItems, function ($p) use ($term) {
                return str_contains(strtolower($p['name'] ?? ''), $term)
                    || str_contains(strtolower($p['cardType']['name'] ?? ''), $term);
            });
        }
        $allProducts = array_merge($daywatchItems, $allProducts);

        // Filter (on ne refiltre PLUS le search si on a déjà tapé l'API search,
        // mais on laisse le filtre local au cas où la fonction est appelée ailleurs)
        $filtered = collect($allProducts)->filter(function ($product) use ($filters, $searchTerm) {
            $match = true;

            // Search local (uniquement si pas déjà fait via l'API)
            if ($searchTerm === '' && !empty($filters['search'])) {
                $term = strtolower($filters['search']);
                $name = strtolower($product['name'] ?? '');
                $brand = strtolower($product['cardType']['name'] ?? '');
                if (!str_contains($name, $term) && !str_contains($brand, $term)) {
                    $match = false;
                }
            }

            // Category
            if ($match && !empty($filters['category'])) {
                $catId = $filters['category'];
                $hasCat = collect($product['cardType']['categories'] ?? [])->contains('id', $catId);
                if (!$hasCat) {
                    $match = false;
                }
            }

            // Region (europe / usa / africa / global)
            if ($match && !empty($filters['region'])) {
                $regions = is_array($filters['region']) ? $filters['region'] : [$filters['region']];
                $productRegion = $product['cardType']['region'] ?? 'other';
                if (!in_array($productRegion, $regions, true)) {
                    $match = false;
                }
            }

            // Top Afrique : marques les plus populaires (Steam, PSN, Xbox, Netflix, Spotify, Apple...)
            if ($match && !empty($filters['popular_only'])) {
                if (empty($product['cardType']['popular_in_africa'])) {
                    $match = false;
                }
            }

            // Country
            if ($match && !empty($filters['country'])) {
                $countries = $filters['country'];
                $currency = $product['price']['currencyCode'] ?? 'XAF';
                
                // Mapping des devises aux régions
                $westAfrica = ['SN', 'CI', 'ML', 'BF', 'TG', 'BJ', 'NE', 'GW']; // XOF
                $centralAfrica = ['CM', 'GA', 'CG', 'TD', 'CF', 'GQ']; // XAF
                
                $isXOF = $currency === 'XOF';
                $isXAF = $currency === 'XAF';
                
                $selectedWest = !empty(array_intersect($countries, $westAfrica));
                $selectedCentral = !empty(array_intersect($countries, $centralAfrica));
                
                // Si l'utilisateur a sélectionné des pays d'Afrique de l'Ouest (XOF)
                // et que le produit est en XAF (Afrique Centrale), on masque
                if ($selectedWest && !$selectedCentral && $isXAF) {
                   $match = false;
                }
                
                // Si l'utilisateur a sélectionné des pays d'Afrique Centrale (XAF)
                // et que le produit est en XOF (Afrique de l'Ouest), on masque
                if ($selectedCentral && !$selectedWest && $isXOF) {
                   $match = false;
                }
            }

            // Price Range
            if ($match && !empty($filters['price_range'])) {
                $price = $product['price']['min'] ?? 0;
                $priceMatch = false;
                foreach ($filters['price_range'] as $range) {
                    if ($range == 'under_1000' && $price < 1000) $priceMatch = true;
                    if ($range == '1000_5000' && $price >= 1000 && $price <= 5000) $priceMatch = true;
                    if ($range == '5000_20000' && $price > 5000 && $price <= 20000) $priceMatch = true;
                    if ($range == 'over_20000' && $price > 20000) $priceMatch = true;
                }
                if (!$priceMatch) {
                    $match = false;
                }
            }

            return $match;
        });

        // Tri
        $sort = $filters['sort'] ?? 'popular';
        $filtered = match ($sort) {
            'price_asc'  => $filtered->sortBy(fn($p) => (float) ($p['price']['min'] ?? 0))->values(),
            'price_desc' => $filtered->sortByDesc(fn($p) => (float) ($p['price']['min'] ?? 0))->values(),
            'newest'     => $filtered->sortByDesc(fn($p) => $p['modifiedDate'] ?? '')->values(),
            // Tri "Populaire" : marques Top Afrique d'abord, puis Europe
            // (FR > BE > EU > autres), puis USA, puis le reste.
            default      => $filtered->sortBy(function ($p) {
                $popular = !empty($p['cardType']['popular_in_africa']) ? 0 : 1;
                $region  = ($p['cardType']['region'] ?? 'other') === 'europe' ? 0
                        : (($p['cardType']['region'] ?? 'other') === 'usa' ? 1
                        : (($p['cardType']['region'] ?? 'other') === 'global' ? 2 : 3));
                $cc = strtoupper($p['cardType']['countryCode'] ?? '');
                $cPrio = match ($cc) {
                    'FR' => 0, 'BE' => 1, 'EU' => 2, 'CH' => 3, 'LU' => 3,
                    'GB' => 4, 'IE' => 4, 'DE' => 5, 'IT' => 5, 'ES' => 5, 'PT' => 5, 'NL' => 5,
                    'US' => 6, 'CA' => 6,
                    'GLC' => 7, 'WW' => 7, 'GLOBAL' => 7, 'GL' => 7,
                    default => 99,
                };
                return [$popular, $region, $cPrio];
            })->values(),
        };

        $total = $filtered->count();
        $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values()->all();

        return [
            'items' => $items,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Récupérer tous les produits individuels (extraits des types de cartes)
     */
    public function getAllProducts($pageIndex = 0, $pageSize = 50)
    {
        $allProducts = $this->fetchAndProcessAllProducts();
        return array_slice($allProducts, $pageIndex * $pageSize, $pageSize);
    }

    /**
     * Récupérer les types de cartes (pour page d'accueil).
     * Tri appliqué :
     *   1. Top Afrique (Steam, PSN, Xbox, Netflix, Spotify, Apple, Roblox, Riot, Epic) en premier
     *   2. Cartes Europe ensuite (mises en avant)
     *   3. Reste du catalogue
     */
    public function getCardTypes($limit = 12)
    {
        $cacheKey = "card_types_v4_slim_{$limit}";

        // Read manuel : pas Cache::remember car on a aussi le cas où le catalogue
        // n'est pas encore prêt (warm cache pas lancé) → on retourne [] sans
        // empoisonner le cache.
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && count($cached) > 0) return $cached;
        }

        try {
            // Lecture sans Collection : groupBy native par référence
            $allProducts = $this->getAllProducts(0, 99999);
            if (empty($allProducts)) return [];

            // Index des cardTypes par nom de marque (sans copier la lourde liste)
            // On garde juste le premier produit + ses siblings comme un mini-pointeur.
            $byName = [];
            foreach ($allProducts as $idx => $p) {
                $name = $p['cardType']['name'] ?? null;
                if ($name === null) continue;
                if (!isset($byName[$name])) {
                    $byName[$name] = ['first' => $idx, 'siblings' => []];
                }
                $byName[$name]['siblings'][] = $idx;
            }
            unset($p);

            // Construit les cardTypes avec leur liste de produits attachée
            $countryPriority = [
                'FR' => 0, 'BE' => 1, 'EU' => 2, 'CH' => 3, 'LU' => 3,
                'GB' => 4, 'IE' => 4, 'DE' => 5, 'IT' => 5, 'ES' => 5, 'PT' => 5, 'NL' => 5,
                'US' => 6, 'CA' => 6,
                'GLC' => 7, 'WW' => 7, 'GLOBAL' => 7, 'GL' => 7,
            ];

            $cardTypes = [];
            foreach ($byName as $name => $entry) {
                $first = $allProducts[$entry['first']];
                $ct = $first['cardType'];
                $ct['products'] = [];
                foreach ($entry['siblings'] as $i) {
                    $ct['products'][] = $allProducts[$i];
                }
                $cardTypes[] = $ct;
            }
            unset($byName, $allProducts);

            // Tri prioritaire en place
            usort($cardTypes, function ($a, $b) use ($countryPriority) {
                $aPop = !empty($a['popular_in_africa']) ? 0 : 1;
                $bPop = !empty($b['popular_in_africa']) ? 0 : 1;
                if ($aPop !== $bPop) return $aPop <=> $bPop;

                $regionRank = fn($r) => $r === 'europe' ? 0 : ($r === 'usa' ? 1 : ($r === 'global' ? 2 : 3));
                $aReg = $regionRank($a['region'] ?? 'other');
                $bReg = $regionRank($b['region'] ?? 'other');
                if ($aReg !== $bReg) return $aReg <=> $bReg;

                $aCc = $countryPriority[strtoupper($a['countryCode'] ?? '')] ?? 99;
                $bCc = $countryPriority[strtoupper($b['countryCode'] ?? '')] ?? 99;
                if ($aCc !== $bCc) return $aCc <=> $bCc;

                return strcmp(strtolower($a['name'] ?? ''), strtolower($b['name'] ?? ''));
            });

            $top = array_slice($cardTypes, 0, $limit);
            unset($cardTypes);

            Cache::put($cacheKey, $top, $this->cacheDuration);
            return $top;
        } catch (\Exception $e) {
            Log::error('Erreur types de cartes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Résout la valeur native d'un produit (ex: 10 EUR pour Roblox FR 10€) à
     * partir de son ID afrikard. Utilisé à la création d'une commande pour
     * stocker `native_value`/`native_currency` sur l'OrderItem — afrikard
     * /orders/checkout attend la valeur NATIVE, pas le prix XAF que voit le user.
     *
     * Ordre de fallback (4 niveaux pour anciennes commandes / cache stale) :
     *  1. Catalogue cache (rapide, déjà chargé)
     *  2. API afrikard ciblée par productId (rapide mais peut rater si l'API
     *     ne supporte pas le filtre productId — afrikard renvoie parfois la
     *     page 0 sans filtrer)
     *  3. Fetch multi-pages forcé (`fetchAllCatalogPages` — coûteux mais
     *     bulletproof, scanne tout le catalogue afrikard)
     *  4. null (et le caller décide quoi faire)
     *
     * @param  bool  $deepScan  Si true, force le scan multi-pages quand l'item
     *                          n'est pas trouvé dans cache + API ciblée. Plus
     *                          lent (10-30s) mais bulletproof. À utiliser
     *                          seulement dans les retry handlers.
     * @return array{value: int, currency: string}|null
     */
    public function resolveNativeValue(int|string $productId, bool $deepScan = false): ?array
    {
        $allProducts = collect($this->getAllProducts(0, 99999))->keyBy('id');
        $product = $allProducts->get((int) $productId)
            ?? $allProducts->get((string) $productId)
            ?? $this->getProductByIdLight($productId);

        // Fallback ultime : warm-cache complet puis retry
        if (!$product && $deepScan) {
            Log::info("resolveNativeValue: deepScan triggered for productId={$productId}");
            $this->fetchAllCatalogPages(100, 50);
            $allProducts = collect($this->getAllProducts(0, 99999))->keyBy('id');
            $product = $allProducts->get((int) $productId)
                ?? $allProducts->get((string) $productId);
        }

        if (!$product) return null;

        $value    = (int) round($product['minFaceValue'] ?? $product['price']['min'] ?? 0);
        $currency = $product['price']['currencyCode'] ?? null;

        if ($value <= 0 || !$currency) return null;
        return ['value' => $value, 'currency' => $currency];
    }

    /**
     * Map léger [cardTypeId => productsCount] pour enrichir les listings
     * (ex: afficher "X montants disponibles" sur chaque carte du catalogue).
     * Cache séparé du listing complet — recalcul O(n) à partir du catalogue
     * déjà processed (lui-même caché). Coût négligeable.
     */
    public function getCardTypeProductCounts(): array
    {
        $cacheKey = 'card_type_counts_v1';
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) return $cached;
        }

        try {
            $all = $this->fetchAndProcessAllProducts();
            $counts = [];
            foreach ($all as $p) {
                $id = $p['cardType']['id'] ?? null;
                if ($id === null) continue;
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
            Cache::put($cacheKey, $counts, $this->cacheDuration);
            return $counts;
        } catch (\Exception $e) {
            Log::warning('getCardTypeProductCounts failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un type de carte par ID avec ses produits
     */
    public function getCardTypeById($cardTypeId)
    {
        // Daywatch (BDD locale)
        if (is_string($cardTypeId) && str_starts_with($cardTypeId, 'daywatch_')) {
            $localId = (int) substr($cardTypeId, 9);
            $dw = DaywatchProduct::find($localId);
            if (!$dw) return null;
            $item = $dw->toCatalogItem();
            $cardType = $item['cardType'];
            $cardType['products'] = [$item];
            return $cardType;
        }

        $cacheKey = "card_type_v3_rich_{$cardTypeId}";

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) return $cached;
            Cache::forget($cacheKey);
        }

        // Fetch direct sur afrikard avec ?brandId=X — récupère les données RICHES
        // (description, terms, redemptionInstructions) pour ce cardType précis.
        // Le cache catalogue principal est slim pour économiser la mémoire ; on
        // ré-enrichit ponctuellement pour les pages détail.
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/catalog", [
                'brandId'  => $cardTypeId,
                'pageSize' => 100,
            ]);
            if (!$response->successful()) {
                Log::warning("afrikard brandId={$cardTypeId} → HTTP {$response->status()}");
                return null;
            }
            $items = $response->json()['items'] ?? [];

            // Filtre les UAE et dédoublonne
            $items = array_values(array_filter($items, fn($p) => !$this->isBlockedProduct($p)));

            if (empty($items)) {
                // Fallback : peut-être un cardType sans produits via brandId, on tente
                // via le cache catalogue slim (pour récupérer au moins le nom/logo)
                $allProducts = $this->getAllProducts(0, 99999);
                $slim = array_values(array_filter($allProducts, fn($p) => isset($p['cardType']['id']) && $p['cardType']['id'] == $cardTypeId));
                if (empty($slim)) return null;
                $ct = $slim[0]['cardType'];
                $ct['products'] = $slim;
                Cache::put($cacheKey, $ct, $this->cacheDuration);
                return $ct;
            }

            // Premier item → on extrait le bloc `brand` complet (riche)
            $brand = $items[0]['brand'] ?? [];
            $cardType = [
                'id'                       => $brand['id'] ?? $cardTypeId,
                'internalId'               => $brand['id'] ?? $cardTypeId,
                'name'                     => $brand['name'] ?? null,
                'logoUrl'                  => $brand['logoUrl'] ?? null,
                'countryCode'              => $brand['countryCode'] ?? null,
                'currencyCode'             => $brand['currencyCode'] ?? null,
                'description'              => $brand['description'] ?? null,
                'terms'                    => $brand['terms'] ?? null,
                'redemptionInstructions'   => $brand['redemptionInstructions'] ?? null,
            ];
            // Enrichit avec les tags region/popular du cache slim si dispo
            $allProducts = $this->getAllProducts(0, 99999);
            foreach ($allProducts as $p) {
                if (isset($p['cardType']['id']) && $p['cardType']['id'] == $cardTypeId) {
                    $cardType['region']            = $p['cardType']['region']            ?? 'other';
                    $cardType['popular_in_africa'] = $p['cardType']['popular_in_africa'] ?? false;
                    $cardType['categories']        = $p['cardType']['categories']        ?? [];
                    break;
                }
            }

            // Liste de produits (slim, suffisant pour l'affichage des montants)
            $cardType['products'] = array_map(fn($p) => $this->processCatalogItem($p), $items);

            Cache::put($cacheKey, $cardType, $this->cacheDuration);
            return $cardType;
        } catch (\Throwable $e) {
            Log::error("getCardTypeById({$cardTypeId}) exception : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Rechercher dans les produits individuels.
     * Combine la recherche server-side (afrikard via param `name`) qui est la
     * source de vérité, ET un fallback sur le cache local (pour les marques
     * où le cache contient déjà la donnée enrichie). Dédoublonne par id.
     */
    public function searchIndividualProducts($query, $pageIndex = 0, $pageSize = 50)
    {
        $query = trim((string) $query);
        if ($query === '') return [];

        $cacheKey = "search_individual_v3_" . md5(strtolower($query)) . "_page_{$pageIndex}_size_{$pageSize}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($query, $pageIndex, $pageSize) {
            try {
                // 1. Recherche server-side : afrikard /catalog?name=X — fiable même si
                //    la marque est absente du cache local (cas Fnac, Darty, etc.).
                $apiResults = $this->searchProductsViaApi($query, 100);

                // 2. Fallback cache local pour les marques déjà chargées
                $searchTerm = strtolower($query);
                $localResults = collect($this->fetchAndProcessAllProducts())
                    ->filter(function ($product) use ($searchTerm) {
                        return str_contains(strtolower($product['name'] ?? ''), $searchTerm)
                            || (isset($product['cardType']) && (
                                str_contains(strtolower($product['cardType']['name'] ?? ''), $searchTerm) ||
                                str_contains(strtolower($product['cardType']['description'] ?? ''), $searchTerm)
                            ));
                    })
                    ->values()
                    ->all();

                // 3. Merge + dédoublonnage par id (priorité aux résultats API)
                $merged = [];
                $seen   = [];
                foreach (array_merge($apiResults, $localResults) as $p) {
                    $id = $p['id'] ?? null;
                    if ($id === null) continue;
                    if (isset($seen[$id])) continue;
                    $seen[$id] = true;
                    $merged[]  = $p;
                }

                return array_slice($merged, $pageIndex * $pageSize, $pageSize);
            } catch (\Exception $e) {
                Log::error('Erreur recherche produits individuels: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Vider le cache
     */
    public function clearCache()
    {
        Cache::flush();
    }
} 