<?php

namespace App\Support;

/**
 * CatalogRows — règle DÉTERMINISTE de regroupement du catalogue en lignes
 * thématiques (P1 §2). Classe pure : testée unitairement sans service.
 *
 * Modes (priorité du filtre le plus spécifique : marque > prix > région > catégorie) :
 *  - marque active   → lignes par RÉGION (France / Europe / USA / Afrique / Monde)
 *  - prix actif      → lignes par MARQUE (produits ayant ≥ 1 variante dans la gamme
 *                      — garanti en amont : le filtre prix s'applique avant)
 *  - région active   → lignes par CATÉGORIE de cette région
 *  - catégorie active→ lignes par MARQUE dans cette catégorie
 *  - aucun filtre    → lignes par CATÉGORIE
 *
 * NB : il n'existe pas (encore) de paramètre `marque=` dédié — les pills marques
 * utilisent `search`. Un search qui matche une marque connue est donc traité
 * comme « filtre marque » ; un search libre reste un listing plat.
 */
class CatalogRows
{
    public const MAX_PER_ROW = 12;

    /** Marques connues (pills + gros du catalogue) — détection « filtre marque ». */
    private const KNOWN_BRANDS = [
        'steam', 'psn', 'playstation', 'xbox', 'netflix', 'spotify', 'apple',
        'itunes', 'roblox', 'riot', 'epic', 'google play', 'google', 'nintendo',
        'amazon', 'disney', 'deezer', 'uber', 'airbnb', 'minecraft', 'fortnite',
    ];

    /** Tokens géographiques à retirer du nom pour agréger une MARQUE. */
    /**
     * Qualificatifs commerciaux retirés de la clé de marque.
     *
     * Sans eux, « Deezer Premium », « Deezer Family 6 Month » et
     * « Deezer Premium 3 Month » comptaient pour TROIS marques : le panneau de
     * filtres affichait l'étiquette « Deezer » mais filtrait sur la clé
     * « deezer premium », qui ne matchait rien. Mesuré avant correctif :
     * 1 369 clés de marque dont 1 101 à un seul produit.
     *
     * On ne retire que ce qui décrit un FORMAT d'abonnement ou d'emballage,
     * jamais ce qui distingue un produit (« Battlefield 2042 » reste entier).
     */
    private const VARIANT_TOKENS = [
        'premium', 'family', 'duo', 'student', 'individual', 'basic', 'standard',
        'plus', 'pro', 'deluxe', 'ultimate', 'essential', 'extra',
        'month', 'months', 'mois', 'year', 'years', 'an', 'ans',
        'subscription', 'abonnement', 'membership', 'plan',
        'gift', 'card', 'cards', 'giftcard', 'voucher', 'code', 'digital',
        'e-gift', 'egift', 'top', 'up', 'topup', 'recharge',
    ];

    private const GEO_TOKENS = [
        'france', 'belgium', 'belgique', 'luxembourg', 'germany', 'allemagne',
        'spain', 'espagne', 'italy', 'italie', 'portugal', 'netherlands',
        'austria', 'ireland', 'switzerland', 'uk', 'united kingdom',
        'united states', 'usa', 'us', 'eu', 'europe', 'global', 'mondial',
        'canada', 'fr', 'be', 'de', 'it', 'es', 'nl', 'pt', 'ca', 'gb', 'ww',
        // Reste du catalogue international afrikard
        'poland', 'hungary', 'czech', 'republic', 'slovakia', 'slovenia',
        'romania', 'bulgaria', 'greece', 'croatia', 'sweden', 'norway',
        // Manquants constatés à l'audit : fragmentaient « Riot Access KSA »,
        // « Netflix KSA », « Epic Games KSA » en marques distinctes.
        'ksa', 'uae', 'latam', 'mena', 'apac', 'nordic', 'benelux', 'dach',
        'denmark', 'finland', 'turkey', 'mexico', 'brazil', 'brasil',
        'argentina', 'chile', 'colombia', 'peru', 'australia', 'new', 'zealand',
        'japan', 'india', 'singapore', 'malaysia', 'thailand', 'philippines',
        'indonesia', 'vietnam', 'korea', 'hong', 'kong', 'taiwan', 'china',
        'saudi', 'arabia', 'qatar', 'kuwait', 'bahrain', 'oman', 'egypt',
        'morocco', 'tunisia', 'nigeria', 'kenya', 'ghana', 'south', 'africa',
        'gabon',
    ];

