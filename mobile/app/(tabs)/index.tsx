import { View, Text, ScrollView, Image, TouchableOpacity, Dimensions, StatusBar, RefreshControl, Animated, Easing } from 'react-native';
import { useRouter } from 'expo-router';
import { MagnifyingGlassIcon, BellIcon } from 'react-native-heroicons/outline';
import { CATEGORIES } from '../../data/mock';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useState, useRef, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { CatalogService, PopularCardType } from '../../services/catalog';
import EmptyState from '../../components/EmptyState';
import LoadingKeychain from '../../components/LoadingKeychain';
import NavbarProfile from '../../components/NavbarProfile';
import { getBrandColor } from '../../utils/brandColor';
import { GiftCardVisual } from '../../components/GiftCardVisual';

const { width } = Dimensions.get('window');

function formatFcfaShort(n: number): string {
  return new Intl.NumberFormat('fr-FR').format(Math.round(n));
}

/**
 * Marquee hero — réplique le `$heroCards = take(3)` de welcome.blade.php :
 * on prend les premières cardTypes de l'API (mêmes que le web mobile hero
 * stack) et on les fait défiler en boucle horizontale.
 *
 * En mobile web : 3 cartes empilées en 3D. Ici, on conserve l'animation
 * marquee mais avec les VRAIES données API (plus de hardcoded Netflix/Steam).
 */
