import { View, Text, ScrollView, RefreshControl, TouchableOpacity, ActivityIndicator, Animated, Easing } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useState, useCallback, useMemo, useRef, useEffect } from 'react';
import { useRouter, useFocusEffect } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getToken } from '../../services/tokenStore';
import {
  ArrowLeftIcon, ShoppingBagIcon, ClipboardDocumentListIcon,
  CheckCircleIcon, ChevronRightIcon, CreditCardIcon, PlusIcon,
  ArrowPathIcon,
} from 'react-native-heroicons/outline';
import { OrderService, Order } from '../../services/order';
import EmptyState from '../../components/EmptyState';
import { useAlert } from '../../context/AlertContext';

const BRAND_COLORS: Record<string, string> = {
  Netflix: '#E50914', Spotify: '#1DB954', Apple: '#000000',
  iTunes: '#D60017', PlayStation: '#003791', Xbox: '#107C10',
  Amazon: '#FF9900', Google: '#01875F', Steam: '#171A21',
  Roblox: '#00A2FF', Nintendo: '#E60012', Disney: '#0E47A1',
};
function getBrandColor(name: string): string {
  if (!name) return '#475569';
  for (const [key, color] of Object.entries(BRAND_COLORS)) {
    if (name.toLowerCase().includes(key.toLowerCase())) return color;
  }
  const palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  const idx = ((hash % palette.length) + palette.length) % palette.length;
  return palette[idx];
}

const STATUS_INFO: Record<string, { label: string; bg: string; text: string; border: string }> = {
  pending:    { label: 'En attente',    bg: '#FEF3C7', text: '#B45309', border: '#FDE68A' },
  processing: { label: 'En traitement', bg: '#E0F2FE', text: '#0369A1', border: '#BAE6FD' },
  completed:  { label: 'Terminée',      bg: '#D1FAE5', text: '#047857', border: '#A7F3D0' },
  cancelled:  { label: 'Annulée',       bg: '#FFE4E6', text: '#BE123C', border: '#FECDD3' },
  failed:     { label: 'Échouée',       bg: '#FFE4E6', text: '#BE123C', border: '#FECDD3' },
  shipped:    { label: 'Expédiée',      bg: '#E0F2FE', text: '#0369A1', border: '#BAE6FD' },
  delivered:  { label: 'Livrée',        bg: '#D1FAE5', text: '#047857', border: '#A7F3D0' },
  refunded:   { label: 'Remboursée',    bg: '#F1F5F9', text: '#475569', border: '#E2E8F0' },
};
const PAY_INFO: Record<string, { label: string; bg: string; text: string; border: string }> = {
  completed:  { label: 'Payé',       bg: '#D1FAE5', text: '#047857', border: '#A7F3D0' },
  paid:       { label: 'Payé',       bg: '#D1FAE5', text: '#047857', border: '#A7F3D0' },
  pending:    { label: 'En attente', bg: '#FEF3C7', text: '#B45309', border: '#FDE68A' },
  processing: { label: 'En cours',   bg: '#E0F2FE', text: '#0369A1', border: '#BAE6FD' },
  failed:     { label: 'Échoué',     bg: '#FFE4E6', text: '#BE123C', border: '#FECDD3' },
};

interface OrderRowProps {
  order: Order;
  onPress: () => void;
  delay: number;
  retryingId: number | null;
  onRetry: (orderId: number) => void;
}

