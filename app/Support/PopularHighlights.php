<?php

namespace App\Support;

use App\Services\ProductApiService;
use Illuminate\Support\Facades\Cache;

/**
 * PopularHighlights
 * =================
 * Bandeau « carte populaire » affiché en tête d'accueil et de boutique.
 *
 * Chaque rafraîchissement en tire une AUTRE au hasard : la structure visuelle
 * ne bouge pas, mais le titre, la description, la couleur, le logo et le bouton
 * appartiennent à la carte tirée. La carte précédente est mémorisée en session
 * pour ne jamais retomber deux fois de suite sur la même.
 *
 * Les IDs afrikard ne sont PAS écrits en dur : chaque entrée est résolue par
 * mots-clés sur le nom du cardType (variante EU/FR privilégiée, comme
 * getFeaturedCardTypes), et le prix d'appel vient du catalogue réel. Une entrée
 * introuvable est simplement ignorée — jamais de lien mort ni de prix inventé.
 */
class PopularHighlights
{
    private const CACHE_KEY  = 'popular_highlights_resolved_v1';
    private const CACHE_TTL  = 21600;              // 6 h
    private const SESSION_KEY = 'ka_last_highlight';

    /** Priorité pays pour choisir LA variante EU/FR d'une marque. */
    private const CC_PRIORITY = [
        'FR' => 0, 'BE' => 1, 'EU' => 2, 'LU' => 3,
        'GB' => 4, 'IE' => 4, 'DE' => 5, 'IT' => 5, 'ES' => 5, 'PT' => 5, 'NL' => 5,
    ];

