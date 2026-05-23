<?php

namespace App\Support;

/**
 * Registre des styles visuels par marque pour les gift cards (= design
 * hybride proposé : couleur officielle de la marque + logo SVG officiel
 * + frame KardAfrica constant + chip doré).
 *
 * Chaque entrée définit :
 *  - background : gradient officiel de la marque (CSS)
 *  - text       : couleur du texte (contraste WCAG AA)
 *  - logo       : SVG inline ou null (fallback : initiale)
 *  - glow       : décor d'ambiance optionnel (radial-gradient CSS)
 *
 * Pour les marques non listées, fallback sur la brandColor hash (palette
 * Money::EXCHANGE_PALETTE) + initiale.
 *
 * Réf : proposition design `kardafrica-hybrid-cards.html` (mai 2026).
 */
class BrandStyle
{
    /**
     * Détecte la clé de marque depuis un nom (case-insensitive, substring).
     */
    public static function detect(?string $name): ?string
    {
        if (!$name) return null;
        $n = strtolower($name);

        // Ordre : marques plus spécifiques d'abord (eg "google play" avant "google")
        if (str_contains($n, 'apple') || str_contains($n, 'itunes') || str_contains($n, 'app store'))  return 'apple';
        if (str_contains($n, 'playstation') || str_contains($n, 'psn'))                                 return 'psn';
        if (str_contains($n, 'netflix'))                                                               return 'netflix';
        if (str_contains($n, 'roblox'))                                                                return 'roblox';
        if (str_contains($n, 'google'))                                                                return 'google';
        if (str_contains($n, 'xbox'))                                                                  return 'xbox';
        if (str_contains($n, 'spotify'))                                                               return 'spotify';
        if (str_contains($n, 'steam'))                                                                 return 'steam';
        if (str_contains($n, 'amazon'))                                                                return 'amazon';

        return null;
    }

    /**
     * Retourne la config visuelle d'une marque connue (clé issue de detect()).
     * Inclut background (gradient CSS), text color, logo SVG inline.
     *
     * @return array{background: string, text: string, logo: string, glow: ?string, chip_gold: bool}|null
     */
    public static function style(string $key): ?array
    {
        $registry = self::registry();
        return $registry[$key] ?? null;
    }

    /**
     * Convenience : detect + style en un appel.
     */
    public static function for(?string $name): ?array
    {
        $key = self::detect($name);
        return $key ? self::style($key) : null;
    }

    /**
     * Construit un style fallback pour les marques non listées : gradient
     * diagonal à partir de la brandColor + assombrissement, texte blanc.
     */
    public static function fallback(string $brandColor): array
    {
        $shaded = self::shade($brandColor);
        return [
            'background' => "linear-gradient(135deg, {$brandColor} 0%, {$shaded} 100%)",
            'text'       => '#ffffff',
            'glow'       => 'radial-gradient(circle at 80% 20%, rgba(255,255,255,0.15), transparent 50%)',
            'chip_gold'  => true,
            'logo'       => null,
        ];
    }

