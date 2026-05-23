import { View, Text, TextInput, TouchableOpacity, ScrollView, Image, RefreshControl, Animated, Easing } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect, useMemo, useRef } from 'react';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MagnifyingGlassIcon, XMarkIcon, ArrowLeftIcon, ArrowRightIcon, ChevronRightIcon } from 'react-native-heroicons/outline';
import { CATEGORIES } from '../data/mock';
import { CatalogService, CatalogProduct } from '../services/catalog';
import LoadingKeychain from '../components/LoadingKeychain';

// Mêmes suggestions que le web search modal
const POPULAR_SUGGESTIONS = [
  { label: 'Netflix',     color: '#E50914' },
  { label: 'Spotify',     color: '#1DB954' },
  { label: 'Apple',       color: '#000000' },
  { label: 'PlayStation', color: '#003791' },
  { label: 'Amazon',      color: '#FF9900' },
  { label: 'Steam',       color: '#171A21' },
];

const BRAND_PALETTE: Record<string, string> = {
  Netflix: '#E50914', Spotify: '#1DB954', Apple: '#000000',
  iTunes: '#D60017', PlayStation: '#003791', Xbox: '#107C10',
  Amazon: '#FF9900', Google: '#01875F', Steam: '#171A21',
  Roblox: '#00A2FF', Nintendo: '#E60012', Disney: '#0E47A1',
  Riot: '#D13639', Epic: '#2A2A2A',
};

function getBrandColor(name: string): string {
  if (!name) return '#1F2937';
  for (const [key, color] of Object.entries(BRAND_PALETTE)) {
    if (name.toLowerCase().includes(key.toLowerCase())) return color;
  }
  const palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  const idx = ((hash % palette.length) + palette.length) % palette.length;
  return palette[idx];
}

function formatFcfa(n: number): string {
  return new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FCFA';
}

