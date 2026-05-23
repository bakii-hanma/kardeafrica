import { View, Text, TouchableOpacity, ScrollView, Image, StatusBar, RefreshControl, Modal } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { TrashIcon, MinusIcon, PlusIcon, ShoppingBagIcon, ArrowRightIcon, Squares2X2Icon, ListBulletIcon } from 'react-native-heroicons/outline';
import { useState } from 'react';
import { useRouter } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import EmptyState from '../../components/EmptyState';
import LoginRequiredModal from '../../components/LoginRequiredModal';
import { useCart } from '../../context/CartContext';
import { useAlert } from '../../context/AlertContext';
import { formatFCFA } from '../../utils/currency';
import NavbarProfile from '../../components/NavbarProfile';

// Brand color helper (cohérent avec le reste de l'app)
const BRAND_COLORS: Record<string, string> = {
  Netflix: '#E50914', Spotify: '#1DB954', Apple: '#000000',
  iTunes: '#D60017', PlayStation: '#003791', Xbox: '#107C10',
  Amazon: '#FF9900', Google: '#01875F', Steam: '#171A21',
  Roblox: '#00A2FF', Nintendo: '#E60012', Disney: '#0E47A1',
  Daywatch: '#44A08D',
};
function getBrandColor(name: string): string {
  if (!name) return '#0F172A';
  for (const [key, color] of Object.entries(BRAND_COLORS)) {
    if (name.toLowerCase().includes(key.toLowerCase())) return color;
  }
  const palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  const idx = ((hash % palette.length) + palette.length) % palette.length;
  return palette[idx];
}