function HeroMarquee({ cards }: { cards: PopularCardType[] }) {
  const translateX = useRef(new Animated.Value(0)).current;
  const cardWidth = 200;
  const gap = 14;
  const totalWidth = cards.length * (cardWidth + gap);

  useEffect(() => {
    if (cards.length === 0) return;
    translateX.setValue(0);
    const loop = Animated.loop(
      Animated.timing(translateX, {
        toValue: -totalWidth,
        duration: Math.max(cards.length, 4) * 3500,  // ~3.5s par carte
        easing: Easing.linear,
        useNativeDriver: true,
      })
    );
    loop.start();
    return () => loop.stop();
  }, [cards.length, totalWidth]);

  if (cards.length === 0) {
    return <View style={{ height: 160 }} />;
  }

  // Doublé pour le seamless loop
  const items = [...cards, ...cards];

  return (
    <View style={{ height: 160, overflow: 'hidden' }}>
      <Animated.View style={{ flexDirection: 'row', gap, paddingHorizontal: 16, transform: [{ translateX }] }}>
        {items.map((card, i) => {
          const fullName   = card.name ?? 'Carte cadeau';
          const shortBrand = fullName.split(/\s+/)[0] || fullName;
          const brandColor = getBrandColor(fullName);
          const priceFcfa  = card.min_price_fcfa ?? 0;
          return (
            <View
              key={`${card.id}-${i}`}
              style={{
                width: cardWidth,
                height: 130,
                borderRadius: 18,
                padding: 16,
                backgroundColor: brandColor,
                shadowColor: brandColor,
                shadowOffset: { width: 0, height: 12 },
                shadowOpacity: 0.45,
                shadowRadius: 20,
                elevation: 6,
                overflow: 'hidden',
              }}
            >
              {/* Halo blanc en haut-droite (= hero web) */}
              <View style={{ position: 'absolute', top: -25, right: -25, width: 90, height: 90, borderRadius: 45, backgroundColor: 'rgba(255,255,255,0.18)' }} />
              {/* Pattern subtil */}
              <View style={{ position: 'absolute', bottom: -15, left: -15, width: 60, height: 60, borderRadius: 30, backgroundColor: 'rgba(255,255,255,0.10)' }} />

              <View style={{ position: 'relative', flex: 1, justifyContent: 'space-between' }}>
                <View>
                  <Text style={{ color: 'rgba(255,255,255,0.85)', fontSize: 9, fontWeight: '800', letterSpacing: 1.6, textTransform: 'uppercase' }}>
                    Gift Card
                  </Text>
                  <Text style={{ color: '#FFFFFF', fontSize: 18, fontWeight: '900', letterSpacing: -0.3, marginTop: 2 }} numberOfLines={1}>
                    {shortBrand}
                  </Text>
                </View>
                <View style={{ flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between' }}>
                  <View>
                    <Text style={{ color: 'rgba(255,255,255,0.65)', fontSize: 9, fontWeight: '700', letterSpacing: 1, textTransform: 'uppercase' }}>À partir de</Text>
                    <Text style={{ color: '#FFFFFF', fontSize: 16, fontWeight: '900', fontVariant: ['tabular-nums'], marginTop: 2 }}>
                      {formatFcfaShort(priceFcfa)} <Text style={{ fontSize: 10, fontWeight: '500', color: 'rgba(255,255,255,0.7)' }}>FCFA</Text>
                    </Text>
                  </View>
                  {/* Puce gold */}
                  <View style={{ width: 28, height: 20, borderRadius: 4, backgroundColor: '#FCD34D', borderWidth: 1, borderColor: 'rgba(255,255,255,0.3)' }} />
                </View>
              </View>
            </View>
          );
        })}
      </Animated.View>
    </View>
  );
}

export default function HomeScreen() {
  const router = useRouter();

  // Catalog State — on stocke des PopularCardType (= cardTypes groupés par
  // brand+region, avec min_price_fcfa). Identique au getCardTypes() web qui
  // alimente la welcome page : Apple France, Spotify France, Playstation France,
  // Apple Belgium… triés par popularité Afrique puis par pays francophone.
  const [products, setProducts] = useState<PopularCardType[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const onRefresh = () => {
    setRefreshing(true);
    loadProducts().finally(() => setRefreshing(false));
  };

  useEffect(() => {
    loadProducts();
  }, []);

  const loadProducts = async () => {
    // 1) Affichage INSTANTANÉ depuis le cache local (stale-while-revalidate),
    //    équivalent du snapshot côté web : l'accueil s'affiche sans attendre le réseau.
    try {
      const cached = await AsyncStorage.getItem('cache_popular_v1');
      if (cached) {
        const arr = JSON.parse(cached);
        if (Array.isArray(arr) && arr.length) {
          setProducts(arr);
          setIsLoading(false);
        }
      }
    } catch { /* pas de cache, on continue */ }

    // 2) Rafraîchissement en arrière-plan + mise à jour du cache.
    try {
      const popular = await CatalogService.getPopular(12);
      if (Array.isArray(popular) && popular.length) {
        setProducts(popular);
        AsyncStorage.setItem('cache_popular_v1', JSON.stringify(popular)).catch(() => {});
      }
    } catch (error) {
      console.error('Failed to load popular cards:', error);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      <StatusBar barStyle="light-content" backgroundColor="#1F2937" />
      
      {/* Dark AppBar with Logo & Search */}
      <View className="bg-[#1F2937] px-4 pt-2 pb-6 shadow-lg z-50">
        <View className="flex-row justify-between items-center mb-4">
          <View className="flex-row items-center">
            <View className="w-10 h-10 mr-2 rounded-xl bg-[#44A08D] items-center justify-center" style={{ padding: 5 }}>
              <Image
                source={require('../assets/FAVCON-KARDAFRICA-.png')}
                className="w-full h-full"
                resizeMode="contain"
              />
            </View>
            <Text className="text-xl font-bold text-white tracking-tight">KardAfrica</Text>
          </View>
          
          <View className="flex-row items-center">
             <TouchableOpacity 
               className="relative"
               onPress={() => router.push('/notifications')}
             >
                <BellIcon size={24} color="white" />
                <View className="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full border border-[#1F2937]" />
             </TouchableOpacity>
             <View className="ml-5">
               <NavbarProfile />
             </View>
          </View>
        </View>

        {/* Integrated Search Bar */}
        <TouchableOpacity 
          className="flex-row items-center bg-gray-800/80 rounded-xl px-4 py-2.5 border border-gray-700"
          onPress={() => router.push('/search')}
        >
          <MagnifyingGlassIcon size={18} color="#9CA3AF" />
          <Text className="flex-1 ml-3 text-sm text-[#6B7280]">Search brands...</Text>
        </TouchableOpacity>
      </View>

      <ScrollView 
        className="flex-1" 
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#1F2937']} tintColor="#1F2937" />
        }
      >
        
        {/* Hero — Marquee anim\xE9 de cartes cadeaux */}
        <View className="pt-5 pb-6">
          <View className="px-4 mb-4">
            <View className="flex-row items-center gap-2 mb-1">
              <View style={{ width: 6, height: 6, borderRadius: 3, backgroundColor: '#44A08D' }} />
              <Text style={{ letterSpacing: 1.5 }} className="text-[#44A08D] text-[10px] font-black uppercase">Top du moment</Text>
            </View>
            <Text className="text-2xl font-black text-slate-900 tracking-tight">Vos marques préférées</Text>
            <Text className="text-xs text-slate-500 mt-1">Livrées en moins de 30 secondes</Text>
          </View>
          <HeroMarquee cards={products.slice(0, 8)} />
        </View>

        {/* Categories */}
        <View className="py-2">
          <ScrollView horizontal showsHorizontalScrollIndicator={false} className="px-4">
            {CATEGORIES.map((category) => (
              <TouchableOpacity 
                key={category.id} 
                className="mr-3 items-center"
                onPress={() => router.push(`/category/${category.id}`)}
              >
                <View className="w-14 h-14 bg-white rounded-full items-center justify-center shadow-sm mb-1 border border-gray-100">
                  <Text className="text-2xl">{category.emoji}</Text>
                </View>
                <Text className="text-[10px] font-medium text-center text-gray-600 w-14" numberOfLines={1}>{category.name}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        </View>

        {/* Top Shopping Brands */}
        <View className="mt-4 px-4">
          <View className="flex-row justify-between items-center mb-3">
            <Text className="text-xl font-bold text-gray-900">Populaires</Text>
            <TouchableOpacity onPress={() => router.push('/search')}>
              <Text className="text-[#44A08D] font-semibold">Voir tout</Text>
            </TouchableOpacity>
          </View>

          <View className="flex-row flex-wrap justify-between">
            {isLoading && !refreshing ? (
              <View className="w-full items-center py-10">
                <LoadingKeychain />
              </View>
            ) : products.length === 0 ? (
              <EmptyState 
                title="Aucun produit disponible"
                message="Impossible de charger les produits pour le moment. Veuillez vérifier votre connexion."
                onRefresh={onRefresh}
                buttonText="Réessayer"
              />
            ) : (
              products.map((cardType) => {
                // PopularCardType shape : un cardType groupé (Apple France, Spotify France...)
                const fullName   = cardType.name ?? 'Carte';
                const shortBrand = fullName.split(/\s+/)[0] || fullName;
                const brandColor = getBrandColor(fullName);
                const logoUrl    = cardType.logoUrl ?? null;
                const priceFcfa  = cardType.min_price_fcfa ?? 0;
                const isPopular  = cardType.popular_in_africa;
                const initial    = fullName.charAt(0).toUpperCase();
                const product    = cardType; // alias pour key

                return (
                  <TouchableOpacity
                    key={product.id}
                    activeOpacity={0.85}
                    style={{
                      width: '48%',
                      backgroundColor: 'white',
                      borderRadius: 16,
                      marginBottom: 12,
                      overflow: 'hidden',
                      borderWidth: 1,
                      borderColor: '#F1F5F9',
                      shadowColor: '#0F172A',
                      shadowOffset: { width: 0, height: 4 },
                      shadowOpacity: 0.06,
                      shadowRadius: 10,
                      elevation: 2,
                    }}
                    onPress={() => router.push({
                      pathname: '/product/[id]',
                      params: {
                        id: String(cardType.id),
                        name: fullName,
                        logoUrl: logoUrl ?? '',
                        brandName: fullName,
                        currencyCode: 'XAF',
                        minPrice: String(priceFcfa),
                        maxPrice: String(priceFcfa),
                        priceFcfa: String(priceFcfa),
                      }
                    })}
                  >
                    {/* ===== VISUEL HYBRIDE (= proposition design) ===== */}
                    <GiftCardVisual
                      brandLabel={fullName}
                      brandColor={brandColor}
                      countryCode={cardType.countryCode}
                      logoUrl={logoUrl}
                      gradId={`home-${cardType.id}`}
                    />

                    {/* ===== BOTTOM : nom + montants + prix ===== */}
                    <View style={{ paddingHorizontal: 14, paddingTop: 12, paddingBottom: 12 }}>
                      {/* Nom + badge */}
                      <View style={{ flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 6 }}>
                        <Text numberOfLines={2} style={{ flex: 1, fontSize: 12, fontWeight: '700', color: '#0F172A', lineHeight: 16 }}>
                          {fullName}
                        </Text>
                        {isPopular && (
                          <View style={{ flexShrink: 0, paddingHorizontal: 5, paddingVertical: 2, borderRadius: 4, borderWidth: 1, borderColor: '#FDE68A', backgroundColor: '#FEF3C7' }}>
                            <Text style={{ fontSize: 8, fontWeight: '900', color: '#B45309', letterSpacing: 0.6 }}>POPULAIRE</Text>
                          </View>
                        )}
                      </View>

                      {/* X montants disponibles */}
                      {cardType.products_count > 0 && (
                        <Text style={{ fontSize: 10, color: '#94A3B8', marginTop: 2, fontWeight: '600' }}>
                          {cardType.products_count} montant{cardType.products_count > 1 ? 's' : ''} disponible{cardType.products_count > 1 ? 's' : ''}
                        </Text>
                      )}

                      {/* Divider + Prix */}
                      <View style={{ marginTop: 10, paddingTop: 10, borderTopWidth: 1, borderTopColor: '#F1F5F9', flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between' }}>
                        <Text style={{ fontSize: 10, color: '#94A3B8', fontWeight: '500' }}>Dès</Text>
                        <Text numberOfLines={1} style={{ fontSize: 14, fontWeight: '900', color: '#0F172A' }}>
                          {new Intl.NumberFormat('fr-FR').format(Math.round(priceFcfa))} FCFA
                        </Text>
                      </View>
                    </View>
                  </TouchableOpacity>
                );
              })
            )}
          </View>
        </View>

        {/* Banner Promo */}
        <View className="px-4 mt-4 mb-8">
          <View className="bg-[#1F2937] rounded-3xl p-6 relative overflow-hidden shadow-xl shadow-gray-900/20">
            {/* Background Decor */}
            <View className="absolute -top-10 -right-10 w-40 h-40 bg-[#44A08D]/20 rounded-full blur-2xl" />
            <View className="absolute -bottom-10 -left-10 w-32 h-32 bg-[#4ECDC4]/20 rounded-full blur-xl" />
            
            <View className="flex-row items-center">
              <View className="flex-1 z-10">
                <View className="bg-[#44A08D]/20 self-start px-3 py-1 rounded-full mb-3 border border-[#44A08D]/30">
                  <Text className="text-[#44A08D] font-bold text-[10px] tracking-wider uppercase">Limited Time</Text>
                </View>
                
                <Text className="text-white font-bold text-2xl mb-1 leading-tight">
                  Gagnez <Text className="text-[#44A08D]">5%</Text>
                </Text>
                <Text className="text-gray-400 text-xs mb-5 font-medium leading-5 w-4/5">
                  Sur votre premier achat de carte cadeau.
                </Text>
                
                <TouchableOpacity 
                  className="bg-[#44A08D] py-3 px-6 rounded-xl self-start shadow-lg shadow-teal-500/30 flex-row items-center"
                  onPress={() => router.push('/(tabs)/boutique')}
                >
                  <Text className="text-white font-bold text-xs mr-2">EN PROFITER</Text>
                  <View className="bg-white/20 rounded-full p-1">
                     {/* Simple arrow icon or similar could go here, using text for now */}
                     <Text className="text-white text-[8px]">➜</Text>
                  </View>
                </TouchableOpacity>
              </View>
              
              {/* 3D-like Visual */}
              <View className="w-24 h-24 absolute -right-2 top-1/2 -translate-y-1/2 items-center justify-center">
                <View className="absolute w-20 h-20 bg-[#44A08D] rounded-2xl transform rotate-12 opacity-20" />
                <View className="absolute w-20 h-20 bg-[#44A08D] rounded-2xl transform -rotate-6 opacity-40" />
                <View className="w-20 h-20 bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] rounded-2xl items-center justify-center shadow-2xl border-t border-white/20 transform rotate-6">
                   <Text className="text-4xl">🏷️</Text>
                </View>
                {/* Floating particles */}
                <View className="absolute -top-4 right-0 w-2 h-2 bg-[#FFD700] rounded-full opacity-80" />
                <View className="absolute bottom-0 -left-2 w-3 h-3 bg-[#4ECDC4] rounded-full opacity-60" />
              </View>
            </View>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
