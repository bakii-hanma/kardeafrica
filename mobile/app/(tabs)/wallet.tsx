import { View, Text, ScrollView, RefreshControl, TouchableOpacity, ActivityIndicator, TextInput, Image, Animated, Easing, Modal, Pressable } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useState, useCallback, useMemo, useRef, useEffect } from 'react';
import { useRouter, useFocusEffect } from 'expo-router';
import AuthRequired from '../../components/AuthRequired';
import EmptyState from '../../components/EmptyState';
import AsyncStorage from '@react-native-async-storage/async-storage';
import NavbarProfile from '../../components/NavbarProfile';
import * as Clipboard from 'expo-clipboard';
import { CardService, Card } from '../../services/card';
import { OrderService, Order } from '../../services/order';
import { usePasswordGate, isCardsUnlocked } from '../../components/PasswordGate';
import { useAlert } from '../../context/AlertContext';
import {
  CreditCardIcon, MagnifyingGlassIcon, EyeIcon, EyeSlashIcon,
  ClipboardDocumentIcon, CheckIcon, ChevronRightIcon, ShoppingBagIcon,
  ExclamationTriangleIcon, ArrowPathIcon, AdjustmentsHorizontalIcon, BanknotesIcon,
} from 'react-native-heroicons/outline';

const BRAND_COLORS: Record<string, string> = {
  'Netflix': '#E50914', 'Spotify': '#1DB954', 'Apple': '#000000',
  'iTunes': '#D60017', 'PlayStation': '#003791', 'Xbox': '#107C10',
  'Amazon': '#FF9900', 'Google': '#01875F', 'Steam': '#171A21',
  'Nintendo': '#E60012', 'Disney': '#0E47A1', 'StarzPlay': '#7C3AED',
  'Talabat': '#FF5A00', 'HUAWEI': '#C7000B', 'IKEA': '#0058A3',
};

function getBrandColor(name: string): string {
  if (!name) return '#1F2937';
  for (const [key, color] of Object.entries(BRAND_COLORS)) {
    if (name.toLowerCase().includes(key.toLowerCase())) return color;
  }
  const palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  const idx = ((hash % palette.length) + palette.length) % palette.length;
  return palette[idx];
}

function maskString(s: string | null | undefined, min = 8) {
  if (!s) return '';
  return '•'.repeat(Math.max(min, s.length));
}

interface CardRowProps {
  card: Card;
  onRequireUnlock: (cb: () => void) => void;
  delay: number;
}

