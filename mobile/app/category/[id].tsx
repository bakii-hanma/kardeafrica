import { View, Text, TouchableOpacity, ScrollView, Image, StatusBar, ActivityIndicator, TextInput, Modal, RefreshControl } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeftIcon, AdjustmentsHorizontalIcon, XMarkIcon } from 'react-native-heroicons/outline';
import { useState, useEffect, useMemo } from 'react';
import { CatalogService, CatalogProduct, CatalogFilter } from '../../services/catalog';
import { CATEGORIES } from '../../data/mock';
import EmptyState from '../../components/EmptyState';
import { convertAndFormatToFCFA, convertToFCFA } from '../../utils/currency';
import LoadingKeychain from '../../components/LoadingKeychain';

// Brand color helper — IDENTIQUE à boutique/cart/product/wallet
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
  const palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
  let hash = 0;
  for (let i = 0; i < brandName.length; i++) hash = brandName.charCodeAt(i) + ((hash << 5) - hash);
  const idx = ((hash % palette.length) + palette.length) % palette.length;
  return palette[idx];
};

const getTextColor = (backgroundColor: string) => {
  const lightColors = ['#FF9900', '#FCD34D', '#FFFFFF'];
  return lightColors.includes(backgroundColor) ? '#000000' : '#FFFFFF';
};

