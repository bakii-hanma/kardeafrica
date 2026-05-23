import { View, Text, TouchableOpacity, ScrollView, Modal, ActivityIndicator, Image } from 'react-native';
import { useRouter } from 'expo-router';
import { ShieldCheckIcon, XMarkIcon } from 'react-native-heroicons/outline';
import { CheckCircleIcon } from 'react-native-heroicons/solid';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useState, useRef, useEffect } from 'react';
import { WebView } from 'react-native-webview';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as PaymentService from '../../services/payment';
import { useCart } from '../../context/CartContext';
import { useAlert } from '../../context/AlertContext';
import { formatFCFA } from '../../utils/currency';
import LoadingKeychain from '../../components/LoadingKeychain';

export default function PaymentScreen() {
  const router = useRouter();
  const { cartItems, cartTotal, cartCount, clearCart } = useCart();
  const { showAlert } = useAlert();

  // Real user data
  const [user, setUser] = useState<any>(null);

  // Payment Logic State
  const [isLoading, setIsLoading] = useState(false);
  const [showWebView, setShowWebView] = useState(false);
  const [portalUrl, setPortalUrl] = useState('');
  const [paymentStatus, setPaymentStatus] = useState<'idle' | 'pending' | 'success' | 'failed'>('idle');
  const [statusMessage, setStatusMessage] = useState('');

  // Refs for polling
  const externalRef = useRef<string>('');
  const pollingAttempts = useRef(0);
  const pollingTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Simulate payment (DEV only)
  const [isSimulating, setIsSimulating] = useState(false);

  const runSimulation = async () => {
    setIsSimulating(true);
    try {
      const items = cartItems.map((it: any) => ({
        product_id: String(it.id || it.product_id),
        name:       it.name,
        price:      Number(it.price) || 0,
        quantity:   Number(it.quantity) || 1,
        image_url:  it.image,
      }));

      const result = await PaymentService.simulatePayment(items);

      if (result.success) {
        clearCart();
        const delivered = result.cards_delivered && result.cards_delivered > 0;
        showAlert({
          title:   delivered ? 'Cartes livrées !' : 'Paiement simulé',
          message: result.message || 'Commande créée avec succès.',
          variant: delivered ? 'success' : 'info',
          buttons: [
            { label: 'Voir mes cartes',  variant: 'primary',  onPress: () => router.replace('/(tabs)/wallet') },
            { label: 'Voir la commande', variant: 'secondary', onPress: () => result.order_id
              ? router.replace(`/orders/${result.order_id}`)
              : router.replace('/(tabs)/wallet') },
          ],
        });
      } else {
        showAlert({ title: 'Échec simulation', message: result.message || 'Erreur inconnue', variant: 'error' });
      }
    } catch (e: any) {
      showAlert({ title: 'Erreur', message: e?.message || 'Erreur réseau', variant: 'error' });
    } finally {
      setIsSimulating(false);
    }
  };

  const handleSimulate = () => {
    if (isSimulating || isLoading) return;
    if (!user) {
      showAlert({
        title: 'Connexion requise',
        message: 'Tu dois être connecté pour simuler un paiement.',
        variant: 'warning',
        buttons: [{ label: 'Se connecter', variant: 'primary', onPress: () => router.push('/login') }],
      });
      return;
    }
    if (cartItems.length === 0) {
      showAlert({ title: 'Panier vide', message: 'Ajoute des articles avant de simuler.', variant: 'info' });
      return;
    }

    showAlert({
      title: 'Simuler le paiement ?',
      message: 'Mode développeur uniquement — la commande sera créée et marquée payée sans passer par E-Billing.',
      variant: 'warning',
      buttons: [
        { label: 'Annuler', variant: 'secondary' },
        { label: 'Simuler', variant: 'primary', onPress: runSimulation },
      ],
    });
  };

  // Load real user data on mount
  useEffect(() => {
    loadUserData();
    return () => {
      if (pollingTimer.current) clearTimeout(pollingTimer.current);
    };
  }, []);

  const loadUserData = async () => {
    try {
      const userData = await AsyncStorage.getItem('user');
      if (userData) {
        const parsed = JSON.parse(userData);
        setUser(parsed);
      }
    } catch (error) {
      console.error('Error loading user data:', error);
    }
  };

  const handlePayment = async () => {
    if (isLoading) return;

    // Validate we have user data
    if (!user) {
      showAlert({
        title: 'Connexion requise',
        message: 'Tu dois être connecté pour effectuer un paiement.',
        variant: 'warning',
        buttons: [{ label: 'Se connecter', variant: 'primary', onPress: () => router.push('/login') }],
      });
      return;
    }

    if (cartItems.length === 0) {
      showAlert({ title: 'Panier vide', message: 'Ajoute des articles avant de payer.', variant: 'info' });
      return;
    }

    setIsLoading(true);
    setPaymentStatus('pending');
    setStatusMessage('Initialisation du paiement...');

    try {
      // 1. Generate Reference
      const ref = PaymentService.generateExternalReference();
      externalRef.current = ref;
      console.log('Generated Reference:', ref);

      // 2. Use REAL cart total and REAL user data
      const amountToPay = Math.round(cartTotal);
      const userName = user.name || `${user.profile?.first_name || ''} ${user.profile?.last_name || ''}`.trim() || 'Client';
      const userEmail = user.email || 'client@kardafrica.com';
      const userPhone = user.phone || user.profile?.phone || '074000000';
      const userId = String(user.id);

      console.log('Payment with real user:', { userId, userName, userEmail, amountToPay });

      const backendInit = await PaymentService.initBackendTransaction(
        userId,
        amountToPay,
        userPhone,
        `Paiement Panier (${cartCount} articles)`,
        ref
      );

      console.log('Backend Init:', backendInit);

      if (!backendInit || !backendInit.success) {
        console.warn('Backend init failed, continuing with E-Billing...');
      }

      // 3. Create E-Bill with real user data and real amount
      const eBill = await PaymentService.createEBill(
        amountToPay,
        `Paiement Panier (${cartCount} articles)`,
        ref,
        userEmail,
        userPhone,
        userName
      );

      console.log('E-Bill Created:', eBill);

      if (!eBill || !eBill.e_bill || !eBill.e_bill.bill_id) {
        throw new Error('Erreur lors de la creation de la facture E-Billing');
      }

      // 4. Open Portal
      const url = PaymentService.getPortalUrl(eBill.e_bill.bill_id);
      setPortalUrl(url);
      setShowWebView(true);
      setIsLoading(false);

    } catch (error: any) {
      console.error('Payment Error:', error);
      showAlert({ title: 'Erreur', message: error.message || 'Une erreur est survenue', variant: 'error' });
      setIsLoading(false);
      setPaymentStatus('failed');
      setStatusMessage('Erreur: ' + (error.message || 'Inconnue'));
    }
  };

  const handleWebViewNavigation = (navState: any) => {
    const url = navState.url.toLowerCase();

    if (
      url.includes('remerciement') ||
      url.includes('callback') ||
      url.includes('success') ||
      url.includes('complete')
    ) {
      setShowWebView(false);
      startPolling();
      return;
    }

    if (
      url.includes('cancel') ||
      url.includes('error') ||
      url.includes('failed')
    ) {
      setShowWebView(false);
      setPaymentStatus('failed');
      setStatusMessage('Paiement annule ou echoue.');
      return;
    }
  };

  const startPolling = () => {
    setIsLoading(true);
    setPaymentStatus('pending');
    setStatusMessage('Verification du statut du paiement...');
    pollingAttempts.current = 0;
    if (pollingTimer.current) clearTimeout(pollingTimer.current);
    poll();
  };

  const poll = async () => {
    if (pollingAttempts.current >= 60) {
      setIsLoading(false);
      setPaymentStatus('failed');
      setStatusMessage('Delai de verification depasse.');
      showAlert({
        title: 'Délai dépassé',
        message: 'La vérification du paiement a pris trop de temps. Si tu as bien payé, tes cartes apparaîtront dans quelques minutes — ou utilise le bouton "Relancer" sur ta commande.',
        variant: 'warning',
      });
      return;
    }

    pollingAttempts.current += 1;

    try {
      const status = await PaymentService.checkPaymentStatus(externalRef.current);
      console.log(`Polling attempt ${pollingAttempts.current}:`, status);

      if (status && status.data) {
        const s = status.data.status;

        if (s === 'completed') {
          setStatusMessage('Paiement confirme ! Finalisation de la commande...');

          // Call finalize API to create order + checkout cards
          const finalizeResult = await PaymentService.finalizePayment(externalRef.current);
          console.log('Finalize result:', finalizeResult);

          if (finalizeResult.success) {
            // Clear local cart
            clearCart();

            setIsLoading(false);
            setPaymentStatus('success');
            setStatusMessage('Commande finalisee ! Vos cartes sont disponibles.');

            const cardsCount = finalizeResult.cards?.length || 0;

            setTimeout(() => {
              showAlert({
                title: 'Paiement réussi !',
                message: `Ta commande a été traitée avec succès.\n${cardsCount} carte${cardsCount > 1 ? 's ont' : ' a'} été ajoutée${cardsCount > 1 ? 's' : ''} à ton portefeuille.`,
                variant: 'success',
                buttons: [{ label: 'Voir mes cartes', variant: 'primary', onPress: () => router.replace('/(tabs)/wallet') }],
              });
            }, 500);
          } else {
            setIsLoading(false);
            setPaymentStatus('failed');
            setStatusMessage(finalizeResult.message || 'Erreur lors de la finalisation');
            showAlert({
              title: 'Erreur',
              message: finalizeResult.message || 'Erreur lors de la finalisation de la commande',
              variant: 'error',
            });
          }
          return;
        } else if (s === 'failed' || s === 'cancelled' || s === 'expired') {
          setIsLoading(false);
          setPaymentStatus('failed');
          setStatusMessage(`Paiement echoue (Statut: ${s})`);
          showAlert({
            title: 'Paiement échoué',
            message: `Le paiement a échoué : ${s}. Tu peux réessayer depuis le panier.`,
            variant: 'error',
          });
          return;
        }
      }
    } catch (err) {
      console.warn('Polling error:', err);
    }

    pollingTimer.current = setTimeout(poll, 3000);
  };

  if (isLoading) {
    return (
      <View className="flex-1 justify-center items-center bg-white">
        <LoadingKeychain />
        {statusMessage ? (
          <Text className="text-gray-500 text-center mt-4 px-8">{statusMessage}</Text>
        ) : null}
      </View>
    );
  }

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      <ScrollView className="flex-1">
        {/* Header */}
        <View className="p-6 bg-white border-b border-gray-100 flex-row justify-between items-start">
          <View>
            <Text className="text-3xl font-black text-gray-900 mb-2">Paiement</Text>
            <Text className="text-gray-500">Finalisez votre commande en toute securite</Text>
          </View>
          <TouchableOpacity
            onPress={() => router.back()}
            className="p-2 bg-gray-100 rounded-full"
          >
            <XMarkIcon size={24} color="#374151" />
          </TouchableOpacity>
        </View>

        {/* Cart Summary */}
        <View className="mt-6 mx-4 mb-2">
          <Text className="text-gray-700 font-bold mb-3 px-1">Recapitulatif ({cartCount})</Text>
          <View className="bg-white rounded-2xl p-4 shadow-sm">
            {cartItems.map((item) => (
              <View key={item.id} className="flex-row items-center py-3 border-b border-gray-100 last:border-0">
                <View className="w-12 h-12 rounded-lg bg-gray-50 items-center justify-center mr-3 overflow-hidden border border-gray-100">
                  {item.image ? (
                    <Image source={{ uri: item.image }} className="w-full h-full" resizeMode="cover" />
                  ) : (
                    <Text className="font-bold text-gray-400">{item.name.charAt(0)}</Text>
                  )}
                </View>
                <View className="flex-1">
                  <Text className="font-bold text-gray-800" numberOfLines={1}>{item.name}</Text>
                  <Text className="text-xs text-gray-500">Qte: {item.quantity} x {formatFCFA(item.price)}</Text>
                </View>
                <Text className="font-bold text-gray-900">{formatFCFA(item.price * item.quantity)}</Text>
              </View>
            ))}

            <View className="mt-4 pt-4 border-t border-gray-100 flex-row justify-between items-center">
              <Text className="font-bold text-gray-600">Total a payer</Text>
              <Text className="font-black text-xl text-[#1F2937]">{formatFCFA(cartTotal)}</Text>
            </View>
          </View>
        </View>

        {/* Status Message Display */}
        {statusMessage ? (
          <View className={`mx-4 p-4 rounded-xl mb-6 ${
            paymentStatus === 'success' ? 'bg-green-100' :
            paymentStatus === 'failed' ? 'bg-red-100' : 'bg-blue-100'
          }`}>
            <Text className={`text-center font-medium ${
              paymentStatus === 'success' ? 'text-green-800' :
              paymentStatus === 'failed' ? 'text-red-800' : 'text-blue-800'
            }`}>
              {statusMessage}
            </Text>
          </View>
        ) : null}

        {/* Payment Methods */}
        <View className="mx-4 bg-white rounded-xl overflow-hidden shadow-sm mb-6">
          <Text className="px-4 pt-4 pb-2 text-gray-500 font-medium">Moyen de paiement selectionne</Text>

          <View className="p-4">
            <View className="flex-row items-center p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
              <View className="w-10 h-10 bg-emerald-100 rounded-full items-center justify-center mr-4">
                <ShieldCheckIcon size={20} color="#059669" />
              </View>
              <View className="flex-1">
                <Text className="text-gray-900 font-bold">Portail Securise E-Billing</Text>
                <Text className="text-emerald-700 text-xs">Mobile Money, Carte Bancaire, Crypto</Text>
              </View>
              <CheckCircleIcon size={24} color="#059669" />
            </View>
          </View>
        </View>

        {/* Security Note */}
        <View className="flex-row justify-center items-center mt-4 mb-8">
          <ShieldCheckIcon size={16} color="#718096" />
          <Text className="text-gray-500 text-xs ml-1">Paiements 100% securises via E-Billing</Text>
        </View>
      </ScrollView>

      {/* Footer */}
      <View className="p-4 bg-white border-t border-gray-200" style={{ gap: 10 }}>
        {/* Bouton paiement reel */}
        <TouchableOpacity
          className={`py-4 rounded-xl items-center shadow-lg ${
            isLoading || paymentStatus === 'pending' ? 'bg-gray-400' : 'bg-gray-900'
          }`}
          onPress={handlePayment}
          disabled={isLoading || paymentStatus === 'pending'}
        >
          {isLoading || paymentStatus === 'pending' ? (
            <ActivityIndicator color="white" />
          ) : (
            <Text className="text-white font-bold text-lg">
              Payer {formatFCFA(cartTotal)}
            </Text>
          )}
        </TouchableOpacity>

        {/* Bouton simulation (DEV) */}
        <TouchableOpacity
          onPress={handleSimulate}
          disabled={isSimulating || isLoading}
          style={{
            paddingVertical: 14,
            borderRadius: 12,
            alignItems: 'center',
            justifyContent: 'center',
            flexDirection: 'row',
            gap: 8,
            backgroundColor: isSimulating ? '#94A3B8' : '#F8FAFC',
            borderWidth: 1.5,
            borderColor: isSimulating ? '#94A3B8' : '#44A08D',
            borderStyle: 'dashed',
          }}
        >
          {isSimulating ? (
            <>
              <ActivityIndicator color="white" />
              <Text className="text-white font-bold text-sm">Simulation en cours…</Text>
            </>
          ) : (
            <>
              <Text style={{ fontSize: 16 }}>⚡</Text>
              <Text style={{ fontSize: 13, fontWeight: '700', color: '#44A08D' }}>
                Simuler le paiement (dev)
              </Text>
            </>
          )}
        </TouchableOpacity>
      </View>

      {/* Payment Portal Modal */}
      <Modal
        visible={showWebView}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => {
          setShowWebView(false);
          startPolling();
        }}
      >
        <SafeAreaView className="flex-1 bg-white">
          <View className="flex-row justify-between items-center px-4 py-3 border-b border-gray-100">
            <Text className="font-bold text-lg text-gray-800">Paiement Securise</Text>
            <TouchableOpacity
              onPress={() => {
                setShowWebView(false);
                startPolling();
              }}
              className="bg-gray-100 p-2 rounded-full"
            >
              <XMarkIcon size={24} color="#374151" />
            </TouchableOpacity>
          </View>
          {portalUrl ? (
            <WebView
              source={{ uri: portalUrl }}
              onNavigationStateChange={handleWebViewNavigation}
              startInLoadingState={true}
              renderLoading={() => (
                <View className="absolute inset-0 items-center justify-center bg-white">
                  <ActivityIndicator size="large" color="#10B981" />
                </View>
              )}
            />
          ) : null}
        </SafeAreaView>
      </Modal>
    </SafeAreaView>
  );
}
