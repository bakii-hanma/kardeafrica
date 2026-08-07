import axios from 'axios';
import { Config } from '../constants/Config';

/**
 * Service catalogue mobile — réplique le système web.
 *
 * Stratégie en cascade :
 *  1. Backend Kardafrica `/api/v1/catalog` (toutes règles déjà appliquées)
 *  2. Sinon → afrikard direct, avec filtrage TS (UAE bloqué, region, popular,
 *     conversion FCFA — mêmes règles qu'on a sur le web côté PHP)
 *  3. Sinon → fallback statique (8 cartes pour que la boutique reste navigable)
 */

const API_URL          = Config.API_URL;          // https://kardafrica.com/api
const AFRIKARD_API_URL = Config.CATALOG_API_URL;  // VPS afrikard (srv1882929.hstgr.cloud)

// ============================================================
// Types
// ============================================================

export interface CatalogCardType {
  id: string | number | null;
  name: string | null;
  logoUrl: string | null;
  countryCode: string | null;
  region: 'europe' | 'usa' | 'africa' | 'global' | 'other' | null;
  popular_in_africa: boolean;
  /** Nombre total de montants disponibles pour ce cardType (siblings inclus) */
  products_count?: number;
  categories?: Array<{ id: number; name: string }>;
  description?: string | null;
  terms?: string | null;
  redemptionInstructions?: string | null;
}

export interface CatalogProduct {
  id: number | string;
  name: string;
  price_fcfa: number;
  native_value: number;
  native_currency: string | null;
  modifiedDate?: string | null;
  cardType: CatalogCardType;
  /** @deprecated alias rétro-compat */
  brand?: CatalogCardType & { description?: string | null };
  /** @deprecated alias rétro-compat */
  price?: { min: number; max: number; currencyCode: string };
}

export interface CatalogResponse {
  data: CatalogProduct[];
  meta: { total: number; current_page: number; last_page: number; per_page: number };
  filters_applied?: Record<string, unknown>;
}

export interface CatalogFilter {
  page?: number;
  per_page?: number;
  search?: string;
  category?: number | string | null;
  region?: ('europe' | 'usa' | 'africa' | 'global')[];
  country?: string[];
  price_range?: ('under_1000' | '1000_5000' | '5000_20000' | 'over_20000')[];
  popular?: boolean;
  sort?: 'popular' | 'price_asc' | 'price_desc' | 'newest';
}

export interface PopularCardType {
  id: string | number | null;
  name: string | null;
  logoUrl: string | null;
  countryCode: string | null;
  region: string | null;
  popular_in_africa: boolean;
  products_count: number;
  min_price_fcfa: number;
}

// ============================================================
// Constantes (mêmes valeurs que le web côté PHP)
// ============================================================

// Pays / devises bloqués (mêmes valeurs que le web : ProductApiService).
// UAE (AED) inutilisable en Afrique ; Suisse (CHF) masquée côté web.
const BLOCKED_COUNTRIES  = new Set(['AE', 'CH']);
const BLOCKED_CURRENCIES = new Set(['AED', 'CHF']);

// Mapping pays → région (cohérent avec ProductApiService::regionMap)
const REGION_MAP: Record<string, string> = {
  FR: 'europe', BE: 'europe', DE: 'europe', IT: 'europe', ES: 'europe', PT: 'europe',
  NL: 'europe', GB: 'europe', IE: 'europe', LU: 'europe', AT: 'europe',
  DK: 'europe', SE: 'europe', NO: 'europe', FI: 'europe', PL: 'europe', CZ: 'europe',
  GR: 'europe', HU: 'europe', RO: 'europe', BG: 'europe', SK: 'europe', SI: 'europe',
  HR: 'europe', EE: 'europe', LV: 'europe', LT: 'europe', MT: 'europe', CY: 'europe',
  EU: 'europe',
  US: 'usa', CA: 'usa',
  ZA: 'africa', NG: 'africa', KE: 'africa', MA: 'africa', TN: 'africa', EG: 'africa',
  DZ: 'africa', GH: 'africa', SN: 'africa', CI: 'africa', CM: 'africa', GA: 'africa',
  GLC: 'global', WW: 'global', GLOBAL: 'global', GL: 'global',
};

