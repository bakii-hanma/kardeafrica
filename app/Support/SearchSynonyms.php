<?php

namespace App\Support;

/**
 * SearchSynonyms — tolérance de la recherche boutique (P1 §7).
 *
 * Deux mécanismes, dans l'ordre :
 *  1. Table de synonymes/alias exacts (« psn » → playstation, « itunes » → apple).
 *  2. Correction typo par distance de Levenshtein (« playstasion » → playstation)
 *     contre la liste des marques candidates.
 *
 * Classe pure — testée unitairement.
 */
class SearchSynonyms
{
    /** Alias exacts (minuscule) → terme canonique de recherche. */
    private const MAP = [
        'psn'          => 'playstation',
        'ps4'          => 'playstation',
        'ps5'          => 'playstation',
        'play station' => 'playstation',
        'itunes'       => 'apple',
        'app store'    => 'apple',
        'appstore'     => 'apple',
        'gpay'         => 'google play',
        'googleplay'   => 'google play',
        'xbox live'    => 'xbox',
        'game pass'    => 'xbox',
        'nintendo eshop' => 'nintendo',
        'eshop'        => 'nintendo',
        'lol'          => 'riot',
        'league of legends' => 'riot',
        'valorant'     => 'riot',
        'fortnite'     => 'epic',
        'chat gpt'     => 'chatgpt',
        'openai'       => 'chatgpt',
    ];

    /** Applique les alias exacts. Renvoie le terme canonique (ou l'entrée nettoyée). */
    public static function normalize(string $query): string
    {
        $q = mb_strtolower(trim($query));
        return self::MAP[$q] ?? $q;
    }

    /**
     * Correction typo : renvoie le candidat le plus proche si la distance de
     * Levenshtein est ≤ 2 (ou ≤ 3 pour les mots ≥ 8 lettres). Null si rien
     * d'assez proche. $candidates = marques en minuscule.
     *
     * @param array<int,string> $candidates
     */
    public static function closest(string $query, array $candidates): ?string
    {
        $q = mb_strtolower(trim($query));
        if (mb_strlen($q) < 3) {
            return null;
        }
        $maxDist = mb_strlen($q) >= 8 ? 3 : 2;

        $best = null;
        $bestDist = PHP_INT_MAX;
        foreach ($candidates as $candidate) {
            $c = mb_strtolower($candidate);
            // Sous-chaîne = déjà un match direct, pas une « correction »
            if (str_contains($c, $q)) {
                return $candidate;
            }
            $dist = levenshtein($q, $c);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $candidate;
            }
        }

        return $bestDist <= $maxDist ? $best : null;
    }
}