    /** Un terme de recherche correspond-il à une marque connue ? */
    public static function isBrandSearch(?string $search): bool
    {
        $s = strtolower(trim((string) $search));
        return $s !== '' && in_array($s, self::KNOWN_BRANDS, true);
    }

    /**
     * Mode de regroupement d'après les filtres actifs.
     * @return 'region'|'brand'|'category'
     */
    public static function mode(array $filters): string
    {
        // Filtre marque : param dédié marque[] (Phase C) — le search matchant
        // une marque connue reste traité pareil (raccourci barre de recherche).
        if (!empty($filters['brand']) || self::isBrandSearch($filters['search'] ?? null)) {
            return 'region';                                   // marque → régions
        }
        if (!empty($filters['price_range'])
            || isset($filters['price_min']) || isset($filters['price_max'])) {
            return 'brand';                                    // prix → marques
        }
        if (!empty($filters['region']) || !empty($filters['country_code'])) {
            return 'category';                                 // région → catégories
        }
        if (!empty($filters['category'])) {
            return 'brand';                                    // catégorie → marques
        }
        return 'category';                                     // défaut
    }

    /**
     * Construit les lignes à partir des produits GROUPÉS (sortie du dedupe,
     * déjà filtrés + triés « populaire » en amont).
     *
     * @param  array $items       produits groupés par cardType
     * @param  string $mode       'category'|'brand'|'region'
     * @param  array $categories  [['id','name'],…] — ordre officiel des catégories
     * @return array<int,array{key:string,title:string,items:array,see_all:array}>
     *         Lignes vides omises ; items plafonnés à MAX_PER_ROW ; see_all =
     *         query params à fusionner aux filtres courants (+ view=all).
     */
    public static function build(array $items, string $mode, array $categories = []): array
    {
        return match ($mode) {
            'region' => self::buildByRegion($items),
            'brand'  => self::buildByBrand($items),
            default  => self::buildByCategory($items, $categories),
        };
    }

    /** Marque « propre » d'un cardType (sans tokens géo) : PSN France → PSN. */
    public static function brandKey(string $cardTypeName): string
    {
        $words = preg_split('/\s+/', trim($cardTypeName)) ?: [];

        $kept = [];
        foreach ($words as $i => $mot) {
            $nu = strtolower(trim($mot, '()·-'));

            if (in_array($nu, self::GEO_TOKENS, true)) {
                continue;
            }

            // Les qualificatifs ne sont retirés qu'APRÈS le premier mot : la
            // marque elle-même peut s'appeler « Premium » ou « Gift », et une
            // clé vide ne filtrerait plus rien.
            if ($i > 0 && in_array($nu, self::VARIANT_TOKENS, true)) {
                continue;
            }

            // Un nombre n'est retiré que s'il QUANTIFIE une durée — « 3 Month »,
            // « 6 Mois ». Seul, il fait partie du nom : retirer « 2042 » de
            // « Battlefield 2042 » fusionnait des jeux distincts.
            $suivant = strtolower(trim($words[$i + 1] ?? '', '()·-'));
            if ($i > 0
                && preg_match('/^\d+$/', $nu) === 1
                && in_array($suivant, ['month', 'months', 'mois', 'year', 'years', 'an', 'ans'], true)) {
                continue;
            }

            $kept[] = $mot;
        }

        $key = trim(implode(' ', $kept));
        $key = $key !== '' ? $key : trim($cardTypeName);

        // Crypto : retirer le ticker/devise entre parenthèses pour agréger
        // (« Binance (BTC) » et « Binance (USDT) » = une seule marque Binance).
        // Restreint à ces marques : la parenthèse porte un vrai sens ailleurs
        // (« Battlefield 2042 (XBOX) » ≠ « Battlefield 2042 »).
        $lower = mb_strtolower($key);
        foreach (CryptoCards::AGGREGATED_BRANDS as $brand) {
            if (str_starts_with($lower, $brand)) {
                $stripped = trim(preg_replace('/\s*\([A-Za-z0-9\-]{2,10}\)\s*/', ' ', $key));
                return $stripped !== '' ? $stripped : $key;
            }
        }
        return $key;
    }