export default function CartScreen() {
  const router = useRouter();
  const { cartItems, removeFromCart, updateQuantity, cartTotal, clearCart } = useCart();
  const { showAlert } = useAlert();
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [refreshing, setRefreshing] = useState(false);
  const [isLoginModalVisible, setIsLoginModalVisible] = useState(false);

  const onRefresh = () => {
    setRefreshing(true);
    // Simulate refresh (or reload cart from storage/server)
    setTimeout(() => {
      setRefreshing(false);
    }, 1000);
  };

  const handleCheckout = async () => {
    try {
      const token = await AsyncStorage.getItem('token');
      if (!token) {
        setIsLoginModalVisible(true);
        return;
      }
      router.push('/payment');
    } catch (error) {
      console.error('Error checking auth:', error);
      setIsLoginModalVisible(true);
    }
  };

  const handleClearCart = () => {
    showAlert({
      title: 'Vider le panier ?',
      message: 'Tu vas perdre tous les articles que tu as ajoutés. Cette action est irréversible.',
      variant: 'warning',
      buttons: [
        { label: 'Annuler', variant: 'secondary' },
        { label: 'Vider le panier', variant: 'danger', onPress: clearCart },
      ],
    });
  };

  // Frais offerts (cohérent avec la maquette web)

  if (cartItems.length === 0) {
    return (
      <SafeAreaView className="flex-1 bg-gray-50">
        <StatusBar barStyle="dark-content" backgroundColor="#F9FAFB" />
        <ScrollView 
          contentContainerStyle={{ flex: 1 }}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#1F2937']} tintColor="#1F2937" />
          }
        >
          <EmptyState 
             title="Votre panier est vide"
             message="Découvrez nos cartes cadeaux et rechargez votre compte en quelques clics."
             onRefresh={() => router.push('/boutique')}
             buttonText="Aller à la boutique"
          />
        </ScrollView>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView className="flex-1" style={{ backgroundColor: '#FAFAF7' }}>
      <StatusBar barStyle="light-content" backgroundColor="#0F172A" />

      {/* Header sombre style web */}
      <View style={{ backgroundColor: '#0F172A' }} className="px-4 pt-2 pb-5 relative overflow-hidden">
        {/* Halo */}
        <View style={{ position: 'absolute', top: -50, right: -50, width: 220, height: 220, borderRadius: 110, backgroundColor: 'rgba(78,205,196,0.12)' }} />

        <View className="flex-row items-center justify-between mb-4" style={{ position: 'relative' }}>
          <View>
            <Text style={{ letterSpacing: 1.5 }} className="text-[#5EEAD4] text-[10px] font-bold uppercase">Panier</Text>
            <Text className="text-xl font-black text-white tracking-tight mt-0.5">Ma sélection</Text>
          </View>
          <View className="flex-row items-center gap-2">
            <View className="flex-row items-center rounded-lg p-0.5 border" style={{ backgroundColor: 'rgba(255,255,255,0.06)', borderColor: 'rgba(255,255,255,0.10)' }}>
              <TouchableOpacity onPress={() => setViewMode('grid')} className="px-2 py-1.5 rounded-md" style={{ backgroundColor: viewMode === 'grid' ? '#44A08D' : 'transparent' }}>
                <Squares2X2Icon size={16} color={viewMode === 'grid' ? 'white' : '#94A3B8'} />
              </TouchableOpacity>
              <TouchableOpacity onPress={() => setViewMode('list')} className="px-2 py-1.5 rounded-md" style={{ backgroundColor: viewMode === 'list' ? '#44A08D' : 'transparent' }}>
                <ListBulletIcon size={16} color={viewMode === 'list' ? 'white' : '#94A3B8'} />
              </TouchableOpacity>
            </View>
            <NavbarProfile />
          </View>
        </View>

        {/* Total card */}
        <View className="rounded-xl px-4 py-3 flex-row items-center justify-between" style={{ backgroundColor: 'rgba(255,255,255,0.06)', borderWidth: 1, borderColor: 'rgba(255,255,255,0.10)' }}>
          <View>
            <Text style={{ letterSpacing: 1 }} className="text-[#94A3B8] text-[9px] font-bold uppercase">Total estimé</Text>
            <Text className="text-white text-xl font-black mt-0.5">{formatFCFA(cartTotal)}</Text>
          </View>
          <View className="flex-row items-center gap-2 px-3 py-2 rounded-lg" style={{ backgroundColor: 'rgba(78,205,196,0.15)', borderWidth: 1, borderColor: 'rgba(78,205,196,0.30)' }}>
            <ShoppingBagIcon size={14} color="#4ECDC4" />
            <Text className="text-[#4ECDC4] text-xs font-bold">{cartItems.length} article{cartItems.length > 1 ? 's' : ''}</Text>
          </View>
        </View>
      </View>

      <ScrollView
        className="flex-1 px-4 pt-4"
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#44A08D']} tintColor="#44A08D" />
        }
      >
        {/* Toolbar mini : count + clear */}
        <View className="flex-row items-center justify-between mb-3">
          <Text style={{ letterSpacing: 1 }} className="text-[10px] uppercase font-black tracking-wider text-slate-400">
            {cartItems.length} {cartItems.length > 1 ? 'articles' : 'article'}
          </Text>
          <TouchableOpacity onPress={handleClearCart} className="flex-row items-center" style={{ gap: 4, paddingVertical: 4, paddingHorizontal: 10, borderRadius: 9999, backgroundColor: '#FEF2F2', borderWidth: 1, borderColor: '#FECACA' }}>
            <TrashIcon size={11} color="#BE123C" />
            <Text style={{ color: '#BE123C', fontSize: 11, fontWeight: '700' }}>Vider</Text>
          </TouchableOpacity>
        </View>

        {/* ============================ CART ITEMS ============================ */}
        <View className="pb-32">
          {viewMode === 'grid' ? (
            // ============= Grid =============
            <View className="flex-row flex-wrap justify-between">
              {cartItems.map((item: any) => {
                const color = item.color || getBrandColor(item.brandName || item.name);
                return (
                  <View
                    key={item.id}
                    className="w-[48%] mb-3 bg-white rounded-2xl overflow-hidden border border-slate-200"
                    style={{ shadowColor: '#0F172A', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}
                  >
                    {/* Visuel carte premium */}
                    <View style={{ backgroundColor: color, height: 110, padding: 12, position: 'relative', overflow: 'hidden' }}>
                      <View style={{ position: 'absolute', top: -25, right: -25, width: 90, height: 90, borderRadius: 45, backgroundColor: 'rgba(255,255,255,0.18)' }} />
                      <View style={{ position: 'absolute', bottom: -18, left: -18, width: 60, height: 60, borderRadius: 30, backgroundColor: 'rgba(255,255,255,0.10)' }} />

                      <Text style={{ color: 'rgba(255,255,255,0.78)', fontSize: 9, fontWeight: '700', letterSpacing: 1.5, textTransform: 'uppercase' }}>Gift Card</Text>
                      <Text style={{ color: '#FFFFFF', fontSize: 16, fontWeight: '900', letterSpacing: -0.3, marginTop: 2 }} numberOfLines={1}>
                        {(item.brandName || item.name).split(' ')[0]}
                      </Text>

                      {/* Puce gold */}
                      <View style={{ position: 'absolute', bottom: 10, right: 10, width: 28, height: 20, borderRadius: 4, backgroundColor: '#FCD34D', borderWidth: 1, borderColor: 'rgba(255,255,255,0.3)' }} />

                      {/* Delete bubble */}
                      <TouchableOpacity
                        onPress={() => removeFromCart(item.id)}
                        style={{ position: 'absolute', top: 8, right: 8, width: 26, height: 26, borderRadius: 13, backgroundColor: 'rgba(0,0,0,0.30)', alignItems: 'center', justifyContent: 'center' }}
                      >
                        <TrashIcon size={13} color="white" />
                      </TouchableOpacity>
                    </View>

                    {/* Body */}
                    <View className="p-3 pt-5 relative">
                      {/* Logo flottant */}
                      <View style={{ position: 'absolute', top: -18, left: 12, width: 36, height: 36, borderRadius: 12, backgroundColor: '#FFFFFF', borderWidth: 1, borderColor: '#E2E8F0', alignItems: 'center', justifyContent: 'center', overflow: 'hidden', shadowColor: '#0F172A', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.1, shadowRadius: 4, elevation: 3 }}>
                        {item.image ? (
                          <Image source={{ uri: item.image }} style={{ width: '85%', height: '85%' }} resizeMode="contain" />
                        ) : (
                          <View style={{ width: '100%', height: '100%', backgroundColor: color, alignItems: 'center', justifyContent: 'center' }}>
                            <Text style={{ color: '#FFFFFF', fontSize: 14, fontWeight: '900' }}>{(item.brandName || item.name).charAt(0)}</Text>
                          </View>
                        )}
                      </View>

                      <Text className="text-xs font-bold text-slate-900 mt-2" numberOfLines={1}>{item.name}</Text>
                      <Text className="text-sm font-black text-slate-900 mt-0.5" style={{ fontVariant: ['tabular-nums'] }}>{formatFCFA(item.price * item.quantity)}</Text>

                      {/* Quantity controls */}
                      <View className="flex-row items-center justify-between rounded-lg mt-2" style={{ backgroundColor: '#F8FAFC', borderWidth: 1, borderColor: '#E2E8F0', padding: 3 }}>
                        <TouchableOpacity onPress={() => updateQuantity(item.id, -1)} style={{ width: 24, height: 24, backgroundColor: '#FFFFFF', borderRadius: 6, alignItems: 'center', justifyContent: 'center' }}>
                          <MinusIcon size={10} color="#475569" />
                        </TouchableOpacity>
                        <Text className="text-xs font-black text-slate-900">{item.quantity}</Text>
                        <TouchableOpacity onPress={() => updateQuantity(item.id, 1)} style={{ width: 24, height: 24, backgroundColor: '#44A08D', borderRadius: 6, alignItems: 'center', justifyContent: 'center' }}>
                          <PlusIcon size={10} color="white" />
                        </TouchableOpacity>
                      </View>
                    </View>
                  </View>
                );
              })}
            </View>
          ) : (
            // ============= List =============
            <View>
              {cartItems.map((item: any) => {
                const color = item.color || getBrandColor(item.brandName || item.name);
                return (
                  <View
                    key={item.id}
                    className="bg-white rounded-2xl mb-3 overflow-hidden border border-slate-200 flex-row"
                    style={{ shadowColor: '#0F172A', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.04, shadowRadius: 6, elevation: 1 }}
                  >
                    {/* Mini-carte gauche */}
                    <View style={{ backgroundColor: color, width: 100, padding: 12, position: 'relative', overflow: 'hidden', alignItems: 'center', justifyContent: 'center' }}>
                      <View style={{ position: 'absolute', top: -20, right: -20, width: 70, height: 70, borderRadius: 35, backgroundColor: 'rgba(255,255,255,0.15)' }} />
                      <View style={{ width: 48, height: 48, borderRadius: 14, backgroundColor: '#FFFFFF', alignItems: 'center', justifyContent: 'center', overflow: 'hidden' }}>
                        {item.image ? (
                          <Image source={{ uri: item.image }} style={{ width: '85%', height: '85%' }} resizeMode="contain" />
                        ) : (
                          <Text style={{ fontSize: 18, fontWeight: '900', color }}>{(item.brandName || item.name).charAt(0)}</Text>
                        )}
                      </View>
                      <Text style={{ color: 'rgba(255,255,255,0.85)', fontSize: 8, fontWeight: '700', letterSpacing: 1, textTransform: 'uppercase', marginTop: 6 }}>Gift Card</Text>
                    </View>

                    {/* Détails droite */}
                    <View className="flex-1 p-3 justify-between">
                      <View className="flex-row items-start justify-between">
                        <View className="flex-1 mr-2">
                          <Text style={{ letterSpacing: 1 }} className="text-[9px] uppercase font-black text-slate-400">{item.brandName || item.name}</Text>
                          <Text className="text-sm font-bold text-slate-900 mt-0.5" numberOfLines={1}>{item.name}</Text>
                        </View>
                        <TouchableOpacity onPress={() => removeFromCart(item.id)} style={{ width: 28, height: 28, borderRadius: 8, backgroundColor: '#FEF2F2', alignItems: 'center', justifyContent: 'center' }}>
                          <TrashIcon size={14} color="#BE123C" />
                        </TouchableOpacity>
                      </View>

                      <View className="flex-row items-end justify-between">
                        <View className="flex-row items-center rounded-lg" style={{ backgroundColor: '#F8FAFC', borderWidth: 1, borderColor: '#E2E8F0', padding: 3 }}>
                          <TouchableOpacity onPress={() => updateQuantity(item.id, -1)} style={{ width: 28, height: 28, backgroundColor: '#FFFFFF', borderRadius: 6, alignItems: 'center', justifyContent: 'center' }}>
                            <MinusIcon size={12} color="#475569" />
                          </TouchableOpacity>
                          <Text className="text-sm font-black text-slate-900" style={{ width: 30, textAlign: 'center' }}>{item.quantity}</Text>
                          <TouchableOpacity onPress={() => updateQuantity(item.id, 1)} style={{ width: 28, height: 28, backgroundColor: '#44A08D', borderRadius: 6, alignItems: 'center', justifyContent: 'center' }}>
                            <PlusIcon size={12} color="white" />
                          </TouchableOpacity>
                        </View>

                        <View className="items-end">
                          <Text style={{ letterSpacing: 1 }} className="text-[9px] uppercase font-black text-slate-400">Total</Text>
                          <Text className="text-base font-black text-slate-900" style={{ fontVariant: ['tabular-nums'] }}>{formatFCFA(item.price * item.quantity)}</Text>
                        </View>
                      </View>
                    </View>
                  </View>
                );
              })}
            </View>
          )}
        </View>
      </ScrollView>

      {/* ============================ FOOTER : Récap + CTA ============================ */}
      <View style={{
        backgroundColor: '#FFFFFF',
        borderTopWidth: 1, borderTopColor: '#F1F5F9',
        paddingTop: 14, paddingHorizontal: 16, paddingBottom: 28,
        shadowColor: '#0F172A', shadowOffset: { width: 0, height: -4 }, shadowOpacity: 0.06, shadowRadius: 8,
      }}>
        {/* Récap lignes */}
        <View style={{ gap: 6, marginBottom: 12 }}>
          <View className="flex-row justify-between">
            <Text className="text-sm text-slate-500">Sous-total</Text>
            <Text className="text-sm font-semibold text-slate-700">{formatFCFA(cartTotal)}</Text>
          </View>
          <View className="flex-row justify-between">
            <Text className="text-sm text-slate-500">Frais de service</Text>
            <Text className="text-sm font-semibold text-emerald-600">Offerts</Text>
          </View>
        </View>

        {/* Total card sombre */}
        <View style={{
          backgroundColor: '#0F172A',
          borderRadius: 14,
          paddingHorizontal: 16, paddingVertical: 14,
          marginBottom: 12,
          flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
          position: 'relative', overflow: 'hidden',
        }}>
          <View style={{ position: 'absolute', top: -30, right: -30, width: 110, height: 110, borderRadius: 55, backgroundColor: 'rgba(78,205,196,0.18)' }} />
          <View style={{ position: 'relative' }}>
            <Text style={{ letterSpacing: 1.2 }} className="text-[#5EEAD4] text-[10px] font-bold uppercase">À payer</Text>
            <Text className="text-white text-2xl font-black mt-0.5" style={{ fontVariant: ['tabular-nums'] }}>{formatFCFA(cartTotal)}</Text>
          </View>
          <View className="flex-row items-center" style={{ gap: 6, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 9999, backgroundColor: 'rgba(78,205,196,0.15)', borderWidth: 1, borderColor: 'rgba(78,205,196,0.30)' }}>
            <ShoppingBagIcon size={12} color="#4ECDC4" />
            <Text className="text-[#4ECDC4] text-[10px] font-bold">{cartItems.length} {cartItems.length > 1 ? 'articles' : 'article'}</Text>
          </View>
        </View>

        {/* CTA Checkout */}
        <TouchableOpacity
          onPress={handleCheckout}
          activeOpacity={0.9}
          style={{
            backgroundColor: '#44A08D',
            borderRadius: 14,
            paddingVertical: 15,
            flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
            gap: 8,
            shadowColor: '#44A08D', shadowOffset: { width: 0, height: 12 }, shadowOpacity: 0.45, shadowRadius: 18, elevation: 6,
          }}
        >
          <Text className="text-white font-black text-base">Passer la commande</Text>
          <ArrowRightIcon size={18} color="white" />
        </TouchableOpacity>
      </View>
      
      <LoginRequiredModal 
        visible={isLoginModalVisible} 
        onClose={() => setIsLoginModalVisible(false)}
        message="Vous devez être connecté pour passer une commande."
      />
    </SafeAreaView>
  );
}
