/**
 * Registre des styles visuels par marque pour les gift cards mobiles
 * (mirror de app/Support/BrandStyle.php). Design hybride :
 *  - couleur officielle de la marque (gradient)
 *  - logo officiel (SVG via react-native-svg)
 *  - frame KardAfrica constant
 *  - chip doré
 *  - région + drapeau
 *
 * Réf : proposition design `kardafrica-hybrid-cards.html` (mai 2026).
 */

export type BrandKey =
  | 'apple' | 'psn' | 'netflix' | 'roblox'
  | 'google' | 'xbox' | 'spotify' | 'steam' | 'amazon';

export interface BrandStyle {
  /** Liste de couleurs pour le gradient (du haut/gauche au bas/droite) */
  gradient: [string, string] | [string, string, string];
  /** Couleur du texte (= contraste sur le gradient) */
  text: string;
  /** Couleur de la glow d'ambiance (rgba) — null = pas de glow */
  glow?: { color: string; position: 'tl' | 'tr' | 'bl' | 'br' | 'center' };
  /** Type de logo : 'svg' (custom) ou null = initiale en fallback */
  logoType: BrandKey;
}

/**
 * Détecte la marque depuis un nom (case-insensitive, substring).
 */
export function detectBrand(name: string | null | undefined): BrandKey | null {
  if (!name) return null;
  const n = name.toLowerCase();
  // Ordre : plus spécifique d'abord
  if (n.includes('apple') || n.includes('itunes') || n.includes('app store')) return 'apple';
  if (n.includes('playstation') || n.includes('psn'))                          return 'psn';
  if (n.includes('netflix'))                                                  return 'netflix';
  if (n.includes('roblox'))                                                   return 'roblox';
  if (n.includes('google'))                                                   return 'google';
  if (n.includes('xbox'))                                                     return 'xbox';
  if (n.includes('spotify'))                                                  return 'spotify';
  if (n.includes('steam'))                                                    return 'steam';
  if (n.includes('amazon'))                                                   return 'amazon';
  return null;
}

/**
 * Registre central — gradient + couleurs + glow par marque.
 */
export const BRAND_STYLES: Record<BrandKey, BrandStyle> = {
  apple: {
    gradient: ['#f5f5f7', '#d2d2d7', '#b8b8be'],
    text: '#1d1d1f',
    glow: { color: 'rgba(255,255,255,0.5)', position: 'tr' },
    logoType: 'apple',
  },
  psn: {
    gradient: ['#003791', '#001f5c'],
    text: '#ffffff',
    glow: { color: 'rgba(0,158,247,0.30)', position: 'bl' },
    logoType: 'psn',
  },
  netflix: {
    gradient: ['#0a0a0a', '#1a0606'],
    text: '#ffffff',
    glow: { color: 'rgba(229,9,20,0.20)', position: 'tr' },
    logoType: 'netflix',
  },
  roblox: {
    gradient: ['#232527', '#111213', '#2a0f10'],
    text: '#ffffff',
    glow: { color: 'rgba(226,72,73,0.18)', position: 'br' },
    logoType: 'roblox',
  },
  google: {
    gradient: ['#ffffff', '#f8f9fa'],
    text: '#202124',
    glow: { color: 'rgba(234,67,53,0.15)', position: 'tr' },
    logoType: 'google',
  },
  xbox: {
    gradient: ['#107C10', '#0a4f0a'],
    text: '#ffffff',
    glow: { color: 'rgba(255,255,255,0.10)', position: 'br' },
    logoType: 'xbox',
  },
  spotify: {
    gradient: ['#0a0a0a', '#04140a'],
    text: '#ffffff',
    glow: { color: 'rgba(30,215,96,0.25)', position: 'br' },
    logoType: 'spotify',
  },
  steam: {
    gradient: ['#1b2838', '#0a141f'],
    text: '#c7d5e0',
    glow: { color: 'rgba(102,192,244,0.20)', position: 'tl' },
    logoType: 'steam',
  },
  amazon: {
    gradient: ['#131a22', '#0a0f15'],
    text: '#ffffff',
    glow: { color: 'rgba(255,153,0,0.20)', position: 'br' },
    logoType: 'amazon',
  },
};

/**
 * Fallback : assombrit une hex pour générer un gradient depuis brandColor.
 */
export function shade(hex: string, factor = 0.65): string {
  const clean = hex.replace('#', '');
  if (clean.length !== 6) return hex;
  const r = Math.max(0, Math.round(parseInt(clean.slice(0, 2), 16) * factor));
  const g = Math.max(0, Math.round(parseInt(clean.slice(2, 4), 16) * factor));
  const b = Math.max(0, Math.round(parseInt(clean.slice(4, 6), 16) * factor));
  return '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase();
}

/**
 * Pour les marques non listées, construit un style fallback à partir de la
 * couleur de marque hashée (cf utils/brandColor.ts).
 */
export function fallbackStyle(brandColor: string): BrandStyle {
  return {
    gradient: [brandColor, shade(brandColor)],
    text: '#ffffff',
    glow: { color: 'rgba(255,255,255,0.15)', position: 'tr' },
    logoType: 'apple', // dummy, le rendu utilisera l'initiale puisque detectBrand renvoie null
  };
}

/**
 * Mapping countryCode → [drapeau emoji, label région].
 */
export function regionInfo(countryCode: string | null | undefined): [string, string] {
  if (!countryCode) return ['', ''];
  const map: Record<string, [string, string]> = {
    FR: ['🇫🇷', 'France'],   BE: ['🇧🇪', 'Belgium'],   DE: ['🇩🇪', 'Deutschland'],
    IT: ['🇮🇹', 'Italia'],    ES: ['🇪🇸', 'España'],    PT: ['🇵🇹', 'Portugal'],
    NL: ['🇳🇱', 'Nederland'], GB: ['🇬🇧', 'UK'],         IE: ['🇮🇪', 'Ireland'],
    CH: ['🇨🇭', 'Schweiz'],   LU: ['🇱🇺', 'Luxembourg'], PL: ['🇵🇱', 'Polska'],
    EU: ['🇪🇺', 'EU'],         US: ['🇺🇸', 'USA'],        CA: ['🇨🇦', 'Canada'],
    BR: ['🇧🇷', 'Brasil'],    AR: ['🇦🇷', 'Argentina'], AU: ['🇦🇺', 'Australia'],
    NG: ['🇳🇬', 'Nigeria'],   ZA: ['🇿🇦', 'South Africa'],
    KE: ['🇰🇪', 'Kenya'],     GH: ['🇬🇭', 'Ghana'],
    SN: ['🇸🇳', 'Sénégal'],   CI: ['🇨🇮', 'Côte d\'Ivoire'],
    CM: ['🇨🇲', 'Cameroun'],  GA: ['🇬🇦', 'Gabon'],
    GLOBAL: ['🌍', 'Global'], GLC: ['🌍', 'Global'], WW: ['🌍', 'Worldwide'],
  };
  return map[countryCode.toUpperCase()] ?? ['', countryCode];
}