export default function SearchScreen() {
  const router = useRouter();
  const [searchQuery, setSearchQuery]   = useState('');
  const [products, setProducts]         = useState<CatalogProduct[]>([]);
  const [isLoading, setIsLoading]       = useState(false);
  const [refreshing, setRefreshing]     = useState(false);

  const hasQuery = searchQuery.trim().length > 0;

  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Recherche server-side dès qu'il y a 2 caractères ou plus
  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    if (!hasQuery) {
      setProducts([]);
      setIsLoading(false);
      return;
    }
    debounceRef.current = setTimeout(async () => {
      try {
        setIsLoading(true);
        const response = await CatalogService.getProducts({ search: searchQuery.trim(), per_page: 30 });
        setProducts(response.data ?? []);
      } catch (e) {
        console.warn('Search failed:', e);
        setProducts([]);
      } finally {
        setIsLoading(false);
        setRefreshing(false);
      }
    }, 250);
    return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
  }, [searchQuery]);

  const onRefresh = () => {
    setRefreshing(true);
    if (hasQuery) setSearchQuery(searchQuery + ' '); // trigger re-fetch
    else setRefreshing(false);
  };

  const goToBoutique = () => {
    router.push({ pathname: '/(tabs)/boutique' as any, params: searchQuery.trim() ? { search: searchQuery.trim() } : {} });
  };

  return (
    <SafeAreaView className="flex-1" style={{ backgroundColor: '#F8FAFC' }}>
      {/* ============= Header avec search ============= */}
      <View className="bg-white border-b border-slate-100" style={{ paddingHorizontal: 16, paddingTop: 8, paddingBottom: 14 }}>
        <View className="flex-row items-center" style={{ gap: 10 }}>
          <TouchableOpacity onPress={() => router.back()} style={{ padding: 4 }}>
            <ArrowLeftIcon size={22} color="#0F172A" />
          </TouchableOpacity>

          <View className="flex-1 flex-row items-center" style={{
            backgroundColor: '#F1F5F9',
            borderRadius: 14,
            paddingHorizontal: 14,
            paddingVertical: 11,
            borderWidth: 1.5,
            borderColor: hasQuery ? '#44A08D' : 'transparent',
          }}>
            <MagnifyingGlassIcon size={18} color="#94A3B8" />
            <TextInput
              autoFocus
              value={searchQuery}
              onChangeText={setSearchQuery}
              placeholder="Rechercher Netflix, Apple, Spotify…"
              placeholderTextColor="#94A3B8"
              returnKeyType="search"
              onSubmitEditing={() => hasQuery && goToBoutique()}
              className="flex-1 ml-3 text-base"
              style={{ color: '#0F172A', padding: 0 }}
            />
            {hasQuery && (
              <TouchableOpacity onPress={() => setSearchQuery('')}>
                <XMarkIcon size={18} color="#94A3B8" />
              </TouchableOpacity>
            )}
          </View>
        </View>
      </View>

      <ScrollView
        className="flex-1"
        contentContainerStyle={{ paddingBottom: 30 }}
        keyboardShouldPersistTaps="handled"
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#44A08D']} tintColor="#44A08D" />}
      >

        {/* ===== ÉTAT VIDE : suggestions populaires + catégories (= web search modal) ===== */}
        {!hasQuery && (
          <>
            {/* Suggestions populaires */}
            <View style={{ paddingHorizontal: 16, paddingTop: 18 }}>
              <Text style={{ fontSize: 10, fontWeight: '900', color: '#94A3B8', letterSpacing: 1.5, marginBottom: 10 }}>
                SUGGESTIONS POPULAIRES
              </Text>
              <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
                {POPULAR_SUGGESTIONS.map(s => (
                  <TouchableOpacity
                    key={s.label}
                    activeOpacity={0.85}
                    onPress={() => setSearchQuery(s.label)}
                    style={{
                      flex: 1, minWidth: '30%',
                      backgroundColor: 'white',
                      borderRadius: 12,
                      borderWidth: 1,
                      borderColor: '#E2E8F0',
                      paddingVertical: 12,
                      paddingHorizontal: 12,
                      flexDirection: 'row',
                      alignItems: 'center',
                      gap: 8,
                      shadowColor: '#0F172A',
                      shadowOffset: { width: 0, height: 1 },
                      shadowOpacity: 0.04,
                      shadowRadius: 3,
                      elevation: 1,
                    }}
                  >
                    <ArrowRightIcon size={14} color="#94A3B8" />
                    <View style={{ width: 6, height: 6, borderRadius: 3, backgroundColor: s.color }} />
                    <Text style={{ fontSize: 13, fontWeight: '700', color: '#0F172A', flex: 1 }} numberOfLines={1}>
                      {s.label}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>

            {/* Catégories */}
            <View style={{ paddingHorizontal: 16, paddingTop: 22 }}>
              <Text style={{ fontSize: 10, fontWeight: '900', color: '#94A3B8', letterSpacing: 1.5, marginBottom: 10 }}>
                CATÉGORIES
              </Text>
              <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
                {CATEGORIES.map(c => (
                  <TouchableOpacity
                    key={c.id}
                    activeOpacity={0.85}
                    onPress={() => router.push(`/category/${c.id}` as any)}
                    style={{
                      flexDirection: 'row',
                      alignItems: 'center',
                      gap: 8,
                      paddingHorizontal: 14,
                      paddingVertical: 10,
                      borderRadius: 999,
                      backgroundColor: 'white',
                      borderWidth: 1,
                      borderColor: '#E2E8F0',
                    }}
                  >
                    <Text style={{ fontSize: 16 }}>{c.emoji}</Text>
                    <Text style={{ fontSize: 13, fontWeight: '700', color: '#475569' }}>{c.name}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>

            {/* Footer CTA */}
            <View style={{ paddingHorizontal: 16, paddingTop: 28, paddingBottom: 8 }}>
              <TouchableOpacity
                activeOpacity={0.85}
                onPress={goToBoutique}
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: 6,
                  paddingVertical: 14,
                  borderRadius: 14,
                  backgroundColor: '#44A08D',
                  shadowColor: '#44A08D',
                  shadowOffset: { width: 0, height: 6 },
                  shadowOpacity: 0.30,
                  shadowRadius: 12,
                  elevation: 4,
                }}
              >
                <Text style={{ color: 'white', fontWeight: '900', fontSize: 14 }}>Voir toute la boutique</Text>
                <ArrowRightIcon size={16} color="white" />
              </TouchableOpacity>
            </View>
          </>
        )}

        {/* ===== ÉTAT RECHERCHE : résultats ===== */}
        {hasQuery && (
          <View style={{ paddingHorizontal: 16, paddingTop: 14 }}>
            {isLoading ? (
              <View style={{ alignItems: 'center', paddingVertical: 60 }}>
                <LoadingKeychain />
              </View>
            ) : products.length === 0 ? (
              <View style={{ alignItems: 'center', paddingVertical: 60 }}>
                <View style={{ width: 64, height: 64, borderRadius: 32, backgroundColor: '#F1F5F9', alignItems: 'center', justifyContent: 'center', marginBottom: 12 }}>
                  <MagnifyingGlassIcon size={28} color="#94A3B8" />
                </View>
                <Text style={{ fontSize: 16, fontWeight: '900', color: '#0F172A' }}>Aucun résultat</Text>
                <Text style={{ fontSize: 13, color: '#64748B', marginTop: 6, textAlign: 'center', paddingHorizontal: 32 }}>
                  Aucune carte ne correspond à « {searchQuery} ». Essaie une autre marque ou explore la boutique.
                </Text>
                <TouchableOpacity
                  onPress={goToBoutique}
                  style={{ marginTop: 14, paddingHorizontal: 18, paddingVertical: 10, borderRadius: 10, backgroundColor: '#0F172A' }}
                >
                  <Text style={{ color: 'white', fontSize: 13, fontWeight: '800' }}>Voir toute la boutique</Text>
                </TouchableOpacity>
              </View>
            ) : (
              <>
                <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 }}>
                  <Text style={{ fontSize: 11, fontWeight: '900', color: '#94A3B8', letterSpacing: 1, textTransform: 'uppercase' }}>
                    {products.length} résultat{products.length > 1 ? 's' : ''}
                  </Text>
                  <TouchableOpacity onPress={goToBoutique}>
                    <Text style={{ fontSize: 12, fontWeight: '700', color: '#44A08D' }}>Voir tout →</Text>
                  </TouchableOpacity>
                </View>
                <View style={{ flexDirection: 'column', gap: 8 }}>
                  {products.map(p => {
                    const fullName   = p.cardType.name ?? p.name;
                    const brandColor = getBrandColor(fullName);
                    const logoUrl    = p.cardType.logoUrl;
                    const isPopular  = p.cardType.popular_in_africa;
                    return (
                      <TouchableOpacity
                        key={p.id}
                        activeOpacity={0.85}
                        onPress={() => router.push({
                          pathname: '/product/[id]' as any,
                          params: {
                            id: String(p.id),
                            name: p.name,
                            brandName: fullName,
                            logoUrl: logoUrl ?? '',
                            currencyCode: p.native_currency ?? 'XAF',
                            minPrice: String(p.native_value),
                            maxPrice: String(p.native_value),
                            priceFcfa: String(p.price_fcfa),
                          },
                        })}
                        style={{
                          flexDirection: 'row',
                          alignItems: 'center',
                          gap: 12,
                          backgroundColor: 'white',
                          borderRadius: 14,
                          padding: 10,
                          borderWidth: 1,
                          borderColor: '#E2E8F0',
                        }}
                      >
                        <View style={{
                          width: 48, height: 48, borderRadius: 11, backgroundColor: brandColor,
                          alignItems: 'center', justifyContent: 'center', overflow: 'hidden',
                          padding: 6,
                        }}>
                          {logoUrl ? (
                            <View style={{ width: '100%', height: '100%', backgroundColor: 'white', borderRadius: 6, padding: 2, alignItems: 'center', justifyContent: 'center' }}>
                              <Image source={{ uri: logoUrl }} style={{ width: '100%', height: '100%' }} resizeMode="contain" />
                            </View>
                          ) : (
                            <Text style={{ color: 'white', fontSize: 18, fontWeight: '900' }}>{(fullName.charAt(0) || '?').toUpperCase()}</Text>
                          )}
                        </View>
                        <View style={{ flex: 1, minWidth: 0 }}>
                          <Text numberOfLines={1} style={{ fontSize: 14, fontWeight: '800', color: '#0F172A' }}>
                            {fullName}
                          </Text>
                          <Text numberOfLines={1} style={{ fontSize: 11, color: '#64748B', marginTop: 1 }}>
                            {p.name}
                          </Text>
                          {isPopular && (
                            <View style={{ alignSelf: 'flex-start', marginTop: 4, paddingHorizontal: 5, paddingVertical: 1, borderRadius: 4, backgroundColor: '#FEF3C7' }}>
                              <Text style={{ fontSize: 8, fontWeight: '900', color: '#B45309', letterSpacing: 0.6 }}>POPULAIRE</Text>
                            </View>
                          )}
                        </View>
                        <View style={{ alignItems: 'flex-end' }}>
                          <Text style={{ fontSize: 14, fontWeight: '900', color: '#0F172A' }}>{formatFcfa(p.price_fcfa)}</Text>
                          <ChevronRightIcon size={14} color="#94A3B8" style={{ marginTop: 2 }} />
                        </View>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </>
            )}
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}