    /**
     * Cartes en rotation. `match` = mots-clés cherchés dans le nom du cardType ;
     * `prefer` = variante privilégiée quand la marque en a plusieurs (Binance).
     * `title` = [avant, marque colorée, après]. `logo` = public/logos/brands/<x>.svg.
     *
     * `video_tone` = tonalité du fond vidéo (public/assets/videos/highlights/
     * <key>.mp4), qui pilote le voile de lisibilité : 'dark' par défaut,
     * 'light' pour les univers en studio clair. Mesuré à la livraison des clips
     * (luma moyenne /255) : apple 225 et googleplay 242 sont clairs, les huit
     * autres tiennent entre 10 et 45. À revoir si un clip est régénéré.
     */
    private const CARDS = [
        [
            'key'     => 'chatgpt',
            'match'   => ['rewarble chatgpt', 'chatgpt'],
            'badge'   => 'Nouveau · IA',
            'brand'   => 'ChatGPT Plus',
            'logo'    => 'openai',
            'color'   => '#10A37F',
            'title'   => ['Payez', 'ChatGPT Plus', 'en Mobile Money.'],
            'tagline' => 'Rechargez votre abonnement ChatGPT (OpenAI) sans carte bancaire — code reçu instantanément.',
            'cta'     => 'Obtenir la carte',
        ],
        [
            'key'     => 'netflix',
            'match'   => ['netflix'],
            'badge'   => 'Populaire · Streaming',
            'brand'   => 'Netflix',
            'logo'    => 'netflix',
            'color'   => '#E50914',
            'title'   => ['Offrez', 'Netflix', 'sans carte bancaire.'],
            'tagline' => 'Rechargez un compte Netflix en quelques secondes. Le code part dès la confirmation du paiement Airtel ou Moov Money.',
            'cta'     => 'Voir les montants',
        ],
        [
            'key'     => 'steam',
            'match'   => ['steam'],
            'badge'   => 'Top gaming',
            'brand'   => 'Steam',
            'logo'    => 'steam',
            'color'   => '#171A21',
            'title'   => ['Rechargez votre', 'portefeuille Steam', 'en Mobile Money.'],
            'tagline' => 'Des milliers de jeux PC accessibles sans carte bancaire : le code Steam arrive dans votre espace client aussitôt payé.',
            'cta'     => 'Choisir un montant',
        ],
        [
            'key'     => 'psn',
            'match'   => ['playstation', 'psn'],
            'badge'   => 'Populaire · Gaming',
            'brand'   => 'PlayStation',
            'logo'    => 'playstation',
            'color'   => '#003791',
            'title'   => ['Créditez votre', 'compte PlayStation', 'en quelques secondes.'],
            'tagline' => 'Jeux, extensions et PS Plus : payez en Airtel ou Moov Money et recevez votre code PSN immédiatement.',
            'cta'     => 'Voir les cartes PSN',
        ],
        [
            'key'     => 'xbox',
            'match'   => ['xbox'],
            'badge'   => 'Populaire · Gaming',
            'brand'   => 'Xbox',
            'logo'    => 'xbox',
            'color'   => '#107C10',
            'title'   => ['Rechargez', 'Xbox', 'sans carte bancaire.'],
            'tagline' => 'Game Pass, jeux et contenus Microsoft Store : votre code Xbox est livré dès la confirmation du paiement.',
            'cta'     => 'Obtenir un code Xbox',
        ],
        [
            'key'     => 'deezer',
            'match'   => ['deezer'],
            'badge'   => 'Musique',
            'brand'   => 'Deezer Premium',
            'logo'    => 'deezer',
            'color'   => '#A238FF',
            'title'   => ['Écoutez', 'Deezer Premium', 'toute l\'année.'],
            'tagline' => 'Payez votre abonnement Deezer en Mobile Money, sans engagement bancaire ni renouvellement automatique.',
            'cta'     => 'Voir les abonnements',
        ],
        [
            'key'        => 'apple',
            'match'      => ['apple', 'itunes', 'app store'],
            'badge'      => 'App Store & iTunes',
            'brand'      => 'Apple',
            'logo'       => 'apple',
            'color'      => '#000000',
            'video_tone' => 'light',   // studio blanc
            'title'   => ['Créditez votre', 'compte Apple', 'depuis Libreville.'],
            'tagline' => 'Applications, musique, iCloud, abonnements : rechargez votre identifiant Apple en Mobile Money.',
            'cta'     => 'Choisir un montant',
        ],
        [
            'key'        => 'googleplay',
            'match'      => ['google play', 'play store', 'playstore'],
            'badge'      => 'Android',
            'brand'      => 'Google Play',
            'logo'       => 'googleplay',
            'color'      => '#01875F',
            'video_tone' => 'light',   // fond blanc cassé + confettis colorés
            'title'   => ['Rechargez', 'Google Play', 'en Mobile Money.'],
            'tagline' => 'Applications, jeux et abonnements Android — le code est livré instantanément, sans carte bancaire.',
            'cta'     => 'Voir les montants',
        ],
        [
            'key'     => 'roblox',
            'match'   => ['roblox'],
            'badge'   => 'Populaire · Gaming',
            'brand'   => 'Roblox',
            'logo'    => 'roblox',
            'color'   => '#00A2FF',
            'title'   => ['Offrez des', 'Robux', 'en un clic.'],
            'tagline' => 'La carte préférée des joueurs Roblox : code reçu dès le paiement Airtel ou Moov Money.',
            'cta'     => 'Obtenir la carte',
        ],
        [
            'key'     => 'binance',
            'match'   => ['binance'],
            'prefer'  => '(usdt)',   // le stablecoin le plus utilisé, pas EURI-USDT
            'badge'   => 'Nouveau · Crypto',
            'brand'   => 'Binance',
            'logo'    => 'binance',
            'color'   => '#F0B90B',
            'title'   => ['Alimentez votre', 'compte Binance', 'en Mobile Money.'],
            'tagline' => 'Achetez vos cryptos depuis le Gabon sans carte bancaire : le code de rechargement Binance arrive instantanément.',
            'cta'     => 'Voir les cartes Binance',
        ],
    ];

