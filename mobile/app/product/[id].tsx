import { View, Text, ScrollView, Image, TouchableOpacity, TextInput, ActivityIndicator, RefreshControl, Alert, Animated, Easing } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { ArrowLeftIcon, InformationCircleIcon, CheckIcon } from 'react-native-heroicons/outline';
import { PRODUCTS } from '../../data/mock';
import { useState, useEffect, useRef } from 'react';
import { SafeAreaView } from 'react-native-safe-area-context';
import { CatalogService } from '../../services/catalog';
import AddToCartModal from '../../components/AddToCartModal';
import { convertToFCFA } from '../../utils/currency';
import LoadingKeychain from '../../components/LoadingKeychain';
import { useCart } from '../../context/CartContext';

// Brand color helper — IDENTIQUE à boutique.tsx / wallet.tsx / cart.tsx
// Garantit la même couleur pour une marque donnée, partout dans l'app.
const BRAND_COLORS: Record<string, string> = {
  Netflix: '#E50914', Spotify: '#1DB954', Apple: '#000000',
  iTunes: '#D60017', PlayStation: '#003791', Xbox: '#107C10',
  Amazon: '#FF9900', Google: '#01875F', Steam: '#171A21',
  Roblox: '#00A2FF', Nintendo: '#E60012', Disney: '#0E47A1',
  StarzPlay: '#7C3AED', Talabat: '#FF5A00', HUAWEI: '#C7000B', IKEA: '#0058A3',
  Daywatch: '#44A08D',
};

const getBrandColor = (brandName: string): string => {
  if (!brandName) return '#0F172A';
  for (const [key, color] of Object.entries(BRAND_COLORS)) {
    if (brandName.toLowerCase().includes(key.toLowerCase())) return color;
  }
  // Fallback hash-based — même formule que boutique.tsx pour cohérence inter-vues
  const palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
  let hash = 0;
  for (let i = 0; i < brandName.length; i++) hash = brandName.charCodeAt(i) + ((hash << 5) - hash);
  const idx = ((hash % palette.length) + palette.length) % palette.length;
  return palette[idx];
};

// Compat — pas utilisé dans le nouveau design mais conservé au cas où le mock l'appelle
const getTextColor = (backgroundColor: string) => {
  const lightColors = ['#FF9900', '#FCD34D', '#FFFFFF'];
  return lightColors.includes(backgroundColor) ? '#000000' : '#FFFFFF';
};

// Un "tier" = un montant disponible (Xbox FR 5 EUR, Xbox FR 10 EUR…).
// Chaque tier est un produit à part entière avec son propre ID afrikard.
type Tier = { id: string; label: string; native: number; fcfa: number; currency: string };