function OrderRow({ order, onPress, delay, retryingId, onRetry }: OrderRowProps) {
  const opacity = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(12)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(opacity, { toValue: 1, duration: 350, delay, useNativeDriver: true, easing: Easing.out(Easing.cubic) }),
      Animated.timing(translateY, { toValue: 0, duration: 350, delay, useNativeDriver: true, easing: Easing.out(Easing.cubic) }),
    ]).start();
  }, []);

  const statusInfo = STATUS_INFO[order.status] || { label: order.status || '—', bg: '#F1F5F9', text: '#475569', border: '#E2E8F0' };
  const payInfo = PAY_INFO[order.payment_status] || { label: order.payment_status || '—', bg: '#F1F5F9', text: '#475569', border: '#E2E8F0' };
  const itemsCount = order.order_items?.length || 0;
  const firstItem = order.order_items?.[0];
  const brandColor = getBrandColor(firstItem?.name || '');
  const brandInitial = (firstItem?.name || '?').charAt(0).toUpperCase();

  // Bouton "Relancer la récupération" : commande PAYÉE mais cartes non livrées.
  // Mirror exact de la logique web /orders (orders.blade.php).
  const isPaid       = ['completed', 'paid'].includes(order.payment_status);
  const isDelivered  = order.status === 'completed';
  const canRetry     = isPaid && !isDelivered && order.status !== 'cancelled' && order.status !== 'refunded';
  const isRetrying   = retryingId === order.id;

  return (
    <Animated.View style={{ opacity, transform: [{ translateY }] }}>
      <TouchableOpacity
        onPress={onPress}
        className="bg-white rounded-2xl border border-slate-200 mb-3 p-3.5"
        activeOpacity={0.85}
        style={{ shadowColor: '#0F172A', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 6, elevation: 1 }}
      >
        <View className="flex-row items-start gap-3">
          {/* Mini-carte brand color */}
          <View
            style={{ backgroundColor: brandColor, width: 46, height: 46 }}
            className="rounded-xl items-center justify-center relative overflow-hidden"
          >
            <View style={{ position: 'absolute', top: -10, right: -10, width: 36, height: 36, borderRadius: 18, backgroundColor: 'rgba(255,255,255,0.20)' }} />
            <Text className="text-white text-xl font-black" style={{ position: 'relative' }}>{brandInitial}</Text>
          </View>

          {/* Détails */}
          <View className="flex-1 min-w-0">
            <View className="flex-row items-center justify-between mb-1">
              <Text className="font-mono text-xs text-slate-400">Commande</Text>
              <Text className="text-[10px] text-slate-400">
                {new Date(order.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })}
              </Text>
            </View>
            <Text className="font-mono text-sm font-black text-slate-900" numberOfLines={1}>
              #{order.order_number}
            </Text>

            {firstItem && (
              <Text className="text-xs text-slate-600 mt-1" numberOfLines={1}>
                {firstItem.name}{itemsCount > 1 ? ` + ${itemsCount - 1} autre${itemsCount - 1 > 1 ? 's' : ''}` : ''}
              </Text>
            )}

            {/* Badges */}
            <View className="flex-row items-center gap-1.5 mt-2 flex-wrap">
              <View className="flex-row items-center gap-1 px-2 py-0.5 rounded-full border" style={{ backgroundColor: statusInfo.bg, borderColor: statusInfo.border }}>
                <View className="w-1.5 h-1.5 rounded-full" style={{ backgroundColor: statusInfo.text }} />
                <Text className="text-[10px] font-bold" style={{ color: statusInfo.text }}>{statusInfo.label}</Text>
              </View>
              <View className="px-2 py-0.5 rounded-full border" style={{ backgroundColor: payInfo.bg, borderColor: payInfo.border }}>
                <Text className="text-[10px] font-bold" style={{ color: payInfo.text }}>{payInfo.label}</Text>
              </View>
              <Text className="text-[10px] text-slate-400 font-medium">· {itemsCount} article{itemsCount > 1 ? 's' : ''}</Text>
            </View>
          </View>

          {/* Total + chevron */}
          <View className="items-end">
            <Text style={{ letterSpacing: 1 }} className="text-[9px] uppercase font-bold text-slate-400">Total</Text>
            <Text className="text-base font-black text-slate-900 mt-0.5" numberOfLines={1}>
              {OrderService.formatAmount(order.total_amount)}
            </Text>
            <View className="w-7 h-7 rounded-lg bg-slate-50 border border-slate-200 items-center justify-center mt-2">
              <ChevronRightIcon size={14} color="#94A3B8" />
            </View>
          </View>
        </View>

        {/* Bouton "Relancer la récupération" — quand commande payée mais cartes pas livrées */}
        {canRetry && (
          <View style={{ marginTop: 12, paddingTop: 12, borderTopWidth: 1, borderTopColor: '#F1F5F9' }}>
            <TouchableOpacity
              onPress={(e) => { e.stopPropagation?.(); onRetry(order.id); }}
              disabled={isRetrying}
              activeOpacity={0.85}
              style={{
                flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
                gap: 8, paddingVertical: 10, paddingHorizontal: 14,
                borderRadius: 10, borderWidth: 1.5,
                backgroundColor: isRetrying ? '#F1F5F9' : '#FEF3C7',
                borderColor:     isRetrying ? '#E2E8F0' : '#FDE68A',
              }}
            >
              {isRetrying ? (
                <ActivityIndicator size="small" color="#B45309" />
              ) : (
                <ArrowPathIcon size={14} color="#B45309" />
              )}
              <Text style={{ fontSize: 12, fontWeight: '900', color: '#B45309' }}>
                {isRetrying ? 'Récupération en cours…' : 'Relancer la récupération des cartes'}
              </Text>
            </TouchableOpacity>
            <Text style={{ fontSize: 10, color: '#94A3B8', textAlign: 'center', marginTop: 6 }}>
              Paiement validé — la livraison de tes cartes a échoué temporairement.
            </Text>
          </View>
        )}
      </TouchableOpacity>
    </Animated.View>
  );
}

