import { View, Text, ScrollView, TouchableOpacity, ActivityIndicator, RefreshControl, Image, Animated, Easing } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useState, useEffect, useRef } from 'react';
import * as Clipboard from 'expo-clipboard';
import {
  ArrowLeftIcon, CheckCircleIcon, CreditCardIcon, EyeIcon, EyeSlashIcon,
  ClipboardDocumentIcon, CheckIcon, ChevronRightIcon, ArrowPathIcon,
  ExclamationTriangleIcon,
} from 'react-native-heroicons/outline';
import { OrderService, Order } from '../../services/order';
import { usePasswordGate } from '../../components/PasswordGate';
import { useAlert } from '../../context/AlertContext';

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

const STATUS_INFO: Record<string, { label: string; bg: string; text: string; border: string }> = {
  pending:    { label: 'En attente',    bg: '#FEF3C7', text: '#B45309', border: '#FDE68A' },
  processing: { label: 'En traitement', bg: '#E0F2FE', text: '#0369A1', border: '#BAE6FD' },
  completed:  { label: 'Terminée',      bg: '#D1FAE5', text: '#047857', border: '#A7F3D0' },
  cancelled:  { label: 'Annulée',       bg: '#FFE4E6', text: '#BE123C', border: '#FECDD3' },
  failed:     { label: 'Échouée',       bg: '#FFE4E6', text: '#BE123C', border: '#FECDD3' },
};
const PAY_INFO: Record<string, { label: string; bg: string; text: string; border: string }> = {
  completed: { label: 'Payé', bg: '#D1FAE5', text: '#047857', border: '#A7F3D0' },
  paid:      { label: 'Payé', bg: '#D1FAE5', text: '#047857', border: '#A7F3D0' },
  pending:   { label: 'En attente', bg: '#FEF3C7', text: '#B45309', border: '#FDE68A' },
  failed:    { label: 'Échoué',     bg: '#FFE4E6', text: '#BE123C', border: '#FECDD3' },
};