    /**
     * Assombrit une hex color (multiplication des canaux RGB par 0.65).
     */
    public static function shade(string $hex, float $factor = 0.65): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return '#' . $hex;
        $r = max(0, (int) round(hexdec(substr($hex, 0, 2)) * $factor));
        $g = max(0, (int) round(hexdec(substr($hex, 2, 2)) * $factor));
        $b = max(0, (int) round(hexdec(substr($hex, 4, 2)) * $factor));
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /**
     * Mapping countryCode → drapeau emoji + label région.
     */
    public static function region(?string $countryCode): array
    {
        $cc = strtoupper((string) $countryCode);
        $map = [
            'FR' => ['🇫🇷', 'France'],
            'BE' => ['🇧🇪', 'Belgium'],
            'DE' => ['🇩🇪', 'Deutschland'],
            'IT' => ['🇮🇹', 'Italia'],
            'ES' => ['🇪🇸', 'España'],
            'PT' => ['🇵🇹', 'Portugal'],
            'NL' => ['🇳🇱', 'Nederland'],
            'GB' => ['🇬🇧', 'UK'],
            'IE' => ['🇮🇪', 'Ireland'],
            'CH' => ['🇨🇭', 'Schweiz'],
            'LU' => ['🇱🇺', 'Luxembourg'],
            'PL' => ['🇵🇱', 'Polska'],
            'EU' => ['🇪🇺', 'EU'],
            'US' => ['🇺🇸', 'USA'],
            'CA' => ['🇨🇦', 'Canada'],
            'BR' => ['🇧🇷', 'Brasil'],
            'AR' => ['🇦🇷', 'Argentina'],
            'AU' => ['🇦🇺', 'Australia'],
            'NG' => ['🇳🇬', 'Nigeria'],
            'ZA' => ['🇿🇦', 'South Africa'],
            'KE' => ['🇰🇪', 'Kenya'],
            'GH' => ['🇬🇭', 'Ghana'],
            'SN' => ['🇸🇳', 'Sénégal'],
            'CI' => ['🇨🇮', 'Côte d\'Ivoire'],
            'CM' => ['🇨🇲', 'Cameroun'],
            'GA' => ['🇬🇦', 'Gabon'],
            'GLOBAL' => ['🌍', 'Global'],
            'GLC' => ['🌍', 'Global'],
            'WW' => ['🌍', 'Worldwide'],
        ];
        return $map[$cc] ?? ['', ''];
    }