export default function OrdersScreen() {
  const router = useRouter();
  const { showAlert } = useAlert();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [retryingId, setRetryingId] = useState<number | null>(null);

  const loadOrders = async () => {
    try {
      const token = await getToken();
      if (!token) { setLoading(false); return; }
      const data = await OrderService.getOrders();
      setOrders(data);
    } catch (e) { console.error('Error loading orders:', e); }
    finally { setLoading(false); setRefreshing(false); }
  };

  useFocusEffect(useCallback(() => { loadOrders(); }, []));

  const onRefresh = () => { setRefreshing(true); loadOrders(); };

  const handleRetry = async (orderId: number) => {
    setRetryingId(orderId);
    try {
      const res = await OrderService.retryDelivery(orderId);
      if (res.success) {
        if (res.cards_pending) {
          showAlert({
            title: 'Récupération relancée',
            message: res.message ?? "L'API du fournisseur est temporairement indisponible. Tes cartes seront livrées dès que possible.",
            variant: 'info',
          });
        } else if (res.already_delivered) {
          showAlert({
            title: 'Déjà livrée',
            message: 'Cette commande a déjà été livrée. Vérifie tes cartes dans le wallet.',
            variant: 'info',
          });
        } else {
          const n = res.cards?.length ?? 0;
          showAlert({
            title: 'Cartes livrées !',
            message: `Tes ${n} carte${n > 1 ? 's sont' : ' est'} disponibles dans ton wallet.`,
            variant: 'success',
            buttons: [
              { label: 'Voir mes cartes', variant: 'primary', onPress: () => router.push('/(tabs)/wallet') },
              { label: 'Plus tard',       variant: 'secondary' },
            ],
          });
        }
        await loadOrders();
      } else {
        showAlert({
          title: 'Échec',
          message: res.message ?? 'Impossible de relancer la livraison pour le moment.',
          variant: 'error',
        });
      }
    } finally {
      setRetryingId(null);
    }
  };

  const stats = useMemo(() => ({
    total: orders.length,
    completed: orders.filter(o => o.status === 'completed').length,
    // total_amount peut arriver en string (Laravel cast decimal:2) — coercion
    // explicite sinon le `+` fait une concaténation et le total finit en NaN.
    spent: orders.reduce((sum, o) => {
      const n = Number(o.total_amount);
      return sum + (Number.isFinite(n) ? n : 0);
    }, 0),
  }), [orders]);

  return (
    <SafeAreaView className="flex-1" style={{ backgroundColor: '#FAFAF7' }}>
      {/* Header sombre style web */}
      <View style={{ backgroundColor: '#0F172A' }} className="px-4 pt-2 pb-5 relative overflow-hidden">
        {/* Halo */}
        <View style={{ position: 'absolute', top: -50, right: -50, width: 220, height: 220, borderRadius: 110, backgroundColor: 'rgba(78,205,196,0.12)' }} />

        <View className="flex-row items-center justify-between" style={{ position: 'relative' }}>
          <View className="flex-row items-center flex-1 min-w-0">
            <TouchableOpacity onPress={() => router.back()} className="mr-3 w-9 h-9 rounded-xl items-center justify-center" style={{ backgroundColor: 'rgba(255,255,255,0.08)' }}>
              <ArrowLeftIcon size={18} color="#FFFFFF" />
            </TouchableOpacity>
            <View>
              <Text style={{ letterSpacing: 1.5 }} className="text-[#5EEAD4] text-[10px] font-bold uppercase">Historique</Text>
              <Text className="text-xl font-black text-white tracking-tight mt-0.5">Mes commandes</Text>
            </View>
          </View>
          <TouchableOpacity
            onPress={() => router.push('/(tabs)/wallet')}
            className="px-3 py-2 rounded-xl flex-row items-center gap-1.5"
            style={{ backgroundColor: 'rgba(78,205,196,0.15)', borderWidth: 1, borderColor: 'rgba(78,205,196,0.3)' }}
          >
            <CreditCardIcon size={14} color="#4ECDC4" />
            <Text className="text-[#4ECDC4] text-xs font-bold">Mes cartes</Text>
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={{ padding: 16, paddingBottom: 100 }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#44A08D']} tintColor="#44A08D" />}
      >
        {/* Stats — premium */}
        {!loading && orders.length > 0 && (
          <View className="mb-4">
            <View className="flex-row gap-2 mb-2">
              <View className="flex-1 bg-white rounded-2xl border border-slate-200 p-3" style={{ shadowColor: '#0F172A', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.04, shadowRadius: 6, elevation: 1 }}>
                <Text style={{ letterSpacing: 1 }} className="text-[9px] uppercase font-bold text-slate-400 mb-1">Total</Text>
                <Text className="text-2xl font-black text-slate-900">{stats.total}</Text>
              </View>
              <View className="flex-1 bg-white rounded-2xl border border-slate-200 p-3" style={{ shadowColor: '#10B981', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.04, shadowRadius: 6, elevation: 1 }}>
                <Text style={{ letterSpacing: 1 }} className="text-[9px] uppercase font-bold text-slate-400 mb-1">Terminées</Text>
                <Text className="text-2xl font-black text-emerald-600">{stats.completed}</Text>
              </View>
            </View>
            {/* Total dépensé en gradient sombre */}
            <View className="rounded-2xl p-4 relative overflow-hidden" style={{ backgroundColor: '#0F172A' }}>
              <View style={{ position: 'absolute', top: -30, right: -30, width: 110, height: 110, borderRadius: 55, backgroundColor: 'rgba(78,205,196,0.18)' }} />
              <View className="flex-row items-center justify-between" style={{ position: 'relative' }}>
                <View>
                  <Text style={{ letterSpacing: 1.2 }} className="text-[#5EEAD4] text-[10px] font-bold uppercase">Total dépensé</Text>
                  <Text className="text-white text-2xl font-black mt-1">
                    {OrderService.formatAmount(stats.spent)}
                  </Text>
                </View>
                <View className="w-11 h-11 rounded-xl items-center justify-center" style={{ backgroundColor: '#44A08D' }}>
                  <ShoppingBagIcon size={22} color="#FFFFFF" />
                </View>
              </View>
            </View>
          </View>
        )}

        {loading && !refreshing ? (
          <ActivityIndicator size="large" color="#44A08D" className="mt-10" />
        ) : orders.length === 0 ? (
          <View className="bg-white rounded-2xl border border-slate-200 p-10 items-center mt-4">
            <View className="w-16 h-16 rounded-2xl bg-slate-100 items-center justify-center mb-4">
              <ClipboardDocumentListIcon size={32} color="#CBD5E1" />
            </View>
            <Text className="text-base font-bold text-slate-900 mb-2 text-center">Aucune commande pour le moment</Text>
            <Text className="text-xs text-slate-500 text-center mb-4">
              Vous n'avez pas encore passé de commande. Découvrez plus de 300 marques de cartes cadeaux.
            </Text>
            <TouchableOpacity
              onPress={() => router.push('/(tabs)/boutique')}
              className="px-5 py-2.5 rounded-xl flex-row items-center gap-2"
              style={{ backgroundColor: '#44A08D' }}
            >
              <ShoppingBagIcon size={16} color="#FFFFFF" />
              <Text className="text-white font-bold text-sm">Découvrir le catalogue</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <View>
            {orders.map((o, i) => (
              <OrderRow
                key={o.id}
                order={o}
                onPress={() => router.push(`/orders/${o.id}`)}
                delay={(i % 12) * 50}
                retryingId={retryingId}
                onRetry={handleRetry}
              />
            ))}
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}