export default function ProductDetailScreen() {
  const params = useLocalSearchParams();
  const { id } = params;
  const router = useRouter();

  const { addToCart } = useCart();

  const [product, setProduct] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedTier, setSelectedTier] = useState<Tier | null>(null);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [addedItem, setAddedItem] = useState<any>(null);

  // Animation values
  const rotation = useRef(new Animated.Value(0)).current;
  const floatY = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Start floating animation
    Animated.loop(
      Animated.sequence([
        Animated.timing(floatY, { toValue: -15, duration: 2000, useNativeDriver: true, easing: Easing.inOut(Easing.sin) }),
        Animated.timing(floatY, { toValue: 0, duration: 2000, useNativeDriver: true, easing: Easing.inOut(Easing.sin) })
      ])
    ).start();
  }, []);

  const handleCardPress = () => {
    Animated.timing(rotation, {
      toValue: 1,
      duration: 1000,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: true
    }).start(() => {
      rotation.setValue(0);
    });
  };

  const spin = rotation.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg']
  });

  const animatedStyle = {
    transform: [
      { perspective: 1000 },
      { rotateY: spin },
      { translateY: floatY }
    ]
  } as any;

  useEffect(() => {
    loadProduct();
  }, [id]);

  const loadProduct = async () => {
    try {
      setLoading(true);

      // 1. Mock products (legacy demo IDs comme 'netflix')
      const mockProduct = PRODUCTS.find(p => p.id === id);
      if (mockProduct) {
        const tiers = mockProduct.values.map(v => ({
          id:      `${mockProduct.id}-${v}`,
          label:   `${v} ${mockProduct.currency}`,
          native:  v,
          fcfa:    convertToFCFA(v, mockProduct.currency),
          currency:mockProduct.currency,
        }));
        setProduct({
          id: mockProduct.id,
          name: mockProduct.name,
          description: mockProduct.description,
          logoUrl: mockProduct.logo,
          brandName: mockProduct.name,
          currencyCode: mockProduct.currency,
          tiers,
          isMock: true,
        });
        setSelectedTier(tiers[0]);
        setLoading(false);
        return;
      }

      // 2. Vraie carte API : on appelle /api/v1/catalog/{id} qui renvoie le
      //    produit + tous ses siblings (= autres montants du même cardType).
      //    Chaque sibling est un produit à part entière avec son propre ID,
      //    son native_value (ex: 5 EUR) et son price_fcfa déjà converti.
      const apiResp = await CatalogService.getProductById(String(id));
      if (apiResp && apiResp.data) {
        const main = apiResp.data;
        const ct   = main.cardType ?? main.brand ?? {};
        const allSiblings = Array.isArray(apiResp.siblings) && apiResp.siblings.length > 0
          ? apiResp.siblings
          : Array.isArray(apiResp.products) && apiResp.products.length > 0
            ? apiResp.products
            : [main];

        // Trie par valeur native croissante pour un rendu naturel (5€, 10€, 25€…)
        const tiers = allSiblings
          .filter((s: any) => s && (s.native_value ?? 0) > 0)
          .map((s: any) => ({
            id:       String(s.id),
            label:    s.name ?? `${s.native_value} ${s.native_currency}`,
            native:   Number(s.native_value ?? 0),
            fcfa:     Number(s.price_fcfa ?? 0),
            currency: s.native_currency ?? main.native_currency ?? 'EUR',
          }))
          .sort((a: any, b: any) => a.native - b.native);

        // L'utilisateur a tapé un produit précis : on présélectionne CE montant
        // (pas forcément le min) — meilleure UX que tomber au montant le plus bas.
        const initialTier = tiers.find((t: any) => t.id === String(main.id)) ?? tiers[0];

        setProduct({
          id:                     main.id,
          name:                   main.name,
          description:            ct.description ?? '',
          terms:                  ct.terms ?? '',
          redemptionInstructions: ct.redemptionInstructions ?? '',
          logoUrl:                ct.logoUrl ?? null,
          brandName:              ct.name ?? main.name,
          countryCode:            ct.countryCode ?? null,
          currencyCode:           main.native_currency ?? ct.currencyCode ?? 'EUR',
          region:                 ct.region ?? null,
          popular_in_africa:      ct.popular_in_africa ?? false,
          categories:             ct.categories ?? [],
          tiers,
          isMock: false,
        });
        setSelectedTier(initialTier);
        return;
      }

      // 3. Si l'API par-id échoue, on utilise les params de navigation pour
      //    afficher au moins quelque chose (mode dégradé : 1 seul montant)
      if (params.name && params.logoUrl && params.currencyCode) {
        const native = parseFloat(params.minPrice as string);
        const fcfa   = Number(params.priceFcfa) || convertToFCFA(native, params.currencyCode as string);
        const tiers = [{
          id:       String(id),
          label:    `${native} ${params.currencyCode}`,
          native,
          fcfa,
          currency: params.currencyCode as string,
        }];
        setProduct({
          id:           Number(id),
          name:         params.name as string,
          description:  '',
          logoUrl:      params.logoUrl as string,
          brandName:    params.brandName as string,
          currencyCode: params.currencyCode as string,
          tiers,
          isMock: false,
        });
        setSelectedTier(tiers[0]);
        return;
      }

      console.error('Product not found:', id);
    } catch (error) {
      console.error('Error loading product:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadProduct();
  };

  const handleAddToCart = () => {
    if (!product || !selectedTier) return;

    const brandColor = getBrandColor(product.brandName || product.name);
    const textColor = getTextColor(brandColor);

    const newItem = {
      // ID unique = ID afrikard du tier sélectionné. Permet d'ajouter Xbox FR 10
      // et Xbox FR 50 séparément au panier (siblings ≠ duplicatas).
      id:        String(selectedTier.id),
      productId: selectedTier.id,
      name:      selectedTier.label,
      price:     selectedTier.fcfa,
      quantity:  1,
      currency:  'XAF',
      image:     product.logoUrl,
      brandName: product.brandName,
      type:      'Gift Card',
      color:     brandColor,
      textColor: textColor,
    };

    addToCart(newItem);
    setAddedItem(newItem);
    setShowSuccessModal(true);
  };

  if (loading && !refreshing) {
    return (
      <SafeAreaView className="flex-1 bg-white items-center justify-center">
        <LoadingKeychain />
      </SafeAreaView>
    );
  }

  if (!product) {
    return (
      <SafeAreaView className="flex-1 bg-white items-center justify-center">
        <Text className="text-gray-500">Produit non trouvé</Text>
        <TouchableOpacity onPress={() => router.back()} className="mt-4 p-2 bg-gray-200 rounded-lg">
          <Text>Retour</Text>
        </TouchableOpacity>
      </SafeAreaView>
    );
  }

  const brandColor = getBrandColor(product.brandName || product.name);
  const initial = (product.brandName || product.name || '?').charAt(0).toUpperCase();

  return (
    <SafeAreaView className="flex-1" style={{ backgroundColor: '#FAFAF7' }}>
      {/* Header sombre style web */}
      <View style={{ backgroundColor: '#0F172A' }} className="px-4 pt-2 pb-5 relative overflow-hidden">
        <View style={{ position: 'absolute', top: -50, right: -50, width: 220, height: 220, borderRadius: 110, backgroundColor: 'rgba(78,205,196,0.12)' }} />
        <View className="flex-row items-center justify-between" style={{ position: 'relative' }}>
          <TouchableOpacity onPress={() => router.back()} className="w-9 h-9 rounded-xl items-center justify-center" style={{ backgroundColor: 'rgba(255,255,255,0.08)' }}>
            <ArrowLeftIcon size={18} color="#FFFFFF" />
          </TouchableOpacity>
          <View style={{ flex: 1, alignItems: 'center' }}>
            <Text style={{ letterSpacing: 1.5 }} className="text-[#5EEAD4] text-[10px] font-bold uppercase">Carte cadeau</Text>
            <Text className="text-white text-base font-black mt-0.5" numberOfLines={1}>{product.brandName || product.name}</Text>
          </View>
          <View style={{ width: 36 }} />
        </View>
      </View>

      <ScrollView
        className="flex-1"
        contentContainerStyle={{ paddingBottom: 200 }}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#44A08D']} tintColor="#44A08D" />}
      >
        {/* ============================ GIFT CARD VISUAL ============================ */}
        <View className="px-4 pt-6 pb-2">
          <Animated.View style={[animatedStyle]}>
            <TouchableOpacity activeOpacity={0.92} onPress={handleCardPress} style={{ width: '100%' }}>
              <View style={{
                width: '100%',
                aspectRatio: 1.6,
                borderRadius: 24,
                backgroundColor: brandColor,
                overflow: 'hidden',
                shadowColor: brandColor,
                shadowOffset: { width: 0, height: 24 },
                shadowOpacity: 0.45,
                shadowRadius: 30,
                elevation: 12,
              }}>
                {/* Halos */}
                <View style={{ position: 'absolute', top: -50, right: -50, width: 200, height: 200, borderRadius: 100, backgroundColor: 'rgba(255,255,255,0.18)' }} />
                <View style={{ position: 'absolute', bottom: -40, left: -40, width: 120, height: 120, borderRadius: 60, backgroundColor: 'rgba(255,255,255,0.10)' }} />

                {/* Content */}
                <View style={{ position: 'relative', flex: 1, padding: 22, justifyContent: 'space-between' }}>
                  {/* Top : label + logo */}
                  <View className="flex-row items-start justify-between">
                    <View style={{ flex: 1 }}>
                      <Text style={{ color: 'rgba(255,255,255,0.78)', fontSize: 10, fontWeight: '700', letterSpacing: 1.8, textTransform: 'uppercase' }}>
                        Gift Card
                      </Text>
                      <Text style={{ color: '#FFFFFF', fontSize: 28, fontWeight: '900', letterSpacing: -0.5, marginTop: 6 }} numberOfLines={1}>
                        {product.brandName || product.name}
                      </Text>
                    </View>
                    <View style={{
                      width: 56, height: 56, borderRadius: 16,
                      backgroundColor: '#FFFFFF',
                      alignItems: 'center', justifyContent: 'center',
                      shadowColor: '#0F172A', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.18, shadowRadius: 8,
                      overflow: 'hidden',
                    }}>
                      {product.logoUrl ? (
                        <Image source={{ uri: product.logoUrl }} style={{ width: '85%', height: '85%' }} resizeMode="contain" />
                      ) : (
                        <Text style={{ fontSize: 24, fontWeight: '900', color: brandColor }}>{initial}</Text>
                      )}
                    </View>
                  </View>

                  {/* Bottom : price + chip gold */}
                  <View className="flex-row items-end justify-between">
                    <View>
                      <Text style={{ color: 'rgba(255,255,255,0.65)', fontSize: 9, fontWeight: '700', letterSpacing: 1, textTransform: 'uppercase' }}>
                        {selectedTier ? 'Sélectionné' : 'Dès'}
                      </Text>
                      <Text style={{ color: '#FFFFFF', fontSize: 26, fontWeight: '900', fontVariant: ['tabular-nums'], marginTop: 2 }}>
                        {selectedTier
                          ? new Intl.NumberFormat('fr-FR').format(Math.round(selectedTier.fcfa)) + ' FCFA'
                          : new Intl.NumberFormat('fr-FR').format(Math.round(product.tiers?.[0]?.fcfa ?? 0)) + ' FCFA'}
                      </Text>
                    </View>
                    <View style={{ width: 42, height: 30, borderRadius: 6, backgroundColor: '#FCD34D', borderWidth: 1, borderColor: 'rgba(255,255,255,0.4)' }} />
                  </View>
                </View>

                {/* Numéro carte décoratif */}
                <Text style={{ position: 'absolute', bottom: 8, left: 22, fontSize: 9, color: 'rgba(255,255,255,0.4)', fontFamily: 'monospace', letterSpacing: 2 }}>
                  •••• •••• •••• 4829
                </Text>
              </View>
            </TouchableOpacity>
          </Animated.View>

          <Text className="text-slate-400 text-[11px] mt-3 text-center">Touchez la carte pour la faire pivoter</Text>
        </View>

        {/* ============================ PRICE SELECTOR ============================ */}
        <View className="px-4 pt-6">
          <View className="flex-row items-center justify-between mb-3">
            <View className="flex-row items-center gap-2">
              <View style={{ width: 6, height: 6, borderRadius: 3, backgroundColor: '#44A08D' }} />
              <Text style={{ letterSpacing: 1.2 }} className="text-[#44A08D] text-[10px] font-black uppercase">
                Choisir le montant
              </Text>
            </View>
            <Text className="text-[10px] font-bold text-slate-400">
              {product.tiers?.length ?? 0} montant{(product.tiers?.length ?? 0) > 1 ? 's' : ''} dispo
            </Text>
          </View>

          <View className="flex-row flex-wrap" style={{ gap: 8 }}>
            {product.tiers?.map((tier: Tier) => {
              const active = selectedTier?.id === tier.id;
              return (
                <TouchableOpacity
                  key={tier.id}
                  onPress={() => setSelectedTier(tier)}
                  style={{
                    paddingVertical: 12,
                    paddingHorizontal: 16,
                    borderRadius: 12,
                    borderWidth: active ? 2 : 1,
                    borderColor: active ? '#44A08D' : '#E2E8F0',
                    backgroundColor: active ? 'rgba(68,160,141,0.10)' : '#FFFFFF',
                    minWidth: 110,
                    alignItems: 'center',
                  }}
                >
                  <Text style={{ fontSize: 10, color: active ? '#44A08D' : '#94A3B8', fontWeight: '700', textTransform: 'uppercase', letterSpacing: 1 }}>
                    {tier.native} {tier.currency}
                  </Text>
                  <Text style={{ fontSize: 14, fontWeight: '900', color: active ? '#0F766E' : '#0F172A', marginTop: 2, fontVariant: ['tabular-nums'] }}>
                    {new Intl.NumberFormat('fr-FR').format(Math.round(tier.fcfa))} FCFA
                  </Text>
                </TouchableOpacity>
              );
            })}
          </View>
        </View>

        {/* ============================ FEATURES ============================ */}
        <View className="px-4 pt-6">
          <View className="bg-white rounded-2xl border border-slate-200 p-2" style={{ shadowColor: '#0F172A', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.04, shadowRadius: 6, elevation: 1 }}>
            {[
              { icon: '⚡', label: 'Livraison instantanée par email', sub: 'Moins de 60 secondes' },
              { icon: '🔒', label: 'Paiement 100% sécurisé', sub: 'Mobile Money & Visa via Futursowax' },
              { icon: '✓',  label: 'Code authentique garanti', sub: 'Remboursé en cas de problème' },
            ].map((feat, i, arr) => (
              <View key={i} className="flex-row items-center" style={{ gap: 12, paddingVertical: 12, paddingHorizontal: 12, borderBottomWidth: i < arr.length - 1 ? 1 : 0, borderBottomColor: '#F1F5F9' }}>
                <View style={{ width: 38, height: 38, borderRadius: 11, backgroundColor: 'rgba(68,160,141,0.10)', alignItems: 'center', justifyContent: 'center' }}>
                  <Text style={{ fontSize: 16 }}>{feat.icon}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text className="text-sm font-bold text-slate-900">{feat.label}</Text>
                  <Text className="text-[11px] text-slate-500 mt-0.5">{feat.sub}</Text>
                </View>
              </View>
            ))}
          </View>
        </View>

        {/* ============================ INFOS PRODUIT (méta) ============================ */}
        <View className="px-4 pt-6">
          <Text className="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2" style={{ letterSpacing: 1.5 }}>
            Informations
          </Text>
          <View className="bg-white rounded-2xl border border-slate-200 p-1">
            {[
              product.brandName       && { label: 'Marque',        value: product.brandName },
              product.countryCode     && { label: 'Pays',          value: product.countryCode },
              product.region          && { label: 'Région',        value: product.region.charAt(0).toUpperCase() + product.region.slice(1) },
              product.currencyCode    && { label: 'Devise native', value: product.currencyCode },
              (product.tiers?.length > 0) && { label: 'Montants disponibles', value: `${product.tiers.length} montant${product.tiers.length > 1 ? 's' : ''}` },
              product.popular_in_africa && { label: 'Mise en avant', value: '🔥 Populaire en Afrique', highlight: true },
              product.categories?.length > 0 && { label: 'Catégorie', value: product.categories.map((c: any) => c.name).join(', ') },
            ].filter(Boolean).map((row: any, i, arr) => (
              <View key={i} className="flex-row items-center justify-between" style={{
                paddingVertical: 11, paddingHorizontal: 14,
                borderBottomWidth: i < arr.length - 1 ? 1 : 0, borderBottomColor: '#F1F5F9',
              }}>
                <Text className="text-[11px] font-semibold uppercase" style={{ color: '#94A3B8', letterSpacing: 0.5 }}>{row.label}</Text>
                <Text className="text-[13px] font-bold" style={{ color: row.highlight ? '#B45309' : '#0F172A' }}>{row.value}</Text>
              </View>
            ))}
          </View>
        </View>

        {/* ============================ DESCRIPTION ============================ */}
        {!!product.description && (
          <View className="px-4 pt-6">
            <Text className="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2" style={{ letterSpacing: 1.5 }}>
              Description
            </Text>
            <View className="bg-white rounded-2xl border border-slate-200 p-4">
              <Text className="text-sm text-slate-700" style={{ lineHeight: 22 }}>
                {product.description}
              </Text>
            </View>
          </View>
        )}

        {/* ============================ COMMENT UTILISER ============================ */}
        <View className="px-4 pt-6">
          <Text className="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2" style={{ letterSpacing: 1.5 }}>
            Comment l'utiliser
          </Text>
          <View className="bg-white rounded-2xl border border-slate-200 p-4">
            <View style={{ gap: 12 }}>
              {[
                'Choisis le montant et passe la commande.',
                'Reçois le code instantanément par email et dans « Mes cartes ».',
                `Utilise-le sur le site officiel de ${product.brandName || product.name}.`,
              ].map((step, i) => (
                <View key={i} className="flex-row" style={{ gap: 12 }}>
                  <View style={{ width: 24, height: 24, borderRadius: 12, backgroundColor: '#44A08D', alignItems: 'center', justifyContent: 'center', flexShrink: 0, marginTop: 1 }}>
                    <Text className="text-white text-xs font-black">{i + 1}</Text>
                  </View>
                  <Text className="text-sm text-slate-700 flex-1" style={{ lineHeight: 20 }}>{step}</Text>
                </View>
              ))}
            </View>

            {/* Instructions de redemption (afrikard) */}
            {!!product.redemptionInstructions && (
              <View className="mt-4 pt-4" style={{ borderTopWidth: 1, borderTopColor: '#F1F5F9' }}>
                <Text className="text-[11px] font-black uppercase tracking-wider mb-2" style={{ color: '#44A08D', letterSpacing: 1 }}>
                  Instructions du fournisseur
                </Text>
                <Text className="text-xs text-slate-600" style={{ lineHeight: 19 }}>
                  {product.redemptionInstructions}
                </Text>
              </View>
            )}
          </View>
        </View>

        {/* ============================ CONDITIONS / TERMES ============================ */}
        {!!product.terms && (
          <View className="px-4 pt-6">
            <Text className="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2" style={{ letterSpacing: 1.5 }}>
              Conditions générales
            </Text>
            <View className="bg-amber-50 rounded-2xl border border-amber-100 p-4">
              <Text className="text-xs text-amber-900" style={{ lineHeight: 19 }}>
                {product.terms}
              </Text>
            </View>
          </View>
        )}
      </ScrollView>

      {/* ============================ BOTTOM CTA ============================ */}
      <View style={{
        position: 'absolute', bottom: 0, left: 0, right: 0,
        backgroundColor: '#FFFFFF',
        borderTopWidth: 1, borderTopColor: '#F1F5F9',
        paddingTop: 14, paddingBottom: 28, paddingHorizontal: 16,
        shadowColor: '#0F172A', shadowOffset: { width: 0, height: -4 }, shadowOpacity: 0.06, shadowRadius: 8,
      }}>
        <View className="flex-row items-center justify-between">
          <View>
            <Text style={{ letterSpacing: 1 }} className="text-[10px] font-bold uppercase text-slate-400">Total</Text>
            <Text className="text-2xl font-black text-slate-900" style={{ fontVariant: ['tabular-nums'] }}>
              {selectedTier ? new Intl.NumberFormat('fr-FR').format(Math.round(selectedTier.fcfa)) + ' FCFA' : '0 FCFA'}
            </Text>
          </View>
          <View className="flex-row" style={{ gap: 8 }}>
            <TouchableOpacity
              onPress={handleAddToCart}
              style={{ paddingHorizontal: 18, paddingVertical: 13, borderRadius: 12, borderWidth: 1.5, borderColor: '#E2E8F0', backgroundColor: '#FFFFFF', alignItems: 'center', justifyContent: 'center' }}
            >
              <Text className="text-slate-700 font-bold text-sm">+ Panier</Text>
            </TouchableOpacity>
            <TouchableOpacity
              onPress={() => { handleAddToCart(); router.push('/(tabs)/cart'); }}
              style={{
                paddingHorizontal: 22, paddingVertical: 13, borderRadius: 12,
                backgroundColor: '#44A08D', alignItems: 'center', justifyContent: 'center',
                shadowColor: '#44A08D', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.45, shadowRadius: 14, elevation: 4,
              }}
            >
              <Text className="text-white font-bold text-sm">Acheter</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>

      <AddToCartModal 
        visible={showSuccessModal}
        onClose={() => setShowSuccessModal(false)}
        onGoToCart={() => router.push('/(tabs)/cart')}
        product={addedItem}
      />
    </SafeAreaView>
  );
}