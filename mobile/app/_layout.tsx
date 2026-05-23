import { Stack, useRouter, useSegments } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useState } from 'react';
import { View, ActivityIndicator } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
// import "../global.css";
import SplashScreen from './splash';
import { CartProvider } from '../context/CartContext';
import { AlertProvider } from '../context/AlertContext';
import { CatalogService } from '../services/catalog';

export default function RootLayout() {
  const [appIsReady, setAppIsReady] = useState(false);
  const [isFirstLaunch, setIsFirstLaunch] = useState<boolean | null>(null);
  const router = useRouter();

  useEffect(() => {
    async function prepare() {
      try {
        // Vérifier si c'est le premier lancement
        const value = await AsyncStorage.getItem('hasSeenOnboarding');
        if (value === null) {
          setIsFirstLaunch(true);
        } else {
          setIsFirstLaunch(false);
        }

        // Sync les taux de change FCFA (admin BDD) en parallèle du splash.
        // Best-effort : silencieux en cas d'échec, l'app garde les fallbacks.
        CatalogService.refreshCurrencyRates().catch(() => {});

        // Simuler un chargement ou vérifier l'état de l'app
        await new Promise(resolve => setTimeout(resolve, 2500)); // Afficher le splash pendant 2.5s
      } catch (e) {
        console.warn(e);
      } finally {
        setAppIsReady(true);
      }
    }

    prepare();
  }, []);

  useEffect(() => {
    if (appIsReady && isFirstLaunch !== null) {
      if (isFirstLaunch) {
        router.replace('/onboarding');
      } else {
        router.replace('/(tabs)');
      }
    }
  }, [appIsReady, isFirstLaunch]);

  if (!appIsReady) {
    return <SplashScreen />;
  }

  return (
    <AlertProvider>
      <CartProvider>
        <Stack screenOptions={{ headerShown: false }}>
          <Stack.Screen name="(tabs)" />
          <Stack.Screen name="onboarding" />
          <Stack.Screen name="login" />
          <Stack.Screen name="payment/index" options={{ presentation: 'modal' }} />
          <Stack.Screen name="product/[id]" />
          <Stack.Screen name="card/[id]" />
          <Stack.Screen name="category/[id]" />
          <Stack.Screen name="orders/index" />
          <Stack.Screen name="orders/[id]" />
          <Stack.Screen name="search" />
          <Stack.Screen name="profile/edit" />
          <Stack.Screen name="profile/security" />
          <Stack.Screen name="profile/help" />
          <Stack.Screen name="profile/assistant" />
          <Stack.Screen name="notifications" />
        </Stack>
        <StatusBar style="auto" />
      </CartProvider>
    </AlertProvider>
  );
}