    /**
     * Registre central des 9 marques avec leurs styles complets.
     * Background = gradient CSS prêt à mettre dans `style="background: ..."`.
     */
    private static function registry(): array
    {
        return [
            'apple' => [
                'background' => 'linear-gradient(135deg, #f5f5f7 0%, #d2d2d7 60%, #b8b8be 100%)',
                'text'       => '#1d1d1f',
                'glow'       => 'radial-gradient(circle at 80% 30%, rgba(255,255,255,0.6), transparent 50%)',
                'chip_gold'  => true,
                'logo'       => '<svg viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg"><path fill="#1d1d1f" d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/></svg>',
            ],
            'psn' => [
                'background' => 'linear-gradient(135deg, #003791 0%, #001f5c 100%)',
                'text'       => '#ffffff',
                'glow'       => 'radial-gradient(circle at 0% 100%, rgba(0,158,247,0.30), transparent 60%)',
                'chip_gold'  => true,
                'logo'       => '<svg viewBox="0 0 200 80" xmlns="http://www.w3.org/2000/svg"><text x="0" y="60" font-family="Arial Black, sans-serif" font-weight="900" font-size="64" fill="#ffffff" letter-spacing="-4">PS</text><text x="80" y="60" font-family="Arial, sans-serif" font-weight="300" font-size="32" fill="#ffffff">N</text></svg>',
            ],
            'netflix' => [
                'background' => 'linear-gradient(180deg, #0a0a0a 0%, #1a0606 100%)',
                'text'       => '#ffffff',
                'glow'       => 'radial-gradient(ellipse at 100% 0%, rgba(229,9,20,0.20), transparent 60%)',
                'chip_gold'  => true,
                'logo'       => '<svg viewBox="0 0 200 60" xmlns="http://www.w3.org/2000/svg"><text x="0" y="50" font-family="Arial Black, sans-serif" font-weight="900" font-size="56" fill="#E50914" letter-spacing="-2">NETFLIX</text></svg>',
            ],
            'roblox' => [
                'background' => 'linear-gradient(180deg, #232527 0%, #111213 60%, #2a0f10 100%)',
                'text'       => '#ffffff',
                'glow'       => 'linear-gradient(135deg, transparent 60%, rgba(226,72,73,0.18))',
                'chip_gold'  => true,
                // Logo Roblox stylisé (carré incliné avec carré blanc au centre)
                'logo'       => '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect x="15" y="15" width="70" height="70" rx="6" fill="#ffffff" transform="rotate(8 50 50)"/><rect x="38" y="38" width="24" height="24" rx="2" fill="#232527" transform="rotate(8 50 50)"/></svg>',
            ],
            'google' => [
                'background' => 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
                'text'       => '#202124',
                'glow'       => 'conic-gradient(from 0deg at 80% 30%, rgba(234,67,53,0.12), rgba(251,188,4,0.12), rgba(52,168,83,0.12), rgba(66,133,244,0.12), rgba(234,67,53,0.12))',
                'chip_gold'  => true,
                'logo'       => '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><polygon points="20,15 75,50 20,85" fill="#34A853"/><polygon points="20,15 75,50 50,40" fill="#FBBC04"/><polygon points="20,85 75,50 50,60" fill="#EA4335"/><polygon points="20,15 50,40 50,60 20,85" fill="#4285F4"/></svg>',
            ],
            'xbox' => [
                'background' => 'linear-gradient(135deg, #107C10 0%, #0a4f0a 100%)',
                'text'       => '#ffffff',
                'glow'       => 'radial-gradient(circle at 80% 80%, rgba(255,255,255,0.10), transparent 50%)',
                'chip_gold'  => true,
                'logo'       => '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="42" fill="#ffffff"/><path d="M30 25 Q45 40 50 50 Q55 40 70 25 Q60 18 50 18 Q40 18 30 25Z" fill="#107C10"/><path d="M30 75 Q45 60 50 50 Q55 60 70 75 Q60 82 50 82 Q40 82 30 75Z" fill="#107C10"/><path d="M22 35 Q35 50 22 65 Q12 55 12 50 Q12 45 22 35Z" fill="#107C10"/><path d="M78 35 Q65 50 78 65 Q88 55 88 50 Q88 45 78 35Z" fill="#107C10"/></svg>',
            ],
            'spotify' => [
                'background' => 'linear-gradient(180deg, #0a0a0a 0%, #04140a 100%)',
                'text'       => '#ffffff',
                'glow'       => 'radial-gradient(circle at 100% 100%, rgba(30,215,96,0.25), transparent 60%)',
                'chip_gold'  => true,
                'logo'       => '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="44" fill="#1ED760"/><path d="M28 38 Q50 32 72 42" stroke="#000" stroke-width="6" stroke-linecap="round" fill="none"/><path d="M30 52 Q50 46 70 56" stroke="#000" stroke-width="5" stroke-linecap="round" fill="none"/><path d="M32 64 Q50 60 66 68" stroke="#000" stroke-width="4" stroke-linecap="round" fill="none"/></svg>',
            ],
            'steam' => [
                'background' => 'linear-gradient(135deg, #1b2838 0%, #0a141f 100%)',
                'text'       => '#c7d5e0',
                'glow'       => 'radial-gradient(circle at 20% 30%, rgba(102,192,244,0.20), transparent 50%)',
                'chip_gold'  => true,
                'logo'       => '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="44" fill="none" stroke="#c7d5e0" stroke-width="3"/><circle cx="38" cy="40" r="14" fill="none" stroke="#c7d5e0" stroke-width="3"/><circle cx="38" cy="40" r="6" fill="#c7d5e0"/><circle cx="62" cy="58" r="10" fill="none" stroke="#c7d5e0" stroke-width="3"/><circle cx="62" cy="58" r="4" fill="#c7d5e0"/></svg>',
            ],
            'amazon' => [
                'background' => 'linear-gradient(180deg, #131a22 0%, #0a0f15 100%)',
                'text'       => '#ffffff',
                'glow'       => 'radial-gradient(ellipse at 50% 100%, rgba(255,153,0,0.20), transparent 60%)',
                'chip_gold'  => true,
                'logo'       => '<svg viewBox="0 0 200 60" xmlns="http://www.w3.org/2000/svg"><text x="0" y="40" font-family="Arial, sans-serif" font-weight="700" font-size="36" fill="#ffffff" letter-spacing="-1">amazon</text><path d="M4 48 Q90 70 180 48" stroke="#FF9900" stroke-width="4" fill="none" stroke-linecap="round"/></svg>',
            ],
        ];
    }
}