function CardRow({ card, onRequireUnlock, delay }: CardRowProps) {
  const [codeShown, setCodeShown] = useState(false);
  const [pinShown, setPinShown] = useState(false);
  const [copied, setCopied] = useState<'code' | 'pin' | null>(null);
  const opacity = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(16)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(opacity, { toValue: 1, duration: 400, delay, useNativeDriver: true, easing: Easing.out(Easing.cubic) }),
      Animated.timing(translateY, { toValue: 0, duration: 400, delay, useNativeDriver: true, easing: Easing.out(Easing.cubic) }),
    ]).start();
  }, []);

  const brandColor = getBrandColor(card.brand || card.name || '');
  const brandName = card.brand || (card.name || '').split(' ')[0];
  const cardCode = card.code || '';
  const cardPin = (card as any).pin || '';

  const toggleCode = () => {
    if (codeShown) { setCodeShown(false); return; }
    onRequireUnlock(() => setCodeShown(true));
  };
  const togglePin = () => {
    if (pinShown) { setPinShown(false); return; }
    onRequireUnlock(() => setPinShown(true));
  };
  const copyCode = () => {
    onRequireUnlock(async () => {
      await Clipboard.setStringAsync(cardCode);
      setCopied('code');
      setTimeout(() => setCopied(null), 1800);
    });
  };

  const statusBadge =
    card.status === 'active'  ? { label: 'Active',   bg: 'rgba(16,185,129,0.30)', dot: true } :
    card.status === 'used'    ? { label: 'Utilisée', bg: 'rgba(100,116,139,0.40)', dot: false } :
    card.status === 'expired' ? { label: 'Expirée',  bg: 'rgba(244,63,94,0.40)', dot: false } :
                                 { label: card.status || '—', bg: 'rgba(255,255,255,0.20)', dot: false };

  return (
    <Animated.View style={{ opacity, transform: [{ translateY }] }} className="bg-white rounded-2xl border border-slate-200 mb-3 overflow-hidden" >

      {/* Visuel carte */}
      <View style={{ backgroundColor: brandColor }} className="p-4 relative" >
        <View className="absolute -top-8 -right-8 w-32 h-32 rounded-full" style={{ backgroundColor: 'rgba(255,255,255,0.15)' }} />
        <View className="flex-row items-start justify-between">
          <View className="flex-1">
            <Text style={{ letterSpacing: 1.5 }} className="text-white/80 text-[10px] font-bold uppercase">Gift Card</Text>
            <Text className="text-white text-2xl font-black mt-1" numberOfLines={1}>{brandName}</Text>
          </View>
          <View className="flex-row items-center gap-1 px-2 py-0.5 rounded-full" style={{ backgroundColor: statusBadge.bg }}>
            {statusBadge.dot && <View className="w-1.5 h-1.5 rounded-full bg-emerald-300" />}
            <Text className="text-white text-[10px] font-bold">{statusBadge.label}</Text>
          </View>
        </View>
        <View className="flex-row items-end justify-between mt-6">
          <View>
            <Text style={{ letterSpacing: 1 }} className="text-white/60 text-[9px] font-bold uppercase">Prix payé</Text>
            <Text className="text-white text-lg font-bold mt-0.5">
              {new Intl.NumberFormat('fr-FR').format((card as any).price_paid_xaf ?? card.balance ?? 0)}
              <Text className="text-white/60 text-xs font-normal"> FCFA</Text>
            </Text>
            {((card as any).face_currency && (card as any).face_currency !== 'XAF') && (
              <Text className="text-white/60 text-[10px] mt-0.5">
                {new Intl.NumberFormat('fr-FR').format((card as any).face_value || 0)} {(card as any).face_currency} de crédit
              </Text>
            )}
          </View>
          <View className="w-9 h-6 rounded" style={{ backgroundColor: '#FCD34D', borderWidth: 1, borderColor: 'rgba(255,255,255,0.3)' }} />
        </View>
      </View>

      {/* Détails */}
      <View className="p-4">
        <Text className="text-sm font-semibold text-slate-900 mb-3" numberOfLines={1}>{card.name}</Text>

        {/* Code */}
        <View className="bg-slate-50 border border-slate-200 rounded-lg p-2.5 mb-2">
          <View className="flex-row items-center justify-between mb-1">
            <Text style={{ letterSpacing: 1 }} className="text-[10px] uppercase font-bold text-slate-400">Code</Text>
            <View className="flex-row items-center gap-1">
              <TouchableOpacity onPress={toggleCode} className="p-1.5">
                {codeShown ? <EyeSlashIcon size={14} color="#64748B" /> : <EyeIcon size={14} color="#64748B" />}
              </TouchableOpacity>
              <TouchableOpacity onPress={copyCode} className="p-1.5">
                {copied === 'code' ? <CheckIcon size={14} color="#059669" /> : <ClipboardDocumentIcon size={14} color="#64748B" />}
              </TouchableOpacity>
            </View>
          </View>
          <Text className="text-xs font-bold text-slate-900" style={{ fontFamily: 'monospace', letterSpacing: 1 }} numberOfLines={1}>
            {codeShown ? cardCode : maskString(cardCode)}
          </Text>
        </View>

        {/* PIN + Expiration */}
        <View className="flex-row gap-2">
          {!!cardPin && (
            <View className="flex-1 bg-slate-50 border border-slate-200 rounded-lg p-2.5">
              <View className="flex-row items-center justify-between mb-1">
                <Text style={{ letterSpacing: 1 }} className="text-[10px] uppercase font-bold text-slate-400">PIN</Text>
                <TouchableOpacity onPress={togglePin} className="p-1">
                  {pinShown ? <EyeSlashIcon size={12} color="#64748B" /> : <EyeIcon size={12} color="#64748B" />}
                </TouchableOpacity>
              </View>
              <Text className="text-xs font-bold text-slate-900" style={{ fontFamily: 'monospace' }}>
                {pinShown ? cardPin : maskString(cardPin, cardPin.length || 4)}
              </Text>
            </View>
          )}
          {!!card.expiry_date && (
            <View className="flex-1 bg-slate-50 border border-slate-200 rounded-lg p-2.5">
              <Text style={{ letterSpacing: 1 }} className="text-[10px] uppercase font-bold text-slate-400 mb-1">Expire</Text>
              <Text className="text-xs font-bold text-slate-900">
                {new Date(card.expiry_date).toLocaleDateString('fr-FR')}
              </Text>
            </View>
          )}
        </View>

        {/* Footer */}
        <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-slate-100">
          <Text className="text-[10px] text-slate-500">
            Achetée le {new Date(card.created_at).toLocaleDateString('fr-FR')}
          </Text>
          <TouchableOpacity
            onPress={copyCode}
            className="flex-row items-center gap-1 px-3 py-1.5 rounded-lg"
            style={{ backgroundColor: '#44A08D' }}
          >
            {copied === 'code' ? <CheckIcon size={12} color="#FFFFFF" /> : <ClipboardDocumentIcon size={12} color="#FFFFFF" />}
            <Text className="text-white text-[11px] font-bold">{copied === 'code' ? 'Copié !' : 'Copier'}</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Animated.View>
  );
}