// Marques considérées populaires en Afrique (cohérent avec PHP)
const POPULAR_BRANDS = [
  'steam', 'psn', 'playstation', 'xbox', 'roblox', 'riot',
  'league of legends', 'valorant', 'epic games', 'epic', 'fortnite',
  'netflix', 'spotify', 'apple', 'itunes', 'app store', 'google play',
];

// Taux de change FCFA — fallback hardcodé. Au runtime, on tente de fetch
// les vrais taux configurés en admin via /api/v1/currency-rates et on les
// stocke en mémoire (mutable). Cas dégradé seul : si kardafrica API tombe
// dès le démarrage de l'app, on utilise ces valeurs figées.
const EXCHANGE_RATES: Record<string, number> = {
  XAF: 1, XOF: 1,
  EUR: 655.957, USD: 620, AED: 170, GBP: 780, CAD: 450,
  ARS: 0.7, AUD: 405, BRL: 120, TRY: 20, MXN: 35, INR: 7.5, ZAR: 35, NGN: 0.4,
};

// Palier d'arrondi FCFA (mutable — sync depuis l'admin via currency-rates)
let FCFA_ROUND_STEP = 100;

// ============================================================
// Helpers (clones TS de ProductApiService)
// ============================================================

function isBlockedProduct(p: any): boolean {
  const cc  = (p?.brand?.countryCode ?? p?.country?.isoCode ?? '').toUpperCase();
  if (cc && BLOCKED_COUNTRIES.has(cc)) return true;
  const cur = (p?.price?.currencyCode ?? '').toUpperCase();
  if (cur && BLOCKED_CURRENCIES.has(cur)) return true;
  const bn = (p?.brand?.name ?? '').toLowerCase();
  if (bn.includes('uae') || bn.includes('emirates')) return true;
  return false;
}

function tagRegion(cc: string | null | undefined): CatalogCardType['region'] {
  if (!cc) return 'other';
  return (REGION_MAP[cc.toUpperCase()] as CatalogCardType['region']) ?? 'other';
}

function isPopularInAfrica(brandName: string, productName: string): boolean {
  const bn = brandName.toLowerCase();
  const pn = productName.toLowerCase();
  return POPULAR_BRANDS.some(kw => bn.includes(kw) || pn.includes(kw));
}

function toFcfa(amount: number, currency: string | null | undefined): number {
  if (!currency) return amount;
  const rate = EXCHANGE_RATES[currency.toUpperCase()] ?? 1;
  const converted = amount * rate;
  // Arrondi vers le HAUT au prochain palier (= comportement Money::toFcfa serveur)
  if (FCFA_ROUND_STEP <= 1) return Math.ceil(converted);
  return Math.ceil(converted / FCFA_ROUND_STEP) * FCFA_ROUND_STEP;
}

/**
 * Normalise un produit afrikard brut vers la shape unifiée Kardafrica.
 * Equivalent TS de processCatalogItem() côté PHP.
 */
function normalizeAfrikardItem(p: any): CatalogProduct | null {
  if (!p?.id || !p?.brand) return null;
  if (isBlockedProduct(p)) return null;

  const native    = (p.minFaceValue ?? p.price?.min ?? 0) | 0;
  const cur       = p.price?.currencyCode ?? null;
  const price_fcfa = native > 0 && cur ? toFcfa(native, cur) : 0;

  const brandName   = p.brand?.name ?? '';
  const productName = p.name ?? '';
  const cc          = p.brand?.countryCode ?? null;

  const cardType: CatalogCardType = {
    id:                p.brand?.id ?? null,
    name:              brandName || null,
    logoUrl:           p.brand?.logoUrl ?? null,
    countryCode:       cc,
    region:            tagRegion(cc),
    popular_in_africa: isPopularInAfrica(brandName, productName),
  };

  return {
    id:               p.id,
    name:             productName,
    price_fcfa,
    native_value:     native,
    native_currency:  cur,
    modifiedDate:     p.modifiedDate ?? null,
    cardType,
  };
}

function enrichWithLegacyAliases(p: CatalogProduct): CatalogProduct {
  const native = p.native_value ?? 0;
  const cur    = p.native_currency ?? 'XAF';
  return {
    ...p,
    brand: { ...p.cardType, description: p.cardType?.description ?? null },
    price: { min: native, max: native, currencyCode: cur },
  };
}