export default function CategoryScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams();
  const categoryId = typeof id === 'string' ? parseInt(id, 10) : null;
  
  const category = CATEGORIES.find(c => c.id === categoryId);

  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [showFilters, setShowFilters] = useState(false);
  
  // Filter states
  const [minPrice, setMinPrice] = useState('');
  const [maxPrice, setMaxPrice] = useState('');
  const [selectedCurrency, setSelectedCurrency] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  // Initial fetch
  useEffect(() => {
    loadProducts();
  }, [categoryId]);

  const onRefresh = () => {
    setRefreshing(true);
    loadProducts();
  };

  const loadProducts = async () => {
    try {
      if (!refreshing) setIsLoading(true);
      const response = await CatalogService.getProducts({ per_page: 100 });
      setProducts(response.data ?? []);
    } catch (error) {
      console.error('Failed to load category products:', error);
      setProducts([]);
    } finally {
      setIsLoading(false);
      setRefreshing(false);
    }
  };

  // Filter logic
  const filteredProducts = useMemo(() => {
    return products.filter(product => {
      // 1. Filter by Category (Mock mapping)
      if (categoryId) {
        const brandName = product.brand.name.toLowerCase();
        const productName = product.name.toLowerCase();
        
        // Find category by ID to get keywords
        const category = CATEGORIES.find(c => c.id === categoryId);
        
        if (category && category.keywords) {
           if (!category.keywords.some(k => brandName.includes(k) || productName.includes(k))) return false;
        }
      }

      // 2. Filter by Search Query
      if (searchQuery) {
        const query = searchQuery.toLowerCase();
        if (!product.name.toLowerCase().includes(query) && 
            !product.brand.name.toLowerCase().includes(query)) {
          return false;
        }
      }

      // 3. Filter by Price
      const priceInFCFA = convertToFCFA(product.price.min, product.price.currencyCode);
      if (minPrice && priceInFCFA < parseFloat(minPrice)) return false;
      if (maxPrice && priceInFCFA > parseFloat(maxPrice)) return false;

      // 4. Filter by Currency
      if (selectedCurrency && product.price.currencyCode !== selectedCurrency) return false;

      return true;
    });
  }, [products, categoryId, searchQuery, minPrice, maxPrice, selectedCurrency]);

  if (!category && !id) {
    return (
      <SafeAreaView className="flex-1 bg-gray-50 items-center justify-center">
        <Text className="text-gray-500">Catégorie non trouvée</Text>
        <TouchableOpacity onPress={() => router.back()} className="mt-4 p-2 bg-gray-200 rounded-lg">
          <Text>Retour</Text>
        </TouchableOpacity>
      </SafeAreaView>
    );
  }

  const title = category ? `${category.emoji} ${category.name}` : 'Produits';

  return (
    <SafeAreaView className="flex-1" style={{ backgroundColor: '#FAFAF7' }}>
      <StatusBar barStyle="light-content" backgroundColor="#0F172A" />

      {/* Header sombre style web */}
      <View style={{ backgroundColor: '#0F172A' }} className="px-4 pt-2 pb-5 relative overflow-hidden">
        {/* Halo */}
        <View style={{ position: 'absolute', top: -50, right: -50, width: 220, height: 220, borderRadius: 110, backgroundColor: 'rgba(78,205,196,0.12)' }} />

        <View className="flex-row items-center justify-between mb-3" style={{ position: 'relative' }}>
          <View className="flex-row items-center flex-1 min-w-0">
            <TouchableOpacity onPress={() => router.back()} className="mr-3 w-9 h-9 rounded-xl items-center justify-center" style={{ backgroundColor: 'rgba(255,255,255,0.08)' }}>
              <ArrowLeftIcon size={18} color="#FFFFFF" />
            </TouchableOpacity>
            <View className="flex-1 min-w-0">
              <Text style={{ letterSpacing: 1.5 }} className="text-[#5EEAD4] text-[10px] font-bold uppercase">Catégorie</Text>
              <Text className="text-xl font-black text-white tracking-tight mt-0.5" numberOfLines={1}>
                {category?.emoji} {category?.name || 'Produits'}
              </Text>
            </View>
          </View>
          <TouchableOpacity
            onPress={() => setShowFilters(true)}
            className="w-9 h-9 rounded-xl items-center justify-center"
            style={{ backgroundColor: (selectedCurrency || minPrice || maxPrice) ? '#44A08D' : 'rgba(255,255,255,0.08)' }}
          >
            <AdjustmentsHorizontalIcon size={16} color="#FFFFFF" />
          </TouchableOpacity>
        </View>

        {/* Search Bar */}
        <View className="flex-row items-center rounded-xl px-3 py-2.5 border" style={{ backgroundColor: 'rgba(255,255,255,0.06)', borderColor: 'rgba(255,255,255,0.10)' }}>
          <TextInput
            placeholder="Rechercher dans cette catégorie…"
            placeholderTextColor="#64748B"
            value={searchQuery}
            onChangeText={setSearchQuery}
            className="flex-1 text-sm text-white"
          />
        </View>
      </View>

      {/* Content */}
      <ScrollView 
        className="flex-1 px-4 pt-6" 
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#1F2937']} tintColor="#1F2937" />
        }
      >
        {isLoading && !refreshing ? (
        <View className="flex-1 items-center justify-center">
          <LoadingKeychain />
        </View>
      ) : (
          <View className="flex-row flex-wrap justify-between pb-20">
            {filteredProducts.length > 0 ? (
              filteredProducts.map((product) => {
                const brandColor = getBrandColor(product.brand.name);
                const textColor = getTextColor(brandColor);
                
                return (
                  <TouchableOpacity 
                    key={product.id}
                    className="w-[48%] bg-white rounded-2xl mb-4 overflow-hidden shadow-sm border border-gray-100"
                    onPress={() => router.push({
                      pathname: '/product/[id]',
                      params: { 
                        id: product.id,
                        name: product.name,
                        description: product.brand.description,
                        logoUrl: product.brand.logoUrl,
                        brandName: product.brand.name,
                        currencyCode: product.price.currencyCode,
                        minPrice: product.price.min.toString(),
                        maxPrice: product.price.max.toString()
                      }
                    })}
                  >
                    {/* Top Card Section */}
                    <View style={{ backgroundColor: brandColor }} className="h-24 p-2 relative overflow-hidden">
                      <Text className="text-white/80 font-bold text-[8px] tracking-widest uppercase mb-0.5">{product.brand.name}</Text>
                      <Text style={{ color: textColor }} className="font-black text-lg tracking-tighter shadow-sm" numberOfLines={1}>
                        {product.name.split(' ').slice(0, 2).join(' ')}
                      </Text>
                      
                      {/* Decorative Elements */}
                      <View className="absolute -right-2 -bottom-2 opacity-20">
                        <View className="w-16 h-16 rounded-full bg-white/30" />
                      </View>
                      <View className="absolute right-2 bottom-2">
                        <Text className="text-xl">🎁</Text>
                      </View>
                    </View>

                    {/* Bottom Details Section */}
                    <View className="bg-white p-2 pt-5 relative">
                      {/* Floating Circular Logo */}
                      <View className="absolute -top-4 left-2 w-8 h-8 bg-white rounded-full p-0.5 shadow-sm items-center justify-center">
                        <View className="w-full h-full rounded-full bg-gray-50 items-center justify-center overflow-hidden">
                           {product.brand.logoUrl ? (
                             <Image 
                               source={{ uri: product.brand.logoUrl }} 
                               className="w-full h-full" 
                               resizeMode="contain"
                             />
                           ) : (
                             <Text className="text-xs font-bold text-gray-800">{product.brand.name.charAt(0)}</Text>
                           )}
                        </View>
                      </View>

                      <Text className="text-gray-900 font-bold text-xs mb-0.5" numberOfLines={1}>{product.name}</Text>
                      <Text className="text-gray-500 text-[10px] mb-2 h-8" numberOfLines={2}>
                        {product.brand.description || 'Carte cadeau disponible immédiatement'}
                      </Text>
                      
                      <View className="flex-row items-center justify-between mt-auto">
                        <View className="bg-green-50 px-1.5 py-0.5 rounded border border-green-100">
                          <Text className="text-green-700 text-[9px] font-bold">2% OFF</Text>
                        </View>
                        <Text className="text-gray-900 font-bold text-xs">
                          {convertAndFormatToFCFA(product.price.min, product.price.currencyCode)}
                        </Text>
                      </View>
                    </View>
                  </TouchableOpacity>
                );
              })
            ) : (
              <View className="w-full mt-10">
                <EmptyState 
                  title="Aucun produit" 
                  message="Aucun produit ne correspond à vos critères dans cette catégorie." 
                  onRefresh={() => {
                     setSearchQuery('');
                     setMinPrice('');
                     setMaxPrice('');
                     setSelectedCurrency(null);
                     onRefresh();
                  }}
                  buttonText="Réinitialiser"
                />
              </View>
            )}
          </View>
        )}
      </ScrollView>

      {/* Filter Modal */}
      <Modal
        visible={showFilters}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setShowFilters(false)}
      >
        <View className="flex-1 bg-black/50 justify-end">
          <View className="bg-white rounded-t-3xl p-6 h-[70%]">
            <View className="flex-row justify-between items-center mb-6">
              <Text className="text-xl font-bold text-gray-900">Filtres</Text>
              <TouchableOpacity onPress={() => setShowFilters(false)}>
                <XMarkIcon size={24} color="#6B7280" />
              </TouchableOpacity>
            </View>

            <ScrollView showsVerticalScrollIndicator={false}>
              {/* Price Range */}
              <View className="mb-6">
                <Text className="text-sm font-medium text-gray-700 mb-3">Prix</Text>
                <View className="flex-row space-x-4">
                  <View className="flex-1">
                    <Text className="text-xs text-gray-500 mb-1">Min</Text>
                    <TextInput
                      value={minPrice}
                      onChangeText={setMinPrice}
                      keyboardType="numeric"
                      placeholder="0"
                      className="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3"
                    />
                  </View>
                  <View className="flex-1">
                    <Text className="text-xs text-gray-500 mb-1">Max</Text>
                    <TextInput
                      value={maxPrice}
                      onChangeText={setMaxPrice}
                      keyboardType="numeric"
                      placeholder="1000"
                      className="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3"
                    />
                  </View>
                </View>
              </View>

              {/* Currency */}
              <View className="mb-6">
                <Text className="text-sm font-medium text-gray-700 mb-3">Devise</Text>
                <View className="flex-row flex-wrap gap-2">
                  {['USD', 'EUR', 'XOF', 'GBP', 'CAD', 'AED', 'XAF'].map((curr) => (
                    <TouchableOpacity
                      key={curr}
                      onPress={() => setSelectedCurrency(selectedCurrency === curr ? null : curr)}
                      className={`px-4 py-2 rounded-full border ${
                        selectedCurrency === curr 
                          ? 'bg-gray-900 border-gray-900' 
                          : 'bg-white border-gray-200'
                      }`}
                    >
                      <Text className={selectedCurrency === curr ? 'text-white' : 'text-gray-700'}>
                        {curr}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>

              {/* Reset Button */}
              <TouchableOpacity
                onPress={() => {
                  setMinPrice('');
                  setMaxPrice('');
                  setSelectedCurrency(null);
                  setShowFilters(false);
                }}
                className="w-full bg-gray-100 py-4 rounded-xl items-center mt-4"
              >
                <Text className="text-gray-700 font-bold">Réinitialiser</Text>
              </TouchableOpacity>

              {/* Apply Button */}
              <TouchableOpacity
                onPress={() => setShowFilters(false)}
                className="w-full bg-gray-900 py-4 rounded-xl items-center mt-4 mb-8"
              >
                <Text className="text-white font-bold">Appliquer</Text>
              </TouchableOpacity>
            </ScrollView>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