    // ------------------------------------------------------------------

    private static function buildByCategory(array $items, array $categories): array
    {
        $buckets = [];
        foreach ($items as $item) {
            $catId = $item['cardType']['categories'][0]['id'] ?? null;
            if ($catId === null) continue;
            $buckets[$catId][] = $item;
        }

        $rows = [];
        // Ordre officiel des catégories ; celles absentes du catalogue = omises.
        $ordered = !empty($categories) ? $categories
            : array_map(fn ($id) => ['id' => $id, 'name' => "Catégorie {$id}"], array_keys($buckets));

        foreach ($ordered as $cat) {
            $id = $cat['id'] ?? null;
            if ($id === null || empty($buckets[$id])) continue;
            $rows[] = [
                'key'     => "cat-{$id}",
                'title'   => $cat['name'] ?? "Catégorie {$id}",
                'items'   => array_slice($buckets[$id], 0, self::MAX_PER_ROW),
                'see_all' => ['category' => $id, 'view' => 'all'],
            ];
        }
        return $rows;
    }

    private static function buildByBrand(array $items): array
    {
        $buckets = [];   // clé insensible à la casse (PlayStation ≡ Playstation)
        $titles  = [];   // titre = première forme rencontrée
        $order   = [];
        foreach ($items as $item) {
            $brand = self::brandKey($item['cardType']['name'] ?? ($item['name'] ?? ''));
            if ($brand === '') continue;
            $key = mb_strtolower($brand);
            if (!isset($buckets[$key])) {
                $order[]      = $key;
                $titles[$key] = $brand;
            }
            $buckets[$key][] = $item;
        }

        $rows = [];
        foreach ($order as $key) {
            $rows[] = [
                'key'     => 'brand-' . \Illuminate\Support\Str::slug($key),
                'title'   => $titles[$key],
                'items'   => array_slice($buckets[$key], 0, self::MAX_PER_ROW),
                // Phase C : filtre marque dédié (plus fiable que search)
                'see_all' => ['marque' => [$titles[$key]], 'view' => 'all'],
            ];
        }
        return $rows;
    }

    private static function buildByRegion(array $items): array
    {
        // Buckets fixes, ordre marché : France → Europe → USA → Afrique → Monde.
        $defs = [
            'fr'     => ['title' => 'France',          'see_all' => ['loc' => ['FR'], 'view' => 'all']],
            'europe' => ['title' => 'Europe',          'see_all' => ['region' => ['europe'], 'view' => 'all']],
            'usa'    => ['title' => 'États-Unis',      'see_all' => ['region' => ['usa'], 'view' => 'all']],
            'africa' => ['title' => 'Gabon & Afrique', 'see_all' => ['region' => ['africa'], 'view' => 'all']],
            'global' => ['title' => 'International',   'see_all' => ['region' => ['global'], 'view' => 'all']],
        ];

        $buckets = [];
        foreach ($items as $item) {
            $cc     = strtoupper($item['cardType']['countryCode'] ?? '');
            $region = $item['cardType']['region'] ?? 'other';
            $key = $cc === 'FR' ? 'fr'
                : (in_array($region, ['europe', 'usa', 'africa', 'global'], true) ? $region : 'global');
            $buckets[$key][] = $item;
        }

        $rows = [];
        foreach ($defs as $key => $def) {
            if (empty($buckets[$key])) continue;
            $rows[] = [
                'key'     => "region-{$key}",
                'title'   => $def['title'],
                'items'   => array_slice($buckets[$key], 0, self::MAX_PER_ROW),
                'see_all' => $def['see_all'],
            ];
        }
        return $rows;
    }
}