// ============================================================
// Fallback statique (kardafrica + afrikard tous deux down)
// ============================================================
const FALLBACK: CatalogProduct[] = [
  { id: 9001, name: 'Netflix Gift Card',     price_fcfa: 9900,  native_value: 15, native_currency: 'USD', cardType: { id: 'netflix-fb',  name: 'Netflix',     logoUrl: null, countryCode: 'GLOBAL', region: 'global', popular_in_africa: true } },
  { id: 9002, name: 'Spotify Premium',       price_fcfa: 6500,  native_value: 10, native_currency: 'USD', cardType: { id: 'spotify-fb',  name: 'Spotify',     logoUrl: null, countryCode: 'GLOBAL', region: 'global', popular_in_africa: true } },
  { id: 9003, name: 'PlayStation Store',     price_fcfa: 6500,  native_value: 10, native_currency: 'USD', cardType: { id: 'psn-fb',      name: 'PlayStation', logoUrl: null, countryCode: 'GLOBAL', region: 'global', popular_in_africa: true } },
  { id: 9004, name: 'Apple Gift Card',       price_fcfa: 9900,  native_value: 15, native_currency: 'USD', cardType: { id: 'apple-fb',    name: 'Apple',       logoUrl: null, countryCode: 'GLOBAL', region: 'global', popular_in_africa: true } },
  { id: 9005, name: 'Steam Wallet',          price_fcfa: 6500,  native_value: 10, native_currency: 'USD', cardType: { id: 'steam-fb',    name: 'Steam',       logoUrl: null, countryCode: 'GLOBAL', region: 'global', popular_in_africa: true } },
  { id: 9006, name: 'Xbox Live',             price_fcfa: 9900,  native_value: 15, native_currency: 'USD', cardType: { id: 'xbox-fb',     name: 'Xbox',        logoUrl: null, countryCode: 'GLOBAL', region: 'global', popular_in_africa: true } },
  { id: 9007, name: 'Roblox Gift Card',      price_fcfa: 6500,  native_value: 10, native_currency: 'USD', cardType: { id: 'roblox-fb',   name: 'Roblox',      logoUrl: null, countryCode: 'GLOBAL', region: 'global', popular_in_africa: true } },
  { id: 9008, name: 'Google Play Card',      price_fcfa: 6500,  native_value: 10, native_currency: 'USD', cardType: { id: 'google-fb',   name: 'Google Play', logoUrl: null, countryCode: 'GLOBAL', region: 'global', popular_in_africa: true } },
];

// ============================================================
// Filtrage / tri client (utilisé pour le fallback afrikard)
// ============================================================

function applyClientFilters(items: CatalogProduct[], filter: CatalogFilter): CatalogProduct[] {
  let out = items;

  if (filter.search) {
    const q = filter.search.toLowerCase();
    out = out.filter(p =>
      p.name.toLowerCase().includes(q) ||
      (p.cardType.name ?? '').toLowerCase().includes(q)
    );
  }

  if (filter.region?.length) {
    out = out.filter(p => filter.region!.includes(p.cardType.region as any));
  }

  if (filter.popular) {
    out = out.filter(p => p.cardType.popular_in_africa);
  }

  if (filter.price_range?.length) {
    out = out.filter(p => {
      const fcfa = p.price_fcfa;
      return filter.price_range!.some(r =>
        (r === 'under_1000' && fcfa < 1000) ||
        (r === '1000_5000'  && fcfa >= 1000 && fcfa <= 5000) ||
        (r === '5000_20000' && fcfa > 5000  && fcfa <= 20000) ||
        (r === 'over_20000' && fcfa > 20000)
      );
    });
  }

  // Tri "popular" : marques Afrique + Europe FR/BE en premier
  const sort = filter.sort ?? 'popular';
  if (sort === 'price_asc')  out = [...out].sort((a, b) => a.price_fcfa - b.price_fcfa);
  if (sort === 'price_desc') out = [...out].sort((a, b) => b.price_fcfa - a.price_fcfa);
  if (sort === 'popular') {
    const ccPrio: Record<string, number> = { FR: 0, BE: 1, EU: 2, CH: 3, LU: 3, GB: 4, US: 5 };
    out = [...out].sort((a, b) => {
      const aPop = a.cardType.popular_in_africa ? 0 : 1;
      const bPop = b.cardType.popular_in_africa ? 0 : 1;
      if (aPop !== bPop) return aPop - bPop;
      const aReg = a.cardType.region === 'europe' ? 0 : a.cardType.region === 'usa' ? 1 : 2;
      const bReg = b.cardType.region === 'europe' ? 0 : b.cardType.region === 'usa' ? 1 : 2;
      if (aReg !== bReg) return aReg - bReg;
      const aCc = ccPrio[(a.cardType.countryCode ?? '').toUpperCase()] ?? 99;
      const bCc = ccPrio[(b.cardType.countryCode ?? '').toUpperCase()] ?? 99;
      return aCc - bCc;
    });
  }

  return out;
}

