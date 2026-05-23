/**
 * Génère la couleur de marque utilisée pour le visuel "carte cadeau".
 * Réplique EXACTE du closure $brandColorFor du web :
 *  - resources/views/welcome.blade.php
 *  - resources/views/boutique.blade.php
 *
 * Algo : palette fixe pour les marques connues, sinon hash basé sur le nom
 * pour que la même marque ait toujours la même couleur (modulo positif).
 */

// Palette identique à celle de welcome.blade.php
const PALETTE = [
  '#0F172A', // slate-900
  '#44A08D', // teal kardafrica
  '#1F2937', // gray-800
  '#0EA5E9', // sky-500
  '#7C3AED', // violet-600
  '#DC2626', // red-600
  '#EA580C', // orange-600
  '#059669', // emerald-600
  '#0D9488', // teal-600
  '#6D28D9', // violet-700
  '#BE185D', // pink-700
];

// Marques connues — couleurs officielles (même mapping que les Blade views)
const KNOWN_BRANDS: Record<string, string> = {
  Netflix:     '#E50914',
  Spotify:     '#1DB954',
  Apple:       '#000000',
  iTunes:      '#D60017',
  Playstation: '#003791',
  PlayStation: '#003791',
  PSN:         '#003791',
  Xbox:        '#107C10',
  Amazon:      '#FF9900',
  Google:      '#01875F',
  Steam:       '#171A21',
  Roblox:      '#00A2FF',
  Nintendo:    '#E60012',
  Disney:      '#0E47A1',
  Riot:        '#D13639',
  Epic:        '#2A2A2A',
  Daywatch:    '#44A08D',
};

/**
 * Hash JS string → entier (peut être négatif)
 * Reproduit fidèlement la formule PHP `($hash << 5) - $hash` du web.
 */
function hashName(s: string): number {
  let hash = 0;
  for (let i = 0; i < s.length; i++) {
    hash = s.charCodeAt(i) + ((hash << 5) - hash);
  }
  return hash;
}

/**
 * Couleur de marque pour un produit.
 * @param name Nom du produit ou de la marque (ex: "Playstation France")
 * @returns hex color #RRGGBB
 */
export function getBrandColor(name: string | null | undefined): string {
  if (!name) return PALETTE[0];

  // 1. Match marque connue (case-insensitive, par "includes")
  for (const [key, color] of Object.entries(KNOWN_BRANDS)) {
    if (name.toLowerCase().includes(key.toLowerCase())) return color;
  }

  // 2. Hash modulo positif sur la palette
  const h = hashName(name);
  const idx = ((h % PALETTE.length) + PALETTE.length) % PALETTE.length;
  return PALETTE[idx];
}
