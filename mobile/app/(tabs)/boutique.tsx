import { View, Text, TouchableOpacity, ScrollView, Image, StatusBar, RefreshControl, Animated, Easing, Modal, Pressable } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect, useMemo, useRef, useCallback } from 'react';
import type { ReactNode } from 'react';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MagnifyingGlassIcon, Squares2X2Icon, ListBulletIcon, ChevronRightIcon, FireIcon, XMarkIcon, AdjustmentsHorizontalIcon } from 'react-native-heroicons/outline';
import { CATEGORIES } from '../../data/mock';
import { CatalogService, CatalogProduct, CatalogFilter } from '../../services/catalog';
import EmptyState from '../../components/EmptyState';
import LoadingKeychain from '../../components/LoadingKeychain';
import NavbarProfile from '../../components/NavbarProfile';
import { getBrandColor } from '../../utils/brandColor';
import { GiftCardVisual } from '../../components/GiftCardVisual';

function formatFcfa(n: number): string {
  return new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FCFA';
}

interface ProductCardProps {
  product: CatalogProduct;
  onPress: () => void;
  delay: number;
  variant: 'grid' | 'list';
}

/**
 * Carte produit — design exactement aligné sur le composant <x-product-card>
 * du web (resources/views/components/product-card.blade.php) :
 *
 *  ┌────────────────────────────┐
 *  │ ◯◯◯◯ pattern halos         │ ← brand color top
 *  │ GIFT CARD                  │
 *  │ Playstation                │ ← brand short name (gros)
 *  │                       ▮▮   │ ← chip
 *  ├────────────────────────────┤
 *  │ ◉ logo                     │ ← floating logo
 *  │ Playstation France         │ ← cardType.name complet
 *  │ POPULAIRE                  │ ← badge orange si popular_in_africa
 *  │ À partir de  92 988 FCFA   │
 *  └────────────────────────────┘
 */