    /**
     * Une carte au hasard, différente de celle du dernier affichage.
     * null si le catalogue n'a rien pu résoudre (cache froid, afrikard down).
     */
    public static function pick(): ?array
    {
        $pool = self::resolved();
        if (empty($pool)) return null;

        $last = null;
        try {
            $last = session(self::SESSION_KEY);
        } catch (\Throwable) {
            // Pas de session (console, tests unitaires) → tirage simple.
        }

        // Éviter la répétition immédiate — sauf s'il ne reste qu'une carte.
        $choices = count($pool) > 1
            ? array_values(array_filter($pool, fn ($c) => $c['key'] !== $last))
            : $pool;

        $picked = $choices[random_int(0, count($choices) - 1)];

        try {
            session()->put(self::SESSION_KEY, $picked['key']);
        } catch (\Throwable) {
            // idem
        }

        return $picked;
    }

    /**
     * Les cartes de CARDS effectivement présentes au catalogue, enrichies de
     * leur id afrikard et de leur prix d'appel FCFA réel. Cache 6 h.
     *
     * @return array<int, array>
     */
    public static function resolved(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && $cached !== []) return $cached;

        try {
            // Tout le catalogue (~2400 cardTypes) : les marques hors des
            // premières pages (Binance, Deezer…) doivent rester atteignables.
            $cardTypes = app(ProductApiService::class)->getCardTypes(5000);
        } catch (\Throwable) {
            return [];
        }
        if (empty($cardTypes)) return [];

        $out = [];
        foreach (self::CARDS as $card) {
            $best = self::bestVariant($cardTypes, $card);
            if (!$best) continue;

            $price = self::minFcfa($best);
            if ($price <= 0) continue;

            $out[] = $card + [
                'card_type_id' => $best['id'],
                'card_name'    => $best['name'] ?? $card['brand'],
                'country_code' => $best['countryCode'] ?? null,
                'price_fcfa'   => $price,
            ];
        }

        if ($out !== []) {
            Cache::put(self::CACHE_KEY, $out, self::CACHE_TTL);
        }
        return $out;
    }

    /** Meilleure variante d'une marque : `prefer` d'abord, puis EU/FR. */
    private static function bestVariant(array $cardTypes, array $card): ?array
    {
        $candidates = array_values(array_filter($cardTypes, function ($ct) use ($card) {
            $name = mb_strtolower($ct['name'] ?? '');
            foreach ($card['match'] as $kw) {
                if ($kw !== '' && str_contains($name, $kw)) return true;
            }
            return false;
        }));
        if ($candidates === []) return null;

        // Variante explicitement souhaitée (ex. Binance USDT parmi 13 actifs).
        if (!empty($card['prefer'])) {
            $preferred = array_values(array_filter(
                $candidates,
                fn ($ct) => str_contains(mb_strtolower($ct['name'] ?? ''), $card['prefer'])
            ));
            if ($preferred !== []) $candidates = $preferred;
        }

        // EU/FR d'abord si la marque en propose ; sinon tout (Roblox, Binance…).
        $euOnly = array_values(array_filter($candidates, fn ($ct) => ($ct['region'] ?? '') === 'europe'));
        $pool = $euOnly !== [] ? $euOnly : $candidates;

        usort($pool, function ($a, $b) {
            $ra = self::CC_PRIORITY[strtoupper($a['countryCode'] ?? '')] ?? 99;
            $rb = self::CC_PRIORITY[strtoupper($b['countryCode'] ?? '')] ?? 99;
            if ($ra !== $rb) return $ra <=> $rb;
            return count($b['products'] ?? []) <=> count($a['products'] ?? []);
        });

        return $pool[0] ?? null;
    }

    /** Prix d'appel = plus petit montant du cardType, converti au tarif de vente. */
    private static function minFcfa(array $cardType): int
    {
        $min = 0;
        foreach ($cardType['products'] ?? [] as $p) {
            $native = (float) ($p['price']['min'] ?? 0);
            if ($native <= 0) continue;
            $fcfa = Money::toFcfa($native, $p['price']['currencyCode'] ?? 'XAF');
            if ($fcfa > 0 && ($min === 0 || $fcfa < $min)) $min = $fcfa;
        }
        return $min;
    }
}