type SortKey = 'latest' | 'oldest' | 'price_desc' | 'price_asc';

export default function WalletScreen() {
  const router = useRouter();
  const { showAlert } = useAlert();
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [cards, setCards] = useState<Card[]>([]);
  const [pendingOrders, setPendingOrders] = useState<Order[]>([]);
  const [retryingId, setRetryingId] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'used' | 'expired'>('all');
  const [sortKey, setSortKey] = useState<SortKey>('latest');
  const [sortOpen, setSortOpen] = useState(false);

  const { requireUnlock, PasswordGateModal } = usePasswordGate();

  const checkAuthAndLoadCards = async () => {
    try {
      const token = await AsyncStorage.getItem('token');
      if (token) { setIsAuthenticated(true); loadCards(); loadPendingOrders(); }
      else { setIsAuthenticated(false); setLoading(false); setCards([]); }
    } catch {
      setIsAuthenticated(false); setLoading(false);
    }
  };

  const loadCards = async () => {
    try {
      if (!refreshing && cards.length === 0) setLoading(true);
      const data = await CardService.getCards();
      setCards(data);
    } catch (e) { console.error('Error loading cards:', e); }
    finally { setLoading(false); setRefreshing(false); }
  };

  // Charge les commandes payées sans cartes livrées (= pour bannière retry,
  // identique au pattern web /cards qui affiche `$pendingOrders`).
  const loadPendingOrders = async () => {
    try {
      const orders = await OrderService.getOrders();
      const pending = orders.filter(o =>
        ['completed', 'paid'].includes(o.payment_status) && o.status !== 'completed'
      );
      setPendingOrders(pending);
    } catch (e) {
      // best-effort, on ignore en cas d'erreur
    }
  };

  useFocusEffect(useCallback(() => { checkAuthAndLoadCards(); }, []));

  const onRefresh = () => { setRefreshing(true); checkAuthAndLoadCards(); };

  const handleRetry = async (orderId: number) => {
    setRetryingId(orderId);
    try {
      const res = await OrderService.retryDelivery(orderId);
      if (res.success) {
        if (res.cards_pending) {
          showAlert({ title: 'Récupération relancée', message: res.message ?? "L'API du fournisseur est temporairement indisponible.", variant: 'info' });
        } else {
          const n = res.cards?.length ?? 0;
          showAlert({
            title: 'Cartes livrées !',
            message: `Tes ${n} carte${n > 1 ? 's sont' : ' est'} maintenant disponibles.`,
            variant: 'success',
          });
        }
        await loadCards();
        await loadPendingOrders();
      } else {
        showAlert({ title: 'Échec', message: res.message ?? 'Impossible de relancer la livraison.', variant: 'error' });
      }
    } finally {
      setRetryingId(null);
    }
  };

  const filteredCards = useMemo(() => {
    let list = cards.filter(card => {
      const q = searchQuery.toLowerCase();
      const matchSearch = card.name.toLowerCase().includes(q) || (card.brand && card.brand.toLowerCase().includes(q));
      const matchStatus = statusFilter === 'all' || card.status === statusFilter;
      return matchSearch && matchStatus;
    });

    // Tri (= mêmes options que le web /cards)
    const priceOf = (c: Card) => Number((c as any).price_paid_xaf ?? c.balance ?? 0);
    const dateOf  = (c: Card) => new Date(c.created_at).getTime();
    if (sortKey === 'oldest')      list = [...list].sort((a, b) => dateOf(a) - dateOf(b));
    else if (sortKey === 'price_desc') list = [...list].sort((a, b) => priceOf(b) - priceOf(a));
    else if (sortKey === 'price_asc')  list = [...list].sort((a, b) => priceOf(a) - priceOf(b));
    else                            list = [...list].sort((a, b) => dateOf(b) - dateOf(a));

    return list;
  }, [cards, searchQuery, statusFilter, sortKey]);

  const stats = useMemo(() => ({
    total: cards.length,
    active: cards.filter(c => c.status === 'active').length,
    used: cards.filter(c => c.status === 'used').length,
    valueXAF: cards.reduce((sum, c) => {
      const n = Number((c as any).price_paid_xaf ?? c.balance ?? 0);
      return sum + (Number.isFinite(n) ? n : 0);
    }, 0),
  }), [cards]);

  const SORT_LABELS: Record<SortKey, string> = {
    latest:     'Plus récentes',
    oldest:     'Plus anciennes',
    price_desc: 'Valeur décroissante',
    price_asc:  'Valeur croissante',
  };

  if (!isAuthenticated) {
    return (
      <SafeAreaView className="flex-1" style={{ backgroundColor: '#FAFAF7' }}>
        <View style={{ backgroundColor: '#0F172A' }} className="px-4 pt-2 pb-5 relative overflow-hidden">
          <View style={{ position: 'absolute', top: -50, right: -50, width: 220, height: 220, borderRadius: 110, backgroundColor: 'rgba(78,205,196,0.12)' }} />
          <View className="flex-row justify-between items-center">
            <Text className="text-xl font-black text-white tracking-tight">Mes cartes</Text>
            <NavbarProfile />
          </View>
        </View>
        <AuthRequired message="Connectez-vous pour voir vos cartes achetées" />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView className="flex-1" style={{ backgroundColor: '#FAFAF7' }}>
      {/* Header sombre style web */}
      <View style={{ backgroundColor: '#0F172A' }} className="px-4 pt-2 pb-4 relative overflow-hidden">
        {/* Halo */}
        <View style={{ position: 'absolute', top: -50, right: -50, width: 220, height: 220, borderRadius: 110, backgroundColor: 'rgba(78,205,196,0.12)' }} />

        <View className="flex-row justify-between items-center mb-3">
          <View>
            <Text style={{ letterSpacing: 1.5 }} className="text-[#5EEAD4] text-[10px] font-bold uppercase">Wallet</Text>
            <Text className="text-xl font-black text-white tracking-tight mt-0.5">Mes cartes</Text>
          </View>
          <NavbarProfile />
        </View>

        {/* Search */}
        <View className="flex-row items-center rounded-xl px-3 py-2.5 border" style={{ backgroundColor: 'rgba(255,255,255,0.06)', borderColor: 'rgba(255,255,255,0.1)' }}>
          <MagnifyingGlassIcon size={16} color="#94A3B8" />
          <TextInput
            className="flex-1 ml-2 text-sm text-white"
            placeholder="Rechercher une carte…"
            placeholderTextColor="#64748B"
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
        </View>

        {/* Filter chips */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} className="mt-3" contentContainerStyle={{ gap: 6 }}>
          {[
            { v: 'all',     label: 'Toutes' },
            { v: 'active',  label: 'Actives' },
            { v: 'used',    label: 'Utilisées' },
            { v: 'expired', label: 'Expirées' },
          ].map(f => (
            <TouchableOpacity
              key={f.v}
              onPress={() => setStatusFilter(f.v as any)}
              className="px-3.5 py-1.5 rounded-full border"
              style={{
                backgroundColor: statusFilter === f.v ? '#44A08D' : 'transparent',
                borderColor: statusFilter === f.v ? '#44A08D' : 'rgba(255,255,255,0.15)',
              }}
            >
              <Text className="text-xs font-bold" style={{ color: statusFilter === f.v ? '#FFFFFF' : '#CBD5E1' }}>{f.label}</Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      </View>

      <ScrollView
        contentContainerStyle={{ padding: 16, paddingBottom: 100 }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#44A08D']} tintColor="#44A08D" />}
      >
        {/* ===== Bannière commandes payées sans cartes (= web /cards) ===== */}
        {pendingOrders.length > 0 && pendingOrders.map(order => (
          <View key={order.id} className="mb-3 rounded-2xl overflow-hidden border" style={{ backgroundColor: '#FFFBEB', borderColor: '#FDE68A' }}>
            <View className="p-4">
              <View className="flex-row items-start gap-3 mb-3">
                <View className="w-10 h-10 rounded-xl items-center justify-center" style={{ backgroundColor: '#FEF3C7' }}>
                  <ExclamationTriangleIcon size={20} color="#B45309" />
                </View>
                <View className="flex-1 min-w-0">
                  <Text className="font-black text-[13px]" style={{ color: '#78350F' }}>
                    Commande <Text style={{ fontFamily: 'monospace' }}>#{order.order_number}</Text> payée mais cartes non livrées
                  </Text>
                  <Text className="text-[11px] mt-0.5" style={{ color: '#92400E' }}>
                    {order.order_items?.length ?? 0} article{(order.order_items?.length ?? 0) > 1 ? 's' : ''} ·{' '}
                    {OrderService.formatAmount(order.total_amount)} · Le fournisseur n'a pas répondu lors du paiement.
                  </Text>
                </View>
              </View>
              <TouchableOpacity
                onPress={() => handleRetry(order.id)}
                disabled={retryingId === order.id}
                activeOpacity={0.85}
                style={{
                  flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
                  gap: 8, paddingVertical: 10, paddingHorizontal: 14,
                  borderRadius: 10,
                  backgroundColor: retryingId === order.id ? '#FDE68A' : '#B45309',
                }}
              >
                {retryingId === order.id ? (
                  <ActivityIndicator size="small" color="#78350F" />
                ) : (
                  <ArrowPathIcon size={14} color="white" />
                )}
                <Text className="font-black text-[12px]" style={{ color: retryingId === order.id ? '#78350F' : 'white' }}>
                  {retryingId === order.id ? 'Récupération…' : 'Relancer la livraison'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        ))}

        {/* ===== Stats : 4 cartes (Total / Actives / Utilisées / Valeur) — match web ===== */}
        {!loading && cards.length > 0 && (
          <View className="mb-4">
            <View className="flex-row gap-2">
              {/* Total */}
              <View className="flex-1 bg-white rounded-xl border border-slate-200 p-3" style={{ shadowColor: '#0F172A', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.04, shadowRadius: 6, elevation: 1 }}>
                <View className="flex-row items-center gap-1.5 mb-1">
                  <View className="w-7 h-7 rounded-lg items-center justify-center" style={{ backgroundColor: '#F0FDFA' }}>
                    <CreditCardIcon size={14} color="#44A08D" />
                  </View>
                </View>
                <Text style={{ letterSpacing: 1 }} className="text-[9px] uppercase font-bold text-slate-400 mb-0.5">Total</Text>
                <Text className="text-xl font-black text-slate-900">{stats.total}</Text>
              </View>
              {/* Actives */}
              <View className="flex-1 bg-white rounded-xl border border-slate-200 p-3" style={{ shadowColor: '#0F172A', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.04, shadowRadius: 6, elevation: 1 }}>
                <View className="flex-row items-center gap-1.5 mb-1">
                  <View className="w-7 h-7 rounded-lg items-center justify-center" style={{ backgroundColor: '#ECFDF5' }}>
                    <CheckIcon size={14} color="#059669" />
                  </View>
                </View>
                <Text style={{ letterSpacing: 1 }} className="text-[9px] uppercase font-bold text-slate-400 mb-0.5">Actives</Text>
                <Text className="text-xl font-black text-emerald-600">{stats.active}</Text>
              </View>
              {/* Utilisées */}
              <View className="flex-1 bg-white rounded-xl border border-slate-200 p-3" style={{ shadowColor: '#0F172A', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.04, shadowRadius: 6, elevation: 1 }}>
                <View className="flex-row items-center gap-1.5 mb-1">
                  <View className="w-7 h-7 rounded-lg items-center justify-center" style={{ backgroundColor: '#F1F5F9' }}>
                    <CheckIcon size={14} color="#64748B" />
                  </View>
                </View>
                <Text style={{ letterSpacing: 1 }} className="text-[9px] uppercase font-bold text-slate-400 mb-0.5">Utilisées</Text>
                <Text className="text-xl font-black text-slate-500">{stats.used}</Text>
              </View>
              {/* Valeur (FCFA) */}
              <View className="flex-1 bg-white rounded-xl border border-slate-200 p-3" style={{ shadowColor: '#0F172A', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.04, shadowRadius: 6, elevation: 1 }}>
                <View className="flex-row items-center gap-1.5 mb-1">
                  <View className="w-7 h-7 rounded-lg items-center justify-center" style={{ backgroundColor: '#FFFBEB' }}>
                    <BanknotesIcon size={14} color="#D97706" />
                  </View>
                </View>
                <Text style={{ letterSpacing: 1 }} className="text-[9px] uppercase font-bold text-slate-400 mb-0.5">Valeur</Text>
                <Text className="text-[13px] font-black text-slate-900 leading-tight" numberOfLines={1}>
                  {new Intl.NumberFormat('fr-FR').format(stats.valueXAF)}
                  <Text className="text-[10px] text-slate-400 font-normal"> FCFA</Text>
                </Text>
              </View>
            </View>
          </View>
        )}

        {/* ===== Sort selector (= web "Plus récentes / Valeur décroissante / …") ===== */}
        {!loading && cards.length > 0 && (
          <TouchableOpacity
            onPress={() => setSortOpen(true)}
            activeOpacity={0.85}
            className="bg-white rounded-xl border border-slate-200 mb-3"
            style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 14, paddingVertical: 12, gap: 10 }}
          >
            <AdjustmentsHorizontalIcon size={16} color="#475569" />
            <View className="flex-1">
              <Text style={{ fontSize: 9, color: '#94A3B8', fontWeight: '700', letterSpacing: 1, textTransform: 'uppercase' }}>Trier par</Text>
              <Text className="text-[13px] font-bold text-slate-900 mt-0.5">{SORT_LABELS[sortKey]}</Text>
            </View>
            <ChevronRightIcon size={16} color="#94A3B8" />
          </TouchableOpacity>
        )}

        {loading && !refreshing ? (
          <ActivityIndicator size="large" color="#44A08D" className="mt-10" />
        ) : filteredCards.length === 0 ? (
          <View className="bg-white rounded-2xl border border-slate-200 p-10 items-center">
            <View className="w-20 h-20 rounded-2xl items-center justify-center mb-5" style={{ backgroundColor: '#F1F5F9' }}>
              <CreditCardIcon size={40} color="#CBD5E1" />
            </View>
            <Text className="text-lg font-black text-slate-900 mb-2 text-center">
              {cards.length === 0 ? "Tu n'as pas encore de cartes" : 'Aucune carte ne correspond'}
            </Text>
            <Text className="text-xs text-slate-500 text-center mb-5" style={{ lineHeight: 18 }}>
              {cards.length === 0
                ? 'Achète ta première carte cadeau et elle apparaîtra ici instantanément après le paiement.'
                : 'Essaie de modifier tes filtres ou efface ta recherche.'}
            </Text>
            <TouchableOpacity
              onPress={() => router.push('/(tabs)/boutique')}
              className="px-5 py-3 rounded-xl flex-row items-center gap-2"
              style={{ backgroundColor: '#44A08D', shadowColor: '#44A08D', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.30, shadowRadius: 14, elevation: 4 }}
            >
              <ShoppingBagIcon size={16} color="#FFFFFF" />
              <Text className="text-white font-bold text-sm">Découvrir le catalogue</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <View>
            {filteredCards.map((card, i) => (
              <CardRow
                key={card.id}
                card={card}
                onRequireUnlock={requireUnlock}
                delay={(i % 12) * 60}
              />
            ))}
          </View>
        )}
      </ScrollView>

      {/* ===== Bottom-sheet pour le tri ===== */}
      <Modal visible={sortOpen} transparent animationType="slide" onRequestClose={() => setSortOpen(false)}>
        <Pressable style={{ flex: 1, backgroundColor: 'rgba(15,23,42,0.55)' }} onPress={() => setSortOpen(false)}>
          <Pressable
            onPress={(e) => e.stopPropagation()}
            style={{
              marginTop: 'auto',
              backgroundColor: 'white',
              borderTopLeftRadius: 24, borderTopRightRadius: 24,
              paddingBottom: 32,
            }}
          >
            <View style={{ alignItems: 'center', paddingTop: 10, paddingBottom: 6 }}>
              <View style={{ width: 40, height: 5, borderRadius: 999, backgroundColor: '#CBD5E1' }} />
            </View>
            <Text className="text-base font-black text-slate-900 px-5 pt-2 pb-3">Trier les cartes</Text>
            {(Object.keys(SORT_LABELS) as SortKey[]).map(k => {
              const active = sortKey === k;
              return (
                <TouchableOpacity
                  key={k}
                  onPress={() => { setSortKey(k); setSortOpen(false); }}
                  className="flex-row items-center justify-between px-5 py-3.5"
                  style={{ borderTopWidth: 1, borderTopColor: '#F1F5F9' }}
                >
                  <Text className="text-[14px] font-bold" style={{ color: active ? '#0F766E' : '#475569' }}>
                    {SORT_LABELS[k]}
                  </Text>
                  {active && (
                    <View className="w-5 h-5 rounded-full items-center justify-center" style={{ backgroundColor: '#44A08D' }}>
                      <CheckIcon size={12} color="white" />
                    </View>
                  )}
                </TouchableOpacity>
              );
            })}
          </Pressable>
        </Pressable>
      </Modal>

      <PasswordGateModal />
    </SafeAreaView>
  );
}