function ProductCard({ product, onPress, delay, variant }: ProductCardProps) {
  const opacity    = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(12)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(opacity, { toValue: 1, duration: 350, delay, useNativeDriver: true, easing: Easing.out(Easing.cubic) }),
      Animated.timing(translateY, { toValue: 0, duration: 350, delay, useNativeDriver: true, easing: Easing.out(Easing.cubic) }),
    ]).start();
  }, []);

  // Mapping aligné sur <x-product-card> web :
  //  - $brandLabel (TOP coloré)   = cardType.name complet, ex: "ROBLOX France"
  //  - $name       (BOTTOM blanc) = product.name,         ex: "Roblox FR" / "Xbox FR 50 EUR"
  const cardTypeName = product.cardType.name ?? '';
  const productName  = product.name ?? cardTypeName ?? 'Carte';
  const brandColor   = getBrandColor(cardTypeName || productName);
  const initial      = (cardTypeName || productName).charAt(0).toUpperCase();
  const logoUrl      = product.cardType.logoUrl;
  const isPopular    = product.cardType.popular_in_africa;

  if (variant === 'grid') {
    return (
      <Animated.View style={{ opacity, transform: [{ translateY }] }} className="w-[48%] mb-3">
        <TouchableOpacity
          activeOpacity={0.85}
          onPress={onPress}
          style={{
            backgroundColor: 'white',
            borderRadius: 16,
            overflow: 'hidden',
            borderWidth: 1,
            borderColor: '#F1F5F9',
            shadowColor: '#0F172A',
            shadowOffset: { width: 0, height: 4 },
            shadowOpacity: 0.06,
            shadowRadius: 10,
            elevation: 2,
          }}
        >
          {/* ===== VISUEL HYBRIDE (= proposition design) ===== */}
          <GiftCardVisual
            brandLabel={cardTypeName || productName}
            brandColor={brandColor}
            countryCode={product.cardType.countryCode}
            currency={product.native_currency}
            logoUrl={product.cardType.logoUrl}
            gradId={`grid-${product.id}`}
          />

          {/* ===== BOTTOM : nom + montants + prix (= web) ===== */}
          <View style={{ paddingHorizontal: 14, paddingTop: 12, paddingBottom: 12 }}>
            {/* product.name + badge POPULAIRE */}
            <View style={{ flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 6 }}>
              <Text numberOfLines={2} style={{ flex: 1, fontSize: 12, fontWeight: '700', color: '#0F172A', lineHeight: 16 }}>
                {productName}
              </Text>
              {isPopular && (
                <View style={{ flexShrink: 0, paddingHorizontal: 5, paddingVertical: 2, borderRadius: 4, borderWidth: 1, borderColor: '#FDE68A', backgroundColor: '#FEF3C7' }}>
                  <Text style={{ fontSize: 8, fontWeight: '900', color: '#B45309', letterSpacing: 0.6 }}>POPULAIRE</Text>
                </View>
              )}
            </View>

            {/* X montants dispo (toujours affiché si > 1) */}
            {(product.cardType.products_count ?? 0) > 1 && (
              <Text style={{ fontSize: 10, color: '#94A3B8', marginTop: 2, fontWeight: '600' }}>
                {product.cardType.products_count} montants disponibles
              </Text>
            )}

            {/* Divider + Prix */}
            <View style={{ marginTop: 10, paddingTop: 10, borderTopWidth: 1, borderTopColor: '#F1F5F9', flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between' }}>
              <Text style={{ fontSize: 10, color: '#94A3B8', fontWeight: '500' }}>Dès</Text>
              <Text numberOfLines={1} style={{ fontSize: 14, fontWeight: '900', color: '#0F172A' }}>
                {formatFcfa(product.price_fcfa)}
              </Text>
            </View>
          </View>
        </TouchableOpacity>
      </Animated.View>
    );
  }

  // ===== List variant (horizontal) — texte blanc sur fond couleur, look mini-carte =====
  return (
    <Animated.View style={{ opacity, transform: [{ translateY }] }}>
      <TouchableOpacity
        activeOpacity={0.85}
        onPress={onPress}
        style={{
          flexDirection: 'row',
          backgroundColor: 'white',
          borderRadius: 16,
          marginBottom: 10,
          overflow: 'hidden',
          borderWidth: 1,
          borderColor: '#E2E8F0',
          shadowColor: '#0F172A',
          shadowOffset: { width: 0, height: 2 },
          shadowOpacity: 0.05,
          shadowRadius: 8,
          elevation: 2,
        }}
      >
        {/* ===== MINI-CARTE GAUCHE : couleur brand + texte BLANC ===== */}
        <View style={{ backgroundColor: brandColor, width: 150, padding: 12, position: 'relative', overflow: 'hidden', justifyContent: 'space-between' }}>
          {/* Halos décoratifs (mêmes que la grille) */}
          <View style={{ position: 'absolute', top: -30, right: -30, width: 100, height: 100, borderRadius: 50, backgroundColor: 'rgba(255,255,255,0.16)' }} />
          <View style={{ position: 'absolute', bottom: -20, left: -20, width: 60, height: 60, borderRadius: 30, backgroundColor: 'rgba(255,255,255,0.10)' }} />
          <View style={{ position: 'absolute', top: 16, right: 18, width: 22, height: 22, borderRadius: 11, borderWidth: 1, borderColor: 'rgba(255,255,255,0.20)' }} />

          {/* Top : GIFT CARD + cardType.name (= brand-label web, BLANC) */}
          <View style={{ position: 'relative' }}>
            <Text style={{ color: 'rgba(255,255,255,0.85)', fontSize: 9, fontWeight: '800', letterSpacing: 1.6, textTransform: 'uppercase' }}>
              Gift Card
            </Text>
            <Text numberOfLines={2} style={{ color: 'white', fontSize: 16, fontWeight: '900', marginTop: 4, letterSpacing: -0.3, lineHeight: 19 }}>
              {cardTypeName || productName}
            </Text>
          </View>

          {/* Bottom : logo + chip */}
          <View style={{ flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between', marginTop: 6 }}>
            <View style={{
              width: 36, height: 36, borderRadius: 10, backgroundColor: 'white',
              alignItems: 'center', justifyContent: 'center', overflow: 'hidden',
              padding: 4, borderWidth: 1, borderColor: 'rgba(255,255,255,0.30)',
            }}>
              {logoUrl ? (
                <Image source={{ uri: logoUrl }} style={{ width: '100%', height: '100%' }} resizeMode="contain" />
              ) : (
                <Text style={{ color: brandColor, fontSize: 16, fontWeight: '900' }}>{initial}</Text>
              )}
            </View>
            <View style={{ width: 26, height: 18, borderRadius: 4, backgroundColor: '#FCD34D', borderWidth: 1, borderColor: 'rgba(255,255,255,0.30)' }} />
          </View>
        </View>

        {/* ===== DÉTAILS DROITE (fond blanc) ===== */}
        <View style={{ flex: 1, padding: 12, justifyContent: 'space-between' }}>
          <View>
            <Text numberOfLines={2} style={{ fontSize: 13, fontWeight: '800', color: '#0F172A', lineHeight: 17 }}>
              {productName}
            </Text>
            {(product.cardType.products_count ?? 0) > 1 && (
              <Text style={{ fontSize: 10, color: '#94A3B8', marginTop: 3 }}>
                {product.cardType.products_count} montants dispo
              </Text>
            )}
            {isPopular && (
              <View style={{ alignSelf: 'flex-start', marginTop: 5, paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4, backgroundColor: '#FEF3C7' }}>
                <Text style={{ fontSize: 9, fontWeight: '900', color: '#B45309', letterSpacing: 0.8 }}>POPULAIRE</Text>
              </View>
            )}
          </View>
          <View style={{ flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between', marginTop: 6 }}>
            <View>
              <Text style={{ fontSize: 9, color: '#94A3B8', fontWeight: '700', letterSpacing: 0.5 }}>À PARTIR DE</Text>
              <Text style={{ fontSize: 15, fontWeight: '900', color: '#0F172A', marginTop: 1 }}>
                {formatFcfa(product.price_fcfa)}
              </Text>
            </View>
            <View style={{ width: 28, height: 28, borderRadius: 9, backgroundColor: '#F1F5F9', alignItems: 'center', justifyContent: 'center' }}>
              <ChevronRightIcon size={14} color="#94A3B8" />
            </View>
          </View>
        </View>
      </TouchableOpacity>
    </Animated.View>
  );
}

// Filtres rapides : régions et marques top
const REGION_PILLS: Array<{ code: 'europe' | 'usa' | 'global' | 'africa'; label: string; emoji: string }> = [
  { code: 'europe', label: 'Europe',     emoji: '🇪🇺' },
  { code: 'usa',    label: 'États-Unis', emoji: '🇺🇸' },
  { code: 'global', label: 'Mondial',    emoji: '🌍' },
  { code: 'africa', label: 'Afrique',    emoji: '🌍' },
];

const BRAND_PILLS = [
  { search: 'Steam',       label: 'Steam',       color: '#171A21' },
  { search: 'Playstation', label: 'PSN',         color: '#003791' },
  { search: 'Xbox',        label: 'Xbox',        color: '#107C10' },
  { search: 'Netflix',     label: 'Netflix',     color: '#E50914' },
  { search: 'Spotify',     label: 'Spotify',     color: '#1DB954' },
  { search: 'Apple',       label: 'Apple',       color: '#000000' },
  { search: 'Roblox',      label: 'Roblox',      color: '#00A2FF' },
  { search: 'Riot',        label: 'Riot',        color: '#D13639' },
  { search: 'Epic',        label: 'Epic',        color: '#2A2A2A' },
];

// Plages de prix (cohérent avec ProductApiService côté web)
type PriceRangeCode = 'under_1000' | '1000_5000' | '5000_20000' | 'over_20000';
const PRICE_RANGES: Array<{ code: PriceRangeCode; label: string; sub: string }> = [
  { code: 'under_1000', label: '< 1 000',     sub: 'FCFA' },
  { code: '1000_5000',  label: '1k — 5k',    sub: 'FCFA' },
  { code: '5000_20000', label: '5k — 20k',   sub: 'FCFA' },
  { code: 'over_20000', label: '> 20k',       sub: 'FCFA' },
];

// Pays disponibles (mêmes que web)
const COUNTRY_PILLS: Array<{ code: string; label: string; flag: string }> = [
  { code: 'SN', label: 'Sénégal',         flag: '🇸🇳' },
  { code: 'CI', label: 'Côte d\'Ivoire',   flag: '🇨🇮' },
  { code: 'ML', label: 'Mali',             flag: '🇲🇱' },
  { code: 'GA', label: 'Gabon',            flag: '🇬🇦' },
  { code: 'CM', label: 'Cameroun',         flag: '🇨🇲' },
];

// Options de tri
type SortCode = 'popular' | 'price_asc' | 'price_desc' | 'newest';
const SORT_OPTIONS: Array<{ code: SortCode; label: string; icon: string }> = [
  { code: 'popular',    label: 'Populaire',       icon: '⭐' },
  { code: 'newest',     label: 'Nouveautés',      icon: '🆕' },
  { code: 'price_asc',  label: 'Prix croissant',  icon: '↑' },
  { code: 'price_desc', label: 'Prix décroissant',icon: '↓' },
];

const PER_PAGE = 24;

/**
 * Pagination — réplique le pattern de boutique.blade.php :
 *   < 1 … 5 6 7 … 311 >
 * Affiche toujours la première page, la dernière, et la page courante ±1.
 * Quand un saut > 1 entre deux numéros affichés, on insère "…".
 */
interface PaginationProps {
  currentPage: number;
  lastPage: number;
  total: number;
  perPage: number;
  onChange: (page: number) => void;
}
function Pagination({ currentPage, lastPage, total, perPage, onChange }: PaginationProps) {
  // Calcule les numéros de page à afficher (= logique du blade web)
  const visiblePages: number[] = [];
  for (let i = 1; i <= lastPage; i++) {
    if (i === 1 || i === lastPage || Math.abs(i - currentPage) <= 1) {
      visiblePages.push(i);
    }
  }

  const start = (currentPage - 1) * perPage + 1;
  const end   = Math.min(currentPage * perPage, total);

  const renderPill = (children: ReactNode, opts: {
    active?: boolean; disabled?: boolean; onPress?: () => void;
  } = {}) => {
    const { active, disabled, onPress } = opts;
    const pillStyle = {
      width: 38, height: 38, borderRadius: 10,
      alignItems: 'center' as const, justifyContent: 'center' as const,
      borderWidth: 1,
      backgroundColor: active ? '#44A08D' : disabled ? '#F8FAFC' : 'white',
      borderColor:     active ? '#44A08D' : '#E2E8F0',
      shadowColor: active ? '#44A08D' : 'transparent',
      shadowOffset: active ? { width: 0, height: 4 } : undefined,
      shadowOpacity: active ? 0.25 : 0,
      shadowRadius: active ? 8 : 0,
      elevation: active ? 3 : 0,
    };
    if (onPress && !disabled) {
      return (
        <TouchableOpacity activeOpacity={0.7} onPress={onPress} style={pillStyle}>
          {children}
        </TouchableOpacity>
      );
    }
    return <View style={pillStyle}>{children}</View>;
  };

  let prevPage: number | null = null;
  return (
    <View style={{ marginTop: 22, marginBottom: 8, alignItems: 'center' }}>
      {/* Compteur "X-Y sur N" */}
      <Text style={{ fontSize: 11, color: '#64748B', fontWeight: '600', marginBottom: 10 }}>
        <Text style={{ color: '#0F172A', fontWeight: '900' }}>{start}</Text>
        <Text>–</Text>
        <Text style={{ color: '#0F172A', fontWeight: '900' }}>{end}</Text>
        <Text> sur </Text>
        <Text style={{ color: '#0F172A', fontWeight: '900' }}>{new Intl.NumberFormat('fr-FR').format(total)}</Text>
        <Text> · page {currentPage}/{lastPage}</Text>
      </Text>

      {/* Nav <  1 … 5 6 7 … 311  > */}
      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 4, flexWrap: 'wrap', justifyContent: 'center' }}>
        {/* Prev */}
        {renderPill(
          <Text style={{ fontSize: 16, color: currentPage > 1 ? '#475569' : '#CBD5E1', fontWeight: '700' }}>‹</Text>,
          { disabled: currentPage <= 1, onPress: () => onChange(currentPage - 1) }
        )}

        {visiblePages.map((p, idx) => {
          const ellipsis = prevPage !== null && p - prevPage > 1
            ? <View key={`gap-${idx}`} style={{ width: 22, height: 38, alignItems: 'center', justifyContent: 'center' }}>
                <Text style={{ color: '#94A3B8', fontWeight: '700' }}>…</Text>
              </View>
            : null;
          prevPage = p;
          return (
            <View key={`pwrap-${p}`} style={{ flexDirection: 'row', alignItems: 'center', gap: 4 }}>
              {ellipsis}
              {renderPill(
                <Text style={{ fontSize: 13, fontWeight: '900', color: p === currentPage ? 'white' : '#475569' }}>{p}</Text>,
                { active: p === currentPage, onPress: () => onChange(p) }
              )}
            </View>
          );
        })}

        {/* Next */}
        {renderPill(
          <Text style={{ fontSize: 16, color: currentPage < lastPage ? '#475569' : '#CBD5E1', fontWeight: '700' }}>›</Text>,
          { disabled: currentPage >= lastPage, onPress: () => onChange(currentPage + 1) }
        )}
      </View>
    </View>
  );
}

export default function BoutiqueScreen() {
  const router = useRouter();
  const scrollRef = useRef<ScrollView>(null);
  const [viewMode, setViewMode]       = useState<'grid' | 'list'>('grid');
  const [products, setProducts]       = useState<CatalogProduct[]>([]);
  const [isLoading, setIsLoading]     = useState(true);
  const [refreshing, setRefreshing]   = useState(false);
  const [total, setTotal]             = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage]       = useState(1);

  // Filtres actifs (parité avec le web)
  const [popularOnly, setPopularOnly]               = useState(false);
  const [selectedRegions, setSelectedRegions]       = useState<('europe' | 'usa' | 'global' | 'africa')[]>([]);
  const [searchTerm, setSearchTerm]                 = useState('');
  const [selectedCategory, setSelectedCategory]     = useState<number | null>(null);
  const [selectedPriceRanges, setSelectedPriceRanges] = useState<PriceRangeCode[]>([]);
  const [selectedCountries, setSelectedCountries]   = useState<string[]>([]);
  const [sortBy, setSortBy]                         = useState<SortCode>('popular');
  const [filtersOpen, setFiltersOpen]               = useState(false);

  const loadProducts = useCallback(async (page: number = 1) => {
    try {
      setIsLoading(true);
      const filter: CatalogFilter = {
        page,
        per_page: PER_PAGE,
        sort: sortBy,
      };
      if (popularOnly)                  filter.popular = true;
      if (selectedRegions.length)       filter.region = selectedRegions;
      if (searchTerm.trim())            filter.search = searchTerm.trim();
      if (selectedCategory !== null)    filter.category = selectedCategory;
      if (selectedPriceRanges.length)   filter.price_range = selectedPriceRanges;
      if (selectedCountries.length)     filter.country = selectedCountries;

      const response = await CatalogService.getProducts(filter);
      setProducts(response.data);
      setTotal(response.meta?.total ?? response.data.length);
      setLastPage(response.meta?.last_page ?? 1);
      setCurrentPage(response.meta?.current_page ?? page);
    } catch (err) {
      console.error('Failed to load products:', err);
    } finally {
      setIsLoading(false);
      setRefreshing(false);
    }
  }, [popularOnly, selectedRegions, searchTerm, selectedCategory, selectedPriceRanges, selectedCountries, sortBy]);

  // Reset à la page 1 quand un filtre change
  useEffect(() => {
    setCurrentPage(1);
    loadProducts(1);
  }, [popularOnly, selectedRegions, searchTerm, selectedCategory, selectedPriceRanges, selectedCountries, sortBy]);

  const goToPage = (page: number) => {
    if (page < 1 || page > lastPage || page === currentPage) return;
    loadProducts(page);
    scrollRef.current?.scrollTo({ y: 0, animated: true });
  };

  const onRefresh = () => { setRefreshing(true); loadProducts(currentPage); };

  const toggleRegion = (code: 'europe' | 'usa' | 'global' | 'africa') => {
    setSelectedRegions(prev => prev.includes(code) ? prev.filter(r => r !== code) : [...prev, code]);
  };

  const setBrand = (term: string) => {
    setSearchTerm(prev => prev.toLowerCase() === term.toLowerCase() ? '' : term);
  };

  const activeFiltersCount =
    (popularOnly ? 1 : 0) +
    selectedRegions.length +
    (searchTerm ? 1 : 0) +
    (selectedCategory !== null ? 1 : 0) +
    selectedPriceRanges.length +
    selectedCountries.length +
    (sortBy !== 'popular' ? 1 : 0);

  const clearAll = () => {
    setPopularOnly(false);
    setSelectedRegions([]);
    setSearchTerm('');
    setSelectedCategory(null);
    setSelectedPriceRanges([]);
    setSelectedCountries([]);
    setSortBy('popular');
  };

  const togglePriceRange = (code: PriceRangeCode) => {
    setSelectedPriceRanges(prev => prev.includes(code) ? prev.filter(c => c !== code) : [...prev, code]);
  };
  const toggleCountry = (code: string) => {
    setSelectedCountries(prev => prev.includes(code) ? prev.filter(c => c !== code) : [...prev, code]);
  };

  return (
    <SafeAreaView className="flex-1" style={{ backgroundColor: '#FAFAF7' }}>
      <StatusBar barStyle="light-content" backgroundColor="#0F172A" />

      {/* ========== Header sombre ========== */}
      <View style={{ backgroundColor: '#0F172A' }} className="px-4 pt-2 pb-5">
        <View style={{ position: 'absolute', top: -50, right: -50, width: 220, height: 220, borderRadius: 110, backgroundColor: 'rgba(78,205,196,0.12)' }} />

        <View className="flex-row justify-between items-center mb-3">
          <View className="flex-row items-center">
            <Image source={require('../assets/FAVCON-KARDAFRICA-.png')} className="w-9 h-9 mr-2 rounded-lg" style={{ backgroundColor: 'rgba(255,255,255,0.08)' }} resizeMode="contain" />
            <Text className="text-xl font-black text-white tracking-tight">Boutique</Text>
          </View>
          {/* Right cluster : toggle vue + profil — même hauteur 40px */}
          <View className="flex-row items-center" style={{ gap: 10 }}>
            {/* Segmented control grid/list — hauteur 40 alignée avec avatar */}
            <View
              className="flex-row items-center"
              style={{
                height: 40,
                paddingHorizontal: 3,
                borderRadius: 12,
                borderWidth: 1,
                backgroundColor: 'rgba(255,255,255,0.06)',
                borderColor: 'rgba(255,255,255,0.12)',
              }}
            >
              <TouchableOpacity
                onPress={() => setViewMode('grid')}
                style={{
                  width: 34, height: 34,
                  borderRadius: 9,
                  alignItems: 'center', justifyContent: 'center',
                  backgroundColor: viewMode === 'grid' ? '#44A08D' : 'transparent',
                }}
              >
                <Squares2X2Icon size={16} color={viewMode === 'grid' ? 'white' : '#94A3B8'} />
              </TouchableOpacity>
              <TouchableOpacity
                onPress={() => setViewMode('list')}
                style={{
                  width: 34, height: 34,
                  borderRadius: 9,
                  alignItems: 'center', justifyContent: 'center',
                  backgroundColor: viewMode === 'list' ? '#44A08D' : 'transparent',
                }}
              >
                <ListBulletIcon size={16} color={viewMode === 'list' ? 'white' : '#94A3B8'} />
              </TouchableOpacity>
            </View>
            <NavbarProfile />
          </View>
        </View>

        <TouchableOpacity
          activeOpacity={0.85}
          className="flex-row items-center rounded-xl px-3 py-3 border"
          style={{ backgroundColor: 'rgba(255,255,255,0.06)', borderColor: 'rgba(255,255,255,0.10)' }}
          onPress={() => router.push('/search')}
        >
          <MagnifyingGlassIcon size={16} color="#94A3B8" />
          <Text className="flex-1 ml-2 text-sm" style={{ color: '#64748B' }}>Rechercher Netflix, Apple, Spotify…</Text>
        </TouchableOpacity>
      </View>

      {/* ===== Bande filtres rapides toujours visible (Top Afrique + Régions) ===== */}
      <View className="bg-white border-b border-slate-100" style={{ paddingVertical: 10 }}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 16, gap: 8 }}>
          {/* 🔥 Top Afrique */}
          <TouchableOpacity
            activeOpacity={0.85}
            onPress={() => setPopularOnly(p => !p)}
            style={{
              flexDirection: 'row', alignItems: 'center', gap: 6,
              paddingHorizontal: 14, paddingVertical: 9,
              borderRadius: 999, borderWidth: 1.5,
              backgroundColor: popularOnly ? '#F43F5E' : 'white',
              borderColor:     popularOnly ? '#F43F5E' : '#E2E8F0',
            }}
          >
            <Text style={{ fontSize: 13 }}>🔥</Text>
            <Text style={{ fontSize: 12, fontWeight: '900', color: popularOnly ? 'white' : '#475569' }}>Top Afrique</Text>
          </TouchableOpacity>

          <View style={{ width: 1, backgroundColor: '#E2E8F0', marginHorizontal: 4 }} />

          {/* Régions */}
          {REGION_PILLS.map(r => {
            const active = selectedRegions.includes(r.code);
            return (
              <TouchableOpacity
                key={r.code}
                activeOpacity={0.85}
                onPress={() => toggleRegion(r.code)}
                style={{
                  flexDirection: 'row', alignItems: 'center', gap: 6,
                  paddingHorizontal: 14, paddingVertical: 9,
                  borderRadius: 999, borderWidth: 1.5,
                  backgroundColor: active ? '#0F172A' : 'white',
                  borderColor:     active ? '#0F172A' : '#E2E8F0',
                }}
              >
                <Text style={{ fontSize: 13 }}>{r.emoji}</Text>
                <Text style={{ fontSize: 12, fontWeight: '900', color: active ? 'white' : '#475569' }}>{r.label}</Text>
              </TouchableOpacity>
            );
          })}

          {/* Bouton "Plus de filtres" qui ouvre la sheet complète */}
          <TouchableOpacity
            activeOpacity={0.85}
            onPress={() => setFiltersOpen(true)}
            style={{
              flexDirection: 'row', alignItems: 'center', gap: 5,
              paddingHorizontal: 12, paddingVertical: 9,
              borderRadius: 999, borderWidth: 1.5,
              backgroundColor: activeFiltersCount > 2 ? '#0F172A' : '#F1F5F9',
              borderColor:     activeFiltersCount > 2 ? '#0F172A' : '#E2E8F0',
            }}
          >
            <AdjustmentsHorizontalIcon size={14} color={activeFiltersCount > 2 ? 'white' : '#475569'} />
            <Text style={{ fontSize: 12, fontWeight: '900', color: activeFiltersCount > 2 ? 'white' : '#475569' }}>
              Tous les filtres
            </Text>
            {activeFiltersCount > 0 && (
              <View style={{
                paddingHorizontal: 5, height: 16, minWidth: 16, borderRadius: 999,
                backgroundColor: activeFiltersCount > 2 ? '#44A08D' : '#0F172A',
                alignItems: 'center', justifyContent: 'center',
              }}>
                <Text style={{ color: 'white', fontSize: 10, fontWeight: '900' }}>{activeFiltersCount}</Text>
              </View>
            )}
          </TouchableOpacity>
        </ScrollView>
      </View>

      {/* Filtres actifs (résumé compact détaillé) */}
      {activeFiltersCount > 0 && (
        <View className="bg-white border-b border-slate-100" style={{ paddingHorizontal: 16, paddingVertical: 8 }}>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 6 }}>
            {popularOnly && (
              <TouchableOpacity onPress={() => setPopularOnly(false)}
                className="flex-row items-center"
                style={{ gap: 4, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: '#FFE4E6' }}>
                <Text style={{ fontSize: 11 }}>🔥</Text>
                <Text className="text-[11px] font-bold" style={{ color: '#BE123C' }}>Top Afrique</Text>
                <XMarkIcon size={11} color="#BE123C" />
              </TouchableOpacity>
            )}
            {selectedRegions.map(r => {
              const pill = REGION_PILLS.find(p => p.code === r);
              if (!pill) return null;
              return (
                <TouchableOpacity key={r} onPress={() => toggleRegion(r)}
                  className="flex-row items-center"
                  style={{ gap: 4, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: '#F1F5F9' }}>
                  <Text style={{ fontSize: 11 }}>{pill.emoji}</Text>
                  <Text className="text-[11px] font-bold" style={{ color: '#475569' }}>{pill.label}</Text>
                  <XMarkIcon size={11} color="#475569" />
                </TouchableOpacity>
              );
            })}
            {searchTerm && (
              <TouchableOpacity onPress={() => setSearchTerm('')}
                className="flex-row items-center"
                style={{ gap: 4, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: '#F1F5F9' }}>
                <Text className="text-[11px] font-bold" style={{ color: '#475569' }}>"{searchTerm}"</Text>
                <XMarkIcon size={11} color="#475569" />
              </TouchableOpacity>
            )}
            {selectedCategory !== null && (() => {
              const cat = CATEGORIES.find(c => c.id === selectedCategory);
              if (!cat) return null;
              return (
                <TouchableOpacity onPress={() => setSelectedCategory(null)}
                  className="flex-row items-center"
                  style={{ gap: 4, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: '#F1F5F9' }}>
                  <Text style={{ fontSize: 11 }}>{cat.emoji}</Text>
                  <Text className="text-[11px] font-bold" style={{ color: '#475569' }}>{cat.name}</Text>
                  <XMarkIcon size={11} color="#475569" />
                </TouchableOpacity>
              );
            })()}
            {selectedPriceRanges.map(p => {
              const pr = PRICE_RANGES.find(x => x.code === p);
              if (!pr) return null;
              return (
                <TouchableOpacity key={p} onPress={() => togglePriceRange(p)}
                  className="flex-row items-center"
                  style={{ gap: 4, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: '#F1F5F9' }}>
                  <Text className="text-[11px] font-bold" style={{ color: '#475569' }}>{pr.label} FCFA</Text>
                  <XMarkIcon size={11} color="#475569" />
                </TouchableOpacity>
              );
            })}
            {selectedCountries.map(c => {
              const co = COUNTRY_PILLS.find(x => x.code === c);
              if (!co) return null;
              return (
                <TouchableOpacity key={c} onPress={() => toggleCountry(c)}
                  className="flex-row items-center"
                  style={{ gap: 4, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: '#F1F5F9' }}>
                  <Text style={{ fontSize: 11 }}>{co.flag}</Text>
                  <Text className="text-[11px] font-bold" style={{ color: '#475569' }}>{co.label}</Text>
                  <XMarkIcon size={11} color="#475569" />
                </TouchableOpacity>
              );
            })}
            {sortBy !== 'popular' && (() => {
              const s = SORT_OPTIONS.find(x => x.code === sortBy);
              if (!s) return null;
              return (
                <TouchableOpacity onPress={() => setSortBy('popular')}
                  className="flex-row items-center"
                  style={{ gap: 4, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: '#F1F5F9' }}>
                  <Text style={{ fontSize: 11 }}>{s.icon}</Text>
                  <Text className="text-[11px] font-bold" style={{ color: '#475569' }}>{s.label}</Text>
                  <XMarkIcon size={11} color="#475569" />
                </TouchableOpacity>
              );
            })()}
            <TouchableOpacity onPress={clearAll}
              className="flex-row items-center"
              style={{ gap: 4, paddingHorizontal: 10, paddingVertical: 6 }}>
              <Text className="text-[11px] font-bold underline" style={{ color: '#44A08D' }}>Tout effacer</Text>
            </TouchableOpacity>
          </ScrollView>
        </View>
      )}

      {/* Catégories rapides (gardées) */}
      <View className="py-3 bg-white">
        <ScrollView horizontal showsHorizontalScrollIndicator={false} className="px-4" contentContainerStyle={{ gap: 10 }}>
          <TouchableOpacity className="items-center" onPress={clearAll}>
            <View className="w-12 h-12 rounded-2xl items-center justify-center mb-1 border" style={{ backgroundColor: '#0F172A', borderColor: '#0F172A' }}>
              <Text className="text-[10px] font-black text-white">Tous</Text>
            </View>
            <Text className="text-[10px] font-bold text-center text-slate-900 w-14">Tous</Text>
          </TouchableOpacity>
          {CATEGORIES.map(category => (
            <TouchableOpacity
              key={category.id}
              onPress={() => router.push(`/category/${category.id}`)}
              className="items-center"
            >
              <View className="w-12 h-12 rounded-2xl items-center justify-center mb-1 border bg-white border-slate-200"
                    style={{ shadowColor: '#0F172A', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.04, shadowRadius: 3 }}>
                <Text className="text-xl">{category.emoji}</Text>
              </View>
              <Text className="text-[10px] font-medium text-center text-slate-600 w-14" numberOfLines={1}>{category.name}</Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      </View>

      {/* Results */}
      <ScrollView
        ref={scrollRef}
        className="flex-1 px-4"
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: 120 }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#44A08D']} tintColor="#44A08D" />}
      >
        {/* Counter + clear all */}
        {!isLoading && products.length > 0 && (
          <View className="flex-row items-center justify-between mb-3 mt-3">
            <Text className="text-[11px] uppercase font-black tracking-wider text-slate-400">
              {total} {total > 1 ? 'cartes' : 'carte'}
              {activeFiltersCount > 0 && ` · ${activeFiltersCount} filtre${activeFiltersCount > 1 ? 's' : ''}`}
            </Text>
            {activeFiltersCount > 0 && (
              <TouchableOpacity onPress={clearAll}>
                <Text className="text-[11px] font-bold text-[#44A08D] underline">Effacer</Text>
              </TouchableOpacity>
            )}
          </View>
        )}

        {isLoading && !refreshing ? (
          <View className="w-full items-center py-16">
            <LoadingKeychain />
          </View>
        ) : products.length === 0 ? (
          <EmptyState
            title="Aucun résultat trouvé"
            message="Essaie un autre filtre ou efface ta recherche."
            onRefresh={clearAll}
            buttonText="Effacer les filtres"
          />
        ) : viewMode === 'grid' ? (
          <View className="flex-row flex-wrap justify-between">
            {products.map((p, i) => (
              <ProductCard
                key={p.id}
                product={p}
                delay={(i % 12) * 35}
                variant="grid"
                onPress={() => router.push({
                  pathname: '/product/[id]',
                  params: {
                    id: String(p.id),
                    name: p.name,
                    brandName: p.cardType.name ?? '',
                    logoUrl: p.cardType.logoUrl ?? '',
                    currencyCode: p.native_currency ?? 'XAF',
                    minPrice: String(p.native_value),
                    maxPrice: String(p.native_value),
                    priceFcfa: String(p.price_fcfa),
                  },
                })}
              />
            ))}
          </View>
        ) : (
          <View>
            {products.map((p, i) => (
              <ProductCard
                key={p.id}
                product={p}
                delay={(i % 12) * 40}
                variant="list"
                onPress={() => router.push({
                  pathname: '/product/[id]',
                  params: {
                    id: String(p.id),
                    name: p.name,
                    brandName: p.cardType.name ?? '',
                    logoUrl: p.cardType.logoUrl ?? '',
                    currencyCode: p.native_currency ?? 'XAF',
                    minPrice: String(p.native_value),
                    maxPrice: String(p.native_value),
                    priceFcfa: String(p.price_fcfa),
                  },
                })}
              />
            ))}
          </View>
        )}

        {/* ========== Pagination (= boutique.blade.php : prev / 1…cur±1…last / next) ========== */}
        {!isLoading && products.length > 0 && lastPage > 1 && (
          <Pagination
            currentPage={currentPage}
            lastPage={lastPage}
            total={total}
            perPage={PER_PAGE}
            onChange={goToPage}
          />
        )}
      </ScrollView>

      {/* ========== FAB sticky : ouvrir le panneau filtres en bas ========== */}
      <TouchableOpacity
        activeOpacity={0.85}
        onPress={() => setFiltersOpen(true)}
        style={{
          position: 'absolute',
          bottom: 24,
          right: 16,
          flexDirection: 'row',
          alignItems: 'center',
          gap: 8,
          paddingHorizontal: 18,
          paddingVertical: 14,
          borderRadius: 999,
          backgroundColor: '#0F172A',
          shadowColor: '#0F172A',
          shadowOffset: { width: 0, height: 8 },
          shadowOpacity: 0.30,
          shadowRadius: 14,
          elevation: 8,
        }}
      >
        <AdjustmentsHorizontalIcon size={18} color="white" />
        <Text className="text-white font-black text-[13px]">Filtres</Text>
        {activeFiltersCount > 0 && (
          <View style={{
            minWidth: 20, height: 20, paddingHorizontal: 6, borderRadius: 999,
            backgroundColor: '#44A08D', alignItems: 'center', justifyContent: 'center',
          }}>
            <Text className="text-white text-[10px] font-black">{activeFiltersCount}</Text>
          </View>
        )}
      </TouchableOpacity>

      {/* ========== Bottom sheet : panneau filtres complet ========== */}
      <Modal visible={filtersOpen} transparent animationType="slide" onRequestClose={() => setFiltersOpen(false)}>
        <Pressable style={{ flex: 1, backgroundColor: 'rgba(15,23,42,0.55)' }} onPress={() => setFiltersOpen(false)}>
          <Pressable
            onPress={(e) => e.stopPropagation()}
            style={{
              marginTop: 'auto',
              backgroundColor: 'white',
              borderTopLeftRadius: 24,
              borderTopRightRadius: 24,
              maxHeight: '85%',
              paddingBottom: 32,
            }}
          >
            {/* Drag handle */}
            <View style={{ alignItems: 'center', paddingTop: 10, paddingBottom: 6 }}>
              <View style={{ width: 40, height: 5, borderRadius: 999, backgroundColor: '#CBD5E1' }} />
            </View>

            {/* Header */}
            <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, paddingTop: 6, paddingBottom: 14 }}>
              <Text className="font-black text-slate-900" style={{ fontSize: 18 }}>Filtres</Text>
              {activeFiltersCount > 0 && (
                <TouchableOpacity onPress={() => { clearAll(); }}>
                  <Text className="font-bold" style={{ color: '#44A08D', fontSize: 13 }}>Tout effacer</Text>
                </TouchableOpacity>
              )}
            </View>

            <ScrollView style={{ paddingHorizontal: 20 }} showsVerticalScrollIndicator={false}>

              {/* Top Afrique toggle */}
              <View style={{ marginBottom: 22 }}>
                <Text className="text-[10px] font-black uppercase tracking-wider mb-2" style={{ color: '#94A3B8', letterSpacing: 1.5 }}>
                  Mise en avant
                </Text>
                <TouchableOpacity
                  activeOpacity={0.85}
                  onPress={() => setPopularOnly(p => !p)}
                  style={{
                    flexDirection: 'row',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    paddingHorizontal: 16,
                    paddingVertical: 14,
                    borderRadius: 14,
                    borderWidth: 2,
                    backgroundColor: popularOnly ? '#FFF1F2' : 'white',
                    borderColor:     popularOnly ? '#F43F5E' : '#E2E8F0',
                  }}
                >
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
                    <Text style={{ fontSize: 22 }}>🔥</Text>
                    <View>
                      <Text className="font-black" style={{ fontSize: 14, color: '#0F172A' }}>Top Afrique uniquement</Text>
                      <Text className="text-[11px]" style={{ color: '#64748B' }}>Steam, PSN, Netflix, Spotify, Apple…</Text>
                    </View>
                  </View>
                  <View style={{
                    width: 44, height: 24, borderRadius: 999,
                    backgroundColor: popularOnly ? '#F43F5E' : '#E2E8F0',
                    padding: 2, justifyContent: 'center',
                  }}>
                    <View style={{
                      width: 20, height: 20, borderRadius: 999, backgroundColor: 'white',
                      transform: [{ translateX: popularOnly ? 20 : 0 }],
                    }} />
                  </View>
                </TouchableOpacity>
              </View>

              {/* Régions */}
              <View style={{ marginBottom: 22 }}>
                <Text className="text-[10px] font-black uppercase tracking-wider mb-2" style={{ color: '#94A3B8', letterSpacing: 1.5 }}>
                  Région · {selectedRegions.length} active{selectedRegions.length > 1 ? 's' : ''}
                </Text>
                <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
                  {REGION_PILLS.map(r => {
                    const active = selectedRegions.includes(r.code);
                    return (
                      <TouchableOpacity
                        key={r.code}
                        activeOpacity={0.85}
                        onPress={() => toggleRegion(r.code)}
                        style={{
                          flexDirection: 'row', alignItems: 'center', gap: 8,
                          paddingHorizontal: 16, paddingVertical: 12,
                          borderRadius: 12, borderWidth: 2,
                          backgroundColor: active ? '#0F172A' : 'white',
                          borderColor:     active ? '#0F172A' : '#E2E8F0',
                        }}
                      >
                        <Text style={{ fontSize: 16 }}>{r.emoji}</Text>
                        <Text className="font-black" style={{ fontSize: 13, color: active ? 'white' : '#475569' }}>{r.label}</Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </View>

              {/* Marques populaires */}
              <View style={{ marginBottom: 22 }}>
                <Text className="text-[10px] font-black uppercase tracking-wider mb-2" style={{ color: '#94A3B8', letterSpacing: 1.5 }}>
                  Marques populaires · clic = filtre direct
                </Text>
                <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
                  {BRAND_PILLS.map(b => {
                    const active = searchTerm.toLowerCase() === b.search.toLowerCase();
                    return (
                      <TouchableOpacity
                        key={b.search}
                        activeOpacity={0.85}
                        onPress={() => setBrand(b.search)}
                        style={{
                          flexDirection: 'row', alignItems: 'center', gap: 8,
                          paddingLeft: 6, paddingRight: 14, paddingVertical: 6,
                          borderRadius: 999, borderWidth: 2,
                          backgroundColor: active ? '#0F172A' : 'white',
                          borderColor:     active ? '#0F172A' : '#E2E8F0',
                        }}
                      >
                        <View style={{ width: 28, height: 28, borderRadius: 999, alignItems: 'center', justifyContent: 'center', backgroundColor: b.color }}>
                          <Text className="text-[12px] font-black text-white">{b.label.charAt(0)}</Text>
                        </View>
                        <Text className="font-black" style={{ fontSize: 13, color: active ? 'white' : '#475569' }}>{b.label}</Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </View>

              {/* ---- Catégories ---- */}
              <View style={{ marginBottom: 22 }}>
                <Text className="text-[10px] font-black uppercase tracking-wider mb-2" style={{ color: '#94A3B8', letterSpacing: 1.5 }}>
                  Catégorie {selectedCategory !== null && '· 1 active'}
                </Text>
                <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
                  <TouchableOpacity
                    activeOpacity={0.85}
                    onPress={() => setSelectedCategory(null)}
                    style={{
                      flexDirection: 'row', alignItems: 'center', gap: 6,
                      paddingHorizontal: 14, paddingVertical: 10,
                      borderRadius: 12, borderWidth: 2,
                      backgroundColor: selectedCategory === null ? '#0F172A' : 'white',
                      borderColor:     selectedCategory === null ? '#0F172A' : '#E2E8F0',
                    }}
                  >
                    <Text style={{ fontSize: 14 }}>📦</Text>
                    <Text className="font-black" style={{ fontSize: 13, color: selectedCategory === null ? 'white' : '#475569' }}>Toutes</Text>
                  </TouchableOpacity>
                  {CATEGORIES.map(cat => {
                    const active = selectedCategory === cat.id;
                    return (
                      <TouchableOpacity
                        key={cat.id}
                        activeOpacity={0.85}
                        onPress={() => setSelectedCategory(active ? null : cat.id)}
                        style={{
                          flexDirection: 'row', alignItems: 'center', gap: 6,
                          paddingHorizontal: 14, paddingVertical: 10,
                          borderRadius: 12, borderWidth: 2,
                          backgroundColor: active ? '#0F172A' : 'white',
                          borderColor:     active ? '#0F172A' : '#E2E8F0',
                        }}
                      >
                        <Text style={{ fontSize: 14 }}>{cat.emoji}</Text>
                        <Text className="font-black" style={{ fontSize: 13, color: active ? 'white' : '#475569' }}>{cat.name}</Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </View>

              {/* ---- Plages de prix ---- */}
              <View style={{ marginBottom: 22 }}>
                <Text className="text-[10px] font-black uppercase tracking-wider mb-2" style={{ color: '#94A3B8', letterSpacing: 1.5 }}>
                  Prix · {selectedPriceRanges.length} active{selectedPriceRanges.length > 1 ? 's' : ''}
                </Text>
                <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
                  {PRICE_RANGES.map(p => {
                    const active = selectedPriceRanges.includes(p.code);
                    return (
                      <TouchableOpacity
                        key={p.code}
                        activeOpacity={0.85}
                        onPress={() => togglePriceRange(p.code)}
                        style={{
                          flex: 1, minWidth: '45%',
                          alignItems: 'center',
                          paddingVertical: 12,
                          borderRadius: 12, borderWidth: 2,
                          backgroundColor: active ? '#0F172A' : 'white',
                          borderColor:     active ? '#0F172A' : '#E2E8F0',
                        }}
                      >
                        <Text className="font-black" style={{ fontSize: 14, color: active ? 'white' : '#0F172A' }}>{p.label}</Text>
                        <Text className="font-bold mt-0.5" style={{ fontSize: 10, opacity: 0.65, color: active ? 'white' : '#94A3B8' }}>{p.sub}</Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </View>

              {/* ---- Pays disponibles ---- */}
              <View style={{ marginBottom: 22 }}>
                <Text className="text-[10px] font-black uppercase tracking-wider mb-2" style={{ color: '#94A3B8', letterSpacing: 1.5 }}>
                  Pays disponibles · {selectedCountries.length} sélectionné{selectedCountries.length > 1 ? 's' : ''}
                </Text>
                <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
                  {COUNTRY_PILLS.map(c => {
                    const active = selectedCountries.includes(c.code);
                    return (
                      <TouchableOpacity
                        key={c.code}
                        activeOpacity={0.85}
                        onPress={() => toggleCountry(c.code)}
                        style={{
                          flexDirection: 'row', alignItems: 'center', gap: 8,
                          paddingHorizontal: 14, paddingVertical: 10,
                          borderRadius: 12, borderWidth: 2,
                          backgroundColor: active ? '#0F172A' : 'white',
                          borderColor:     active ? '#0F172A' : '#E2E8F0',
                        }}
                      >
                        <Text style={{ fontSize: 16 }}>{c.flag}</Text>
                        <Text className="font-black" style={{ fontSize: 13, color: active ? 'white' : '#475569' }}>{c.label}</Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </View>

              {/* ---- Tri ---- */}
              <View style={{ marginBottom: 14 }}>
                <Text className="text-[10px] font-black uppercase tracking-wider mb-2" style={{ color: '#94A3B8', letterSpacing: 1.5 }}>
                  Trier par
                </Text>
                <View style={{ flexDirection: 'column', gap: 6 }}>
                  {SORT_OPTIONS.map(s => {
                    const active = sortBy === s.code;
                    return (
                      <TouchableOpacity
                        key={s.code}
                        activeOpacity={0.85}
                        onPress={() => setSortBy(s.code)}
                        style={{
                          flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
                          paddingHorizontal: 14, paddingVertical: 12,
                          borderRadius: 12, borderWidth: 2,
                          backgroundColor: active ? '#F0FDFA' : 'white',
                          borderColor:     active ? '#44A08D' : '#E2E8F0',
                        }}
                      >
                        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
                          <Text style={{ fontSize: 16 }}>{s.icon}</Text>
                          <Text className="font-black" style={{ fontSize: 13, color: active ? '#0F4F44' : '#475569' }}>{s.label}</Text>
                        </View>
                        <View style={{
                          width: 18, height: 18, borderRadius: 999, borderWidth: 2,
                          borderColor: active ? '#44A08D' : '#CBD5E1',
                          alignItems: 'center', justifyContent: 'center',
                          backgroundColor: active ? '#44A08D' : 'white',
                        }}>
                          {active && <View style={{ width: 6, height: 6, borderRadius: 999, backgroundColor: 'white' }} />}
                        </View>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </View>
            </ScrollView>

            {/* Bouton fermer / appliquer */}
            <View style={{ paddingHorizontal: 20, paddingTop: 14, borderTopWidth: 1, borderTopColor: '#F1F5F9', marginTop: 6 }}>
              <TouchableOpacity
                activeOpacity={0.85}
                onPress={() => setFiltersOpen(false)}
                style={{
                  paddingVertical: 14,
                  borderRadius: 14,
                  backgroundColor: '#44A08D',
                  alignItems: 'center',
                  shadowColor: '#44A08D',
                  shadowOffset: { width: 0, height: 6 },
                  shadowOpacity: 0.30,
                  shadowRadius: 12,
                  elevation: 4,
                }}
              >
                <Text className="text-white font-black" style={{ fontSize: 14 }}>
                  Voir {total} produit{total > 1 ? 's' : ''}
                </Text>
              </TouchableOpacity>
            </View>
          </Pressable>
        </Pressable>
      </Modal>
    </SafeAreaView>
  );
}