interface CardRowProps {
  card: any;
  delay: number;
  onRequireUnlock: (cb: () => void) => void;
}
function CardRow({ card, delay, onRequireUnlock }: CardRowProps) {
  const [codeShown, setCodeShown] = useState(false);
  const [pinShown, setPinShown] = useState(false);
  const [copied, setCopied] = useState<string | null>(null);
  const opacity = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(20)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(opacity, { toValue: 1, duration: 500, delay, useNativeDriver: true, easing: Easing.out(Easing.cubic) }),
      Animated.timing(translateY, { toValue: 0, duration: 500, delay, useNativeDriver: true, easing: Easing.out(Easing.cubic) }),
    ]).start();
  }, []);

  const brandColor = getBrandColor(card.brand || card.name || '');
  const brandName = card.brand || (card.name || '').split(' ')[0];
  const cardCode = card.code || card.card_code || '';
  const cardPin = card.pin || '';

  const toggleCode = () => {
    if (codeShown) { setCodeShown(false); return; }
    onRequireUnlock(() => setCodeShown(true));
  };
  const togglePin = () => {
    if (pinShown) { setPinShown(false); return; }
    onRequireUnlock(() => setPinShown(true));
  };
  const doCopy = (text: string, field: string) => {
    onRequireUnlock(async () => {
      await Clipboard.setStringAsync(text);
      setCopied(field);
      setTimeout(() => setCopied(null), 1800);
    });
  };

  // Status badge — 3 variants comme le wallet (active/used/expired)
  const statusBadge =
    card.status === 'active'  ? { label: 'Active',   bg: 'rgba(16,185,129,0.30)', dot: true } :
    card.status === 'used'    ? { label: 'Utilisée', bg: 'rgba(100,116,139,0.40)', dot: false } :
    card.status === 'expired' ? { label: 'Expirée',  bg: 'rgba(244,63,94,0.40)',   dot: false } :
                                 { label: card.status || '—', bg: 'rgba(255,255,255,0.20)', dot: false };

  // Champs unifiés (= wallet) — la mobile API renvoie price_paid_xaf, mais on
  // garde les fallbacks `balance` et `face_value` pour les anciennes versions.
  const pricePaid    = (card as any).price_paid_xaf ?? card.balance ?? card.face_value ?? 0;
  const faceCurrency = (card as any).face_currency ?? card.currency;
  const faceValue    = (card as any).face_value ?? 0;
  const expiryDate   = card.expiry_date ?? card.expiration_date;
  const createdAt    = card.created_at;

  return (
    <Animated.View style={{ opacity, transform: [{ translateY }] }} className="bg-white rounded-2xl border border-slate-200 mb-3 overflow-hidden">
      {/* ===== Visuel carte (= identique au wallet, identique au web) ===== */}
      <View style={{ backgroundColor: brandColor }} className="p-4 relative">
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
              {new Intl.NumberFormat('fr-FR').format(Number(pricePaid) || 0)}
              <Text className="text-white/60 text-xs font-normal"> FCFA</Text>
            </Text>
            {faceCurrency && faceCurrency !== 'XAF' && (
              <Text className="text-white/60 text-[10px] mt-0.5">
                {new Intl.NumberFormat('fr-FR').format(Number(faceValue) || 0)} {faceCurrency} de crédit
              </Text>
            )}
          </View>
          <View className="w-9 h-6 rounded" style={{ backgroundColor: '#FCD34D', borderWidth: 1, borderColor: 'rgba(255,255,255,0.3)' }} />
        </View>
      </View>

      {/* ===== Détails (= identique au wallet) ===== */}
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
              <TouchableOpacity onPress={() => doCopy(cardCode, 'code')} className="p-1.5">
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
          {!!expiryDate && (
            <View className="flex-1 bg-slate-50 border border-slate-200 rounded-lg p-2.5">
              <Text style={{ letterSpacing: 1 }} className="text-[10px] uppercase font-bold text-slate-400 mb-1">Expire</Text>
              <Text className="text-xs font-bold text-slate-900">
                {new Date(expiryDate).toLocaleDateString('fr-FR')}
              </Text>
            </View>
          )}
        </View>

        {/* Footer : "Achetée le DATE" + bouton Copier compact (= wallet/web) */}
        <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-slate-100">
          {createdAt ? (
            <Text className="text-[10px] text-slate-500">
              Achetée le {new Date(createdAt).toLocaleDateString('fr-FR')}
            </Text>
          ) : <View />}
          <TouchableOpacity
            onPress={() => doCopy(cardCode, 'code')}
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

export default function OrderDetailScreen() {
  const { id } = useLocalSearchParams();
  const router = useRouter();

  const [order, setOrder] = useState<Order | null>(null);
  const [cards, setCards] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [retrying, setRetrying] = useState(false);

  const { requireUnlock, PasswordGateModal } = usePasswordGate();
  const { showAlert } = useAlert();

  const loadOrder = async () => {
    try {
      setLoading(true);
      const data = await OrderService.getOrder(Number(id));
      setOrder(data.order);
      setCards(data.cards || []);
    } catch (e) {
      console.error('Error loading order:', e);
      showAlert({ title: 'Erreur', message: 'Impossible de charger la commande.', variant: 'error' });
    } finally { setLoading(false); setRefreshing(false); }
  };

  useEffect(() => { loadOrder(); }, [id]);
  const onRefresh = () => { setRefreshing(true); loadOrder(); };

  /**
   * Relance la récupération des cartes pour cette commande
   * (= bouton "Relancer" sur le web /orders, mais pour le mobile).
   * N'apparaît que si la commande est PAYÉE mais sans cartes livrées.
   */
  const handleRetry = async () => {
    if (!order) return;
    setRetrying(true);
    try {
      const res = await OrderService.retryDelivery(order.id);
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
        await loadOrder();
      } else {
        showAlert({
          title: 'Échec',
          message: res.message ?? 'Impossible de relancer la livraison pour le moment.',
          variant: 'error',
        });
      }
    } finally {
      setRetrying(false);
    }
  };

  if (loading && !refreshing) {
    return (
      <SafeAreaView className="flex-1 bg-white items-center justify-center">
        <ActivityIndicator size="large" color="#44A08D" />
      </SafeAreaView>
    );
  }

  if (!order) {
    return (
      <SafeAreaView className="flex-1 bg-white items-center justify-center">
        <Text className="text-slate-500">Commande non trouvée</Text>
        <TouchableOpacity onPress={() => router.back()} className="mt-4 px-4 py-2 rounded-lg" style={{ backgroundColor: '#44A08D' }}>
          <Text className="text-white font-bold">Retour</Text>
        </TouchableOpacity>
      </SafeAreaView>
    );
  }

  const statusInfo = STATUS_INFO[order.status] || { label: order.status, bg: '#F1F5F9', text: '#475569', border: '#E2E8F0' };
  const payInfo = PAY_INFO[order.payment_status] || { label: order.payment_status, bg: '#F1F5F9', text: '#475569', border: '#E2E8F0' };

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
            <View className="flex-1 min-w-0">
              <Text style={{ letterSpacing: 1.5 }} className="text-[#5EEAD4] text-[10px] font-bold uppercase">Commande</Text>
              <Text className="font-mono text-base font-black text-white mt-0.5" numberOfLines={1}>#{order.order_number}</Text>
              <Text className="text-[10px] text-slate-400 mt-0.5">
                {new Date(order.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
              </Text>
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
        {/* Status badge */}
        <View className="flex-row items-center gap-2 mb-4 flex-wrap">
          {order.status === 'completed' && (
            <View className="flex-row items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200">
              <CheckCircleIcon size={14} color="#059669" />
              <Text className="text-emerald-700 text-xs font-bold">Commande livrée</Text>
            </View>
          )}
          <View className="flex-row items-center gap-1.5 px-2.5 py-1 rounded-md border" style={{ backgroundColor: statusInfo.bg, borderColor: statusInfo.border }}>
            <View className="w-1.5 h-1.5 rounded-full" style={{ backgroundColor: statusInfo.text }} />
            <Text className="text-[11px] font-bold" style={{ color: statusInfo.text }}>{statusInfo.label}</Text>
          </View>
          <View className="flex-row items-center gap-1.5 px-2.5 py-1 rounded-md border" style={{ backgroundColor: payInfo.bg, borderColor: payInfo.border }}>
            <Text className="text-[11px] font-bold" style={{ color: payInfo.text }}>{payInfo.label}</Text>
          </View>
        </View>

        {/* ========== Relancer la récupération — commande payée sans cartes livrées ========== */}
        {(() => {
          const isPaid      = ['completed', 'paid'].includes(order.payment_status);
          const isDelivered = order.status === 'completed';
          const canRetry    = isPaid && !isDelivered && order.status !== 'cancelled' && order.status !== 'refunded';
          if (!canRetry) return null;

          return (
            <View className="mb-4 rounded-2xl overflow-hidden border" style={{ backgroundColor: '#FFFBEB', borderColor: '#FDE68A' }}>
              <View className="p-4">
                <View className="flex-row items-start gap-3 mb-3">
                  <View className="w-9 h-9 rounded-xl items-center justify-center" style={{ backgroundColor: '#FEF3C7' }}>
                    <ExclamationTriangleIcon size={18} color="#B45309" />
                  </View>
                  <View className="flex-1">
                    <Text className="font-black text-[14px]" style={{ color: '#78350F' }}>
                      Cartes en attente de livraison
                    </Text>
                    <Text className="text-[12px] mt-1" style={{ color: '#92400E', lineHeight: 17 }}>
                      Le paiement est validé mais la livraison des cartes a échoué temporairement.
                      Tu peux relancer la récupération auprès du fournisseur.
                    </Text>
                  </View>
                </View>

                <TouchableOpacity
                  onPress={handleRetry}
                  disabled={retrying}
                  activeOpacity={0.85}
                  style={{
                    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
                    gap: 8, paddingVertical: 12, paddingHorizontal: 16,
                    borderRadius: 12,
                    backgroundColor: retrying ? '#FDE68A' : '#B45309',
                    shadowColor: '#B45309', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.25, shadowRadius: 8, elevation: 3,
                  }}
                >
                  {retrying ? (
                    <ActivityIndicator size="small" color="#78350F" />
                  ) : (
                    <ArrowPathIcon size={16} color="white" />
                  )}
                  <Text className="font-black text-[13px]" style={{ color: retrying ? '#78350F' : 'white' }}>
                    {retrying ? 'Récupération en cours…' : 'Relancer la récupération des cartes'}
                  </Text>
                </TouchableOpacity>
              </View>
            </View>
          );
        })()}

        {/* Vos cartes (animées par ligne) */}
        {cards.length > 0 && (
          <View className="mb-4">
            <View className="flex-row items-center gap-2 mb-3">
              <View className="w-7 h-7 rounded-lg items-center justify-center" style={{ backgroundColor: '#44A08D' }}>
                <CreditCardIcon size={14} color="#FFFFFF" />
              </View>
              <Text className="text-base font-bold text-slate-900">Vos cartes</Text>
              <Text className="text-xs text-slate-500">· {cards.length} reçue{cards.length > 1 ? 's' : ''}</Text>
            </View>
            {cards.map((c, i) => (
              <CardRow key={c.id} card={c} delay={i * 100} onRequireUnlock={requireUnlock} />
            ))}
          </View>
        )}

        {/* Articles commandés */}
        <View className="bg-white rounded-2xl border border-slate-200 mb-4 overflow-hidden">
          <View className="px-4 py-3 border-b border-slate-100 bg-slate-50">
            <Text className="text-sm font-bold text-slate-900">Articles ({order.order_items?.length || 0})</Text>
          </View>
          {order.order_items?.map((item, index) => (
            <View
              key={item.id}
              className={`flex-row items-center p-3 ${index < order.order_items.length - 1 ? 'border-b border-slate-100' : ''}`}
            >
              <View className="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 items-center justify-center overflow-hidden mr-3">
                {item.image_url ? (
                  <Image source={{ uri: item.image_url }} className="w-full h-full" resizeMode="cover" />
                ) : (
                  <Text className="font-bold text-slate-500 text-base">{item.name.charAt(0).toUpperCase()}</Text>
                )}
              </View>
              <View className="flex-1 min-w-0">
                <View className="flex-row items-center gap-1.5">
                  <View className="px-1.5 py-0.5 rounded-md bg-slate-100">
                    <Text className="text-[10px] font-bold text-slate-700">{item.quantity}×</Text>
                  </View>
                  <Text className="text-sm font-semibold text-slate-900 flex-1" numberOfLines={1}>{item.name}</Text>
                </View>
                <Text className="text-[11px] text-slate-500 mt-0.5">{OrderService.formatAmount(item.unit_price)} / unité</Text>
              </View>
              <Text className="text-sm font-black text-slate-900">{OrderService.formatAmount(item.total_price)}</Text>
            </View>
          ))}
        </View>

        {/* Récapitulatif */}
        <View className="bg-white rounded-2xl border border-slate-200 mb-4 overflow-hidden">
          <View className="px-4 py-3 border-b border-slate-100 bg-slate-50">
            <Text className="text-sm font-bold text-slate-900">Récapitulatif</Text>
          </View>
          <View className="p-4 gap-2.5">
            <View className="flex-row justify-between">
              <Text className="text-sm text-slate-600">Sous-total</Text>
              <Text className="text-sm font-semibold text-slate-900">{OrderService.formatAmount(order.subtotal)}</Text>
            </View>
            {order.tax_amount > 0 && (
              <View className="flex-row justify-between">
                <Text className="text-sm text-slate-600">TVA</Text>
                <Text className="text-sm font-semibold text-slate-900">{OrderService.formatAmount(order.tax_amount)}</Text>
              </View>
            )}
            <View className="flex-row justify-between">
              <Text className="text-sm text-slate-600">Frais de service</Text>
              <Text className="text-sm font-semibold text-emerald-600">Offerts</Text>
            </View>
            <View className="flex-row justify-between pt-2.5 border-t border-slate-100 items-end">
              <Text style={{ letterSpacing: 1 }} className="text-[10px] uppercase font-bold text-slate-400">Total payé</Text>
              <Text className="text-xl font-black text-slate-900">{OrderService.formatAmount(order.total_amount)}</Text>
            </View>
          </View>
        </View>

        {/* Paiement */}
        {order.payment_method && (
          <View className="bg-white rounded-2xl border border-slate-200 p-4 mb-4">
            <Text style={{ letterSpacing: 1 }} className="text-[10px] uppercase font-bold text-slate-400 mb-2">Mode de paiement</Text>
            <View className="flex-row items-center gap-3">
              <View className="w-9 h-9 rounded-lg items-center justify-center" style={{ backgroundColor: '#44A08D' }}>
                <CreditCardIcon size={16} color="#FFFFFF" />
              </View>
              <View className="flex-1 min-w-0">
                <Text className="text-sm font-semibold text-slate-900 capitalize">
                  {order.payment_method === 'simulated' ? 'Paiement simulé'
                    : order.payment_method === 'ebilling' ? 'E-Billing'
                    : order.payment_method}
                </Text>
                {(order as any).external_reference && (
                  <Text style={{ fontFamily: 'monospace' }} className="text-[10px] text-slate-500 mt-0.5" numberOfLines={1}>
                    {(order as any).external_reference}
                  </Text>
                )}
              </View>
            </View>
          </View>
        )}

        {/* Besoin d'aide ? — section help (= web /orders/{id}) */}
        <View className="bg-white rounded-2xl border border-slate-200 p-4">
          <Text style={{ letterSpacing: 1 }} className="text-[10px] uppercase font-bold text-slate-400 mb-2">Besoin d'aide ?</Text>
          <Text className="text-xs text-slate-600 mb-3" style={{ lineHeight: 18 }}>
            Un problème avec ta commande ? Notre équipe est disponible 24/7 pour t'aider.
          </Text>
          <View className="flex-row gap-2">
            <TouchableOpacity
              onPress={() => router.push('/profile/assistant')}
              activeOpacity={0.85}
              className="flex-1 flex-row items-center justify-center gap-2 px-4 py-2.5 rounded-xl"
              style={{ backgroundColor: '#F0FDFA', borderWidth: 1, borderColor: '#99F6E4' }}
            >
              <Text className="text-[12px] font-black" style={{ color: '#0F766E' }}>Demander à Kara</Text>
            </TouchableOpacity>
            <TouchableOpacity
              onPress={() => router.push('/profile/help')}
              activeOpacity={0.85}
              className="flex-1 flex-row items-center justify-center gap-2 px-4 py-2.5 rounded-xl"
              style={{ backgroundColor: '#44A08D' }}
            >
              <Text className="text-white text-[12px] font-black">Contacter le support</Text>
            </TouchableOpacity>
          </View>
        </View>
      </ScrollView>

      {/* Bottom CTA */}
      {cards.length > 0 && (
        <View className="absolute bottom-0 left-0 right-0 bg-white border-t border-slate-100 p-4 pb-8">
          <TouchableOpacity
            onPress={() => router.push('/(tabs)/wallet')}
            className="w-full py-4 rounded-2xl flex-row items-center justify-center gap-2"
            style={{ backgroundColor: '#44A08D' }}
          >
            <CreditCardIcon size={20} color="#FFFFFF" />
            <Text className="text-white font-bold text-base">Voir toutes mes cartes</Text>
            <ChevronRightIcon size={18} color="#FFFFFF" />
          </TouchableOpacity>
        </View>
      )}

      <PasswordGateModal />
    </SafeAreaView>
  );
}
