import React, { useState, useEffect, useRef } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, Image, StatusBar,
  KeyboardAvoidingView, Platform, ActivityIndicator,
} from 'react-native';
import { useRouter } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { setToken } from '../services/tokenStore';
import { AuthService } from '../services/auth';
import ErrorModal from '../components/ErrorModal';
import SuccessModal from '../components/SuccessModal';
import CountryPicker, { Country } from '../components/CountryPicker';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  ArrowLeftIcon, PhoneIcon, ChevronDownIcon, ChevronRightIcon,
  ChatBubbleLeftRightIcon, ArrowPathIcon,
} from 'react-native-heroicons/outline';

const BRAND = '#44A08D';
const WHATSAPP = '#25D366';

/**
 * Connexion / inscription par numéro WhatsApp + code OTP.
 * Un seul geste : on saisit son numéro, on reçoit un code à 6 chiffres sur
 * WhatsApp, on le saisit, on est connecté. Le compte est créé automatiquement
 * au premier envoi si le numéro est nouveau (géré côté serveur).
 */
export default function LoginScreen() {
  const router = useRouter();

  const [step, setStep] = useState<'phone' | 'code'>('phone');
  const [selectedCountryCode, setSelectedCountryCode] = useState('+241'); // Gabon
  const [selectedFlag, setSelectedFlag] = useState('🇬🇦');
  const [phone, setPhone] = useState('');
  const [code, setCode] = useState('');
  const [phoneMasked, setPhoneMasked] = useState('');

  const [countryPickerVisible, setCountryPickerVisible] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [cooldown, setCooldown] = useState(0);

  const [errorMessage, setErrorMessage] = useState('');
  const [errorModalVisible, setErrorModalVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [successTitle, setSuccessTitle] = useState('');
  const [successModalVisible, setSuccessModalVisible] = useState(false);

  const cooldownRef = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    return () => { if (cooldownRef.current) clearInterval(cooldownRef.current); };
  }, []);

  const startCooldown = (seconds: number) => {
    setCooldown(seconds);
    if (cooldownRef.current) clearInterval(cooldownRef.current);
    cooldownRef.current = setInterval(() => {
      setCooldown((s) => {
        if (s <= 1) {
          if (cooldownRef.current) clearInterval(cooldownRef.current);
          return 0;
        }
        return s - 1;
      });
    }, 1000);
  };

  const fullPhone = () => `${selectedCountryCode}${phone.replace(/[^0-9]/g, '')}`;

  const showError = (msg: string) => {
    setErrorMessage(msg);
    setErrorModalVisible(true);
  };

  const handleCountrySelect = (country: Country) => {
    setSelectedCountryCode(country.code);
    setSelectedFlag(country.flag);
    setCountryPickerVisible(false);
  };

  // Étape 1 — envoyer le code
  const handleSendCode = async () => {
    const digits = phone.replace(/[^0-9]/g, '');
    if (digits.length < 6) {
      showError('Entre un numéro de téléphone valide.');
      return;
    }

    setIsLoading(true);
    try {
      const res = await AuthService.sendWhatsAppCode(fullPhone());
      if (res.status === 'success' || res.status === 'cooldown') {
        if (res.phone_masked) setPhoneMasked(res.phone_masked);
        setStep('code');
        startCooldown(res.status === 'cooldown' && res.seconds ? res.seconds : 60);
      } else {
        showError(res.message || "L'envoi du code a échoué.");
      }
    } catch {
      showError('Une erreur est survenue. Vérifie ta connexion internet.');
    } finally {
      setIsLoading(false);
    }
  };

  // Étape 2 — vérifier le code
  const handleVerifyCode = async () => {
    const c = code.replace(/[^0-9]/g, '');
    if (c.length !== 6) {
      showError('Entre le code à 6 chiffres reçu sur WhatsApp.');
      return;
    }

    setIsLoading(true);
    try {
      const res = await AuthService.verifyWhatsAppCode(fullPhone(), c);
      if (res.status === 'success' && res.data) {
        await setToken(res.data.token);
        await AsyncStorage.setItem('user', JSON.stringify(res.data.user));

        setSuccessTitle('Connexion réussie');
        setSuccessMessage(`Bienvenue${res.data.user?.name ? ', ' + res.data.user.name : ''} ! Tes cartes sont dans ton compte.`);
        setSuccessModalVisible(true);
        setTimeout(() => {
          setSuccessModalVisible(false);
          router.replace('/(tabs)');
        }, 1800);
      } else {
        showError(res.message || 'Code incorrect.');
      }
    } catch {
      showError('Une erreur est survenue. Vérifie ta connexion internet.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleResend = async () => {
    if (cooldown > 0) return;
    setIsLoading(true);
    try {
      const res = await AuthService.sendWhatsAppCode(fullPhone());
      if (res.status === 'success' || res.status === 'cooldown') {
        startCooldown(res.status === 'cooldown' && res.seconds ? res.seconds : 60);
      } else {
        showError(res.message || "L'envoi du code a échoué.");
      }
    } catch {
      showError('Une erreur est survenue. Vérifie ta connexion internet.');
    } finally {
      setIsLoading(false);
    }
  };

  const goBack = () => {
    if (step === 'code') {
      setStep('phone');
      setCode('');
      return;
    }
    router.back();
  };

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      <StatusBar barStyle="dark-content" />

      {/* Décor */}
      <View className="absolute top-0 left-0 w-full h-full overflow-hidden">
        <View className="absolute -top-20 -right-20 w-64 h-64 bg-[#44A08D]/10 rounded-full" />
        <View className="absolute bottom-0 -left-20 w-72 h-72 bg-emerald-500/5 rounded-full" />
      </View>

      {/* Header */}
      <View className="px-6 py-4 flex-row items-center justify-between z-10">
        <TouchableOpacity
          onPress={goBack}
          className="w-10 h-10 bg-white rounded-full items-center justify-center shadow-sm border border-gray-100"
        >
          <ArrowLeftIcon size={20} color="#1F2937" />
        </TouchableOpacity>
        <Image
          source={require('./assets/FAVCON-KARDAFRICA-.png')}
          className="w-10 h-10 rounded-full"
        />
        <View className="w-10" />
      </View>

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        className="flex-1 z-10"
      >
        <View className="flex-1 px-6 pt-6">
          {/* Icône WhatsApp */}
          <View className="items-center mb-6">
            <View className="w-16 h-16 rounded-2xl items-center justify-center mb-4" style={{ backgroundColor: WHATSAPP + '1A' }}>
              <ChatBubbleLeftRightIcon size={32} color={WHATSAPP} />
            </View>
            <Text className="text-2xl font-bold text-gray-900">
              {step === 'phone' ? 'Connexion' : 'Vérification'}
            </Text>
            <Text className="text-sm text-gray-500 text-center mt-2 px-4">
              {step === 'phone'
                ? 'Entre ton numéro WhatsApp : tu recevras un code à 6 chiffres. Pas de compte ? Il est créé automatiquement.'
                : `Code envoyé sur WhatsApp${phoneMasked ? ' au ' + phoneMasked : ''}.`}
            </Text>
          </View>

          {step === 'phone' ? (
            <>
              {/* Sélecteur pays + numéro */}
              <Text className="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Ton numéro WhatsApp</Text>
              <View className="flex-row items-center gap-2">
                <TouchableOpacity
                  onPress={() => setCountryPickerVisible(true)}
                  className="flex-row items-center bg-white border border-gray-200 rounded-2xl px-3 h-14"
                >
                  <Text className="text-xl mr-1">{selectedFlag}</Text>
                  <Text className="text-base font-semibold text-gray-800">{selectedCountryCode}</Text>
                  <ChevronDownIcon size={16} color="#9CA3AF" />
                </TouchableOpacity>
                <View className="flex-1 flex-row items-center bg-white border border-gray-200 rounded-2xl px-4 h-14">
                  <PhoneIcon size={18} color="#9CA3AF" />
                  <TextInput
                    value={phone}
                    onChangeText={setPhone}
                    placeholder="6X XX XX XX"
                    keyboardType="phone-pad"
                    maxLength={15}
                    className="flex-1 ml-2 text-base text-gray-900"
                    placeholderTextColor="#9CA3AF"
                  />
                </View>
              </View>

              <TouchableOpacity
                onPress={handleSendCode}
                disabled={isLoading}
                className="mt-6 h-14 rounded-2xl items-center justify-center flex-row"
                style={{ backgroundColor: isLoading ? '#9CA3AF' : BRAND }}
              >
                {isLoading ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <>
                    <Text className="text-white font-bold text-base mr-1">Recevoir mon code</Text>
                    <ChevronRightIcon size={18} color="#fff" />
                  </>
                )}
              </TouchableOpacity>

              <Text className="text-xs text-gray-400 text-center mt-4 px-4">
                En continuant, tu acceptes de recevoir un code de vérification sur WhatsApp.
              </Text>
            </>
          ) : (
            <>
              {/* Saisie du code */}
              <Text className="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Code de vérification</Text>
              <View className="bg-white border border-gray-200 rounded-2xl px-4 h-16 justify-center">
                <TextInput
                  value={code}
                  onChangeText={(t) => setCode(t.replace(/[^0-9]/g, '').slice(0, 6))}
                  placeholder="— — — — — —"
                  keyboardType="number-pad"
                  maxLength={6}
                  autoFocus
                  className="text-2xl font-bold text-gray-900 text-center tracking-[8px]"
                  placeholderTextColor="#D1D5DB"
                />
              </View>

              <TouchableOpacity
                onPress={handleVerifyCode}
                disabled={isLoading}
                className="mt-6 h-14 rounded-2xl items-center justify-center"
                style={{ backgroundColor: isLoading ? '#9CA3AF' : BRAND }}
              >
                {isLoading ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <Text className="text-white font-bold text-base">Se connecter</Text>
                )}
              </TouchableOpacity>

              {/* Renvoyer */}
              <TouchableOpacity
                onPress={handleResend}
                disabled={cooldown > 0 || isLoading}
                className="mt-5 flex-row items-center justify-center"
              >
                <ArrowPathIcon size={16} color={cooldown > 0 ? '#9CA3AF' : BRAND} />
                <Text className="ml-1.5 text-sm font-semibold" style={{ color: cooldown > 0 ? '#9CA3AF' : BRAND }}>
                  {cooldown > 0 ? `Renvoyer le code (${cooldown}s)` : 'Renvoyer le code'}
                </Text>
              </TouchableOpacity>

              <TouchableOpacity onPress={goBack} className="mt-4 items-center">
                <Text className="text-sm text-gray-500">Changer de numéro</Text>
              </TouchableOpacity>
            </>
          )}
        </View>
      </KeyboardAvoidingView>

      <CountryPicker
        visible={countryPickerVisible}
        onClose={() => setCountryPickerVisible(false)}
        onSelect={handleCountrySelect}
        selectedCode={selectedCountryCode}
      />

      <ErrorModal
        visible={errorModalVisible}
        message={errorMessage}
        onClose={() => setErrorModalVisible(false)}
      />

      <SuccessModal
        visible={successModalVisible}
        title={successTitle}
        message={successMessage}
        onClose={() => setSuccessModalVisible(false)}
      />
    </SafeAreaView>
  );
}