// ============================================================
// Service public
// ============================================================

export const CatalogService = {
  /**
   * Liste paginée de produits — essaie Kardafrica API, fallback afrikard direct.
   */
  async getProducts(filter: CatalogFilter = {}): Promise<CatalogResponse> {
    // 1. Essai backend Kardafrica
    try {
      const params: Record<string, unknown> = {
        page: filter.page ?? 1,
        per_page: filter.per_page ?? 20,
      };
      if (filter.search)              params.search = filter.search;
      if (filter.category)            params.category = filter.category;
      if (filter.popular)             params.popular = 1;
      if (filter.sort)                params.sort = filter.sort;
      if (filter.region?.length)      params.region = filter.region;
      if (filter.country?.length)     params.country = filter.country;
      if (filter.price_range?.length) params.price_range = filter.price_range;

      const res = await axios.get(`${API_URL}/v1/catalog`, {
        params,
        timeout: 12000,
        paramsSerializer: { indexes: null },
      });

      const body = res.data;
      if (body?.success && Array.isArray(body.data) && body.data.length > 0) {
        return {
          data: body.data.map(enrichWithLegacyAliases),
          meta: body.meta,
          filters_applied: body.filters_applied,
        };
      }
    } catch (e: any) {
      // 404 = endpoint pas encore déployé en prod → on passe au fallback afrikard
      if (e?.response?.status !== 404) {
        console.warn('Kardafrica API error:', e?.message);
      }
    }

    // 2. Fallback afrikard direct (avec règles web appliquées en TS)
    try {
      const params: Record<string, unknown> = {
        pageIndex: 0,
        pageSize:  Math.min(100, (filter.per_page ?? 20) * 5), // sur-fetch pour pouvoir filtrer
      };
      if (filter.search) params.name = filter.search;

      const res = await axios.get(`${AFRIKARD_API_URL}/catalog`, { params, timeout: 12000 });
      const items = (res.data?.items ?? []) as any[];

      const normalized = items
        .map(normalizeAfrikardItem)
        .filter((p): p is CatalogProduct => p !== null);

      const filtered = applyClientFilters(normalized, filter);
      const perPage  = filter.per_page ?? 20;
      const page     = filter.page ?? 1;
      const start    = (page - 1) * perPage;
      const paged    = filtered.slice(start, start + perPage).map(enrichWithLegacyAliases);

      if (paged.length > 0) {
        return {
          data: paged,
          meta: {
            total:        filtered.length,
            current_page: page,
            last_page:    Math.max(1, Math.ceil(filtered.length / perPage)),
            per_page:     perPage,
          },
        };
      }
    } catch (e: any) {
      console.warn('Catalog API down, fallback static:', e?.message);
    }

    // 3. Fallback statique
    const staticFiltered = applyClientFilters(FALLBACK.map(enrichWithLegacyAliases), filter);
    return {
      data: staticFiltered,
      meta: { total: staticFiltered.length, current_page: 1, last_page: 1, per_page: staticFiltered.length },
    };
  },

  async getPopular(limit: number = 12): Promise<PopularCardType[]> {
    try {
      const res = await axios.get(`${API_URL}/v1/catalog/popular`, { params: { limit }, timeout: 8000 });
      if (res.data?.success && Array.isArray(res.data.data)) return res.data.data;
    } catch { /* fallback below */ }

    // Fallback : on réplique getCardTypes() web — on ne FILTRE pas par popular
    // (sinon on perd Apple France, Spotify France… si pas tagués), on TRIE par
    // popular d'abord puis on dégroupe par cardType.id (ou name) en gardant le
    // min_price_fcfa du cardType. Sur-fetch large pour avoir un échantillon
    // représentatif (le fallback afrikard ne ramène que la page 0).
    const list = await this.getProducts({ per_page: 100, sort: 'popular' });
    const byCardType = new Map<string, { product: CatalogProduct; minPrice: number; count: number }>();
    for (const p of list.data) {
      const key = String(p.cardType.id ?? p.cardType.name ?? '');
      if (!key) continue;
      const fcfa = p.price_fcfa;
      const cur  = byCardType.get(key);
      if (!cur) {
        byCardType.set(key, { product: p, minPrice: fcfa, count: 1 });
      } else {
        cur.count++;
        if (fcfa > 0 && (cur.minPrice === 0 || fcfa < cur.minPrice)) cur.minPrice = fcfa;
      }
    }
    const out: PopularCardType[] = [];
    for (const { product: p, minPrice, count } of byCardType.values()) {
      out.push({
        id:                p.cardType.id,
        name:              p.cardType.name,
        logoUrl:           p.cardType.logoUrl,
        countryCode:       p.cardType.countryCode,
        region:            p.cardType.region,
        popular_in_africa: p.cardType.popular_in_africa,
        products_count:    count,
        min_price_fcfa:    minPrice,
      });
      if (out.length >= limit) break;
    }
    return out;
  },

  async getProductById(id: string | number): Promise<{ data: any; siblings?: any[]; products?: any[] } | null> {
    try {
      const res = await axios.get(`${API_URL}/v1/catalog/${id}`, { timeout: 10000 });
      if (res.data?.success) return res.data;
    } catch { /* fallback below */ }

    // Fallback afrikard direct par productId
    try {
      const res = await axios.get(`${AFRIKARD_API_URL}/catalog`, {
        params: { productId: id, pageSize: 5 },
        timeout: 8000,
      });
      const items = (res.data?.items ?? []) as any[];
      const product = items.find(p => String(p.id) === String(id));
      if (product) {
        const normalized = normalizeAfrikardItem(product);
        if (normalized) {
          return {
            data: enrichWithLegacyAliases({
              ...normalized,
              cardType: {
                ...normalized.cardType,
                description:            product.brand?.description ?? null,
                terms:                  product.brand?.terms ?? null,
                redemptionInstructions: product.brand?.redemptionInstructions ?? null,
              },
            }),
          };
        }
      }
    } catch (e: any) {
      console.warn('Product detail fallback failed:', e?.message);
    }
    return null;
  },

  async getCategories(): Promise<Array<{ id: number; name: string; icon: string; emoji: string }>> {
    try {
      const res = await axios.get(`${API_URL}/v1/categories`, { timeout: 6000 });
      return res.data?.data ?? [];
    } catch {
      // Fallback constant — mêmes 6 catégories que côté web
      return [
        { id: 1, name: 'Divertissement', icon: 'film',           emoji: '🎬' },
        { id: 2, name: 'Jeux Vidéo',     icon: 'gamepad-2',      emoji: '🎮' },
        { id: 3, name: 'Musique',        icon: 'music',          emoji: '🎵' },
        { id: 4, name: 'Shopping',       icon: 'shopping-cart',  emoji: '🛍️' },
        { id: 5, name: 'Daywatch',       icon: 'tv',             emoji: '📺' },
        { id: 6, name: 'Voyage',         icon: 'map',            emoji: '✈️' },
      ];
    }
  },

  formatFcfa(amount: number): string {
    return new Intl.NumberFormat('fr-FR').format(Math.round(amount)) + ' FCFA';
  },

  /**
   * Sync les taux de change avec l'admin (BDD serveur). À appeler au démarrage
   * de l'app pour que le fallback afrikard direct utilise les bons taux.
   * Best-effort : silencieux en cas d'échec (les valeurs hardcodées restent).
   */
  async refreshCurrencyRates(): Promise<void> {
    try {
      const res = await axios.get(`${API_URL}/v1/currency-rates`, { timeout: 6000 });
      const rates = res.data?.rates;
      const step  = res.data?.round_step;
      if (rates && typeof rates === 'object') {
        for (const [code, rate] of Object.entries(rates)) {
          const r = Number(rate);
          if (Number.isFinite(r) && r > 0) {
            EXCHANGE_RATES[code.toUpperCase()] = r;
          }
        }
      }
      if (Number.isFinite(Number(step)) && Number(step) > 0) {
        FCFA_ROUND_STEP = Number(step);
      }
    } catch {
      // Silencieux — on garde les valeurs hardcodées
    }
  },
};
