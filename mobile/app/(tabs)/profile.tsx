import { View, Text, TouchableOpacity, Image, ScrollView, Animated, Easing, Dimensions } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AuthRequired from '../../components/AuthRequired';
import LogoutModal from '../../components/LogoutModal';
import { useState, useEffect, useRef, useMemo } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getToken, deleteToken } from '../../services/tokenStore';
import { useRouter, useFocusEffect } from 'expo-router';
import { UserIcon, ArrowRightOnRectangleIcon, ChevronRightIcon, MapPinIcon, EnvelopeIcon, PhoneIcon, SparklesIcon, PencilIcon, ShieldCheckIcon, QuestionMarkCircleIcon, ExclamationTriangleIcon, ShoppingBagIcon } from 'react-native-heroicons/outline';
import { Gyroscope } from 'expo-sensors';
import { AuthService } from '../../services/auth';
import React from 'react';

const { width } = Dimensions.get('window');

export default function ProfileScreen() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [user, setUser] = useState<any>(null);
  const [isLogoutModalVisible, setIsLogoutModalVisible] = useState(false);
  const [imageError, setImageError] = useState(false);
  const router = useRouter();

  // Animation Refs
  const floatAnim = useRef(new Animated.Value(0)).current;
  const rotateX = useRef(new Animated.Value(0)).current;
  const rotateY = useRef(new Animated.Value(0)).current;
  const [subscription, setSubscription] = useState<any>(null);

  useFocusEffect(
    React.useCallback(() => {
      checkAuth();
    }, [])
  );

  useEffect(() => {
    startFloatingAnimation();
    subscribeToGyroscope();

    return () => {
      unsubscribeFromGyroscope();
    };
  }, []);

  const isProfileComplete = useMemo(() => {
    if (!user) return true;
    const p = user.profile || {};
    return (
      user.phone &&
      p.first_name &&
      p.last_name &&
      p.date_of_birth &&
      p.gender &&
      p.country &&
      p.city &&
      p.address
    );
  }, [user]);


  const startFloatingAnimation = () => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(floatAnim, {
          toValue: -10,
          duration: 2000,
          easing: Easing.inOut(Easing.sin),
          useNativeDriver: true,
        }),
        Animated.timing(floatAnim, {
          toValue: 0,
          duration: 2000,
          easing: Easing.inOut(Easing.sin),
          useNativeDriver: true,
        }),
      ])
    ).start();
  };

  const subscribeToGyroscope = () => {
    Gyroscope.setUpdateInterval(16);
    setSubscription(
      Gyroscope.addListener((data) => {
        Animated.parallel([
          Animated.timing(rotateX, {
            toValue: data.y * 10, // Invert axis for natural feel
            duration: 100,
            useNativeDriver: true,
          }),
          Animated.timing(rotateY, {
            toValue: data.x * 10,
            duration: 100,
            useNativeDriver: true,
          }),
        ]).start();
      })
    );
  };

  const unsubscribeFromGyroscope = () => {
    subscription && subscription.remove();
    setSubscription(null);
  };

  const cardRotateX = rotateX.interpolate({
    inputRange: [-10, 10],
    outputRange: ['10deg', '-10deg'],
    extrapolate: 'clamp',
  });

  const cardRotateY = rotateY.interpolate({
    inputRange: [-10, 10],
    outputRange: ['10deg', '-10deg'],
    extrapolate: 'clamp',
  });

  const checkAuth = async () => {
    try {
      const token = await getToken();
      const userData = await AsyncStorage.getItem('user');
      
      if (token) {
        setIsAuthenticated(true);
        if (userData) {
          setUser(JSON.parse(userData));
        }

        // Refresh user data from server to ensure profile is up to date
        try {
          const response = await AuthService.getUser(token);
          if (response.status === 'success' && response.data?.user) {
            const updatedUser = response.data.user;
            setUser(updatedUser);
            await AsyncStorage.setItem('user', JSON.stringify(updatedUser));
          }
        } catch (err) {
          console.log('Failed to refresh user data', err);
        }
      } else {
        setIsAuthenticated(false);
      }
    } catch (error) {
      console.error('Error checking auth:', error);
      setIsAuthenticated(false);
    }
  };

  const handleLogout = () => {
    setIsLogoutModalVisible(true);
  };

  const performLogout = async () => {
    try {
      setIsLogoutModalVisible(false);
      // H1 — révoque le token côté serveur avant de l'effacer localement (sinon
      // un token éventuellement exfiltré resterait valide).
      try {
        const token = await getToken();
        if (token) await AuthService.logout(token);
      } catch {}
      await deleteToken();
      await AsyncStorage.removeItem('user');
      setIsAuthenticated(false);
      setUser(null);
      router.replace('/');
    } catch (error) {
      console.error('Error logging out:', error);
    }
  };

  if (!isAuthenticated) {
    return (
      <SafeAreaView className="flex-1 bg-gray-50">
        <AuthRequired message="Connectez-vous pour gérer votre profil" />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      <ScrollView className="flex-1">
        {/* Header Section */}
        <View className="items-center justify-center py-8 px-4 z-10">
          <Animated.View 
            style={{
              transform: [
                { translateY: floatAnim },
                { rotateX: cardRotateX },
                { rotateY: cardRotateY },
                { perspective: 1000 }
              ]
            }}
            className="w-full bg-[#0F172A] p-6 rounded-3xl shadow-xl shadow-gray-900/50 border border-white/10 overflow-hidden"
          >
            {/* Edit Button */}
            <TouchableOpacity 
              onPress={() => router.push('/profile/edit')}
              className="absolute top-4 right-4 z-20 bg-white/10 p-2 rounded-full"
            >
              <PencilIcon size={20} color="#4ECDC4" />
            </TouchableOpacity>

            <View className="flex-row items-center space-x-4">
              <View className="w-20 h-20 bg-gray-800 rounded-full items-center justify-center border-2 border-[#4ECDC4]/50 overflow-hidden relative shadow-sm">
                {user?.profile?.avatar && !imageError ? (
                  <Image 
                    source={{ uri: user.profile.avatar }} 
                    className="w-full h-full rounded-full" 
                    onError={() => setImageError(true)}
                  />
                ) : (
                  <UserIcon size={40} color="#4ECDC4" />
                )}
                {/* Shiny effect overlay */}
                <View className="absolute inset-0 bg-white/10" />
              </View>
              
              <View className="flex-1">
                <Text className="text-2xl font-bold text-white mb-1">{user?.name || 'Utilisateur'}</Text>
                <Text className="text-gray-400 text-sm mb-2">{user?.email}</Text>
                
                <View className="flex-row items-center bg-[#4ECDC4]/10 self-start px-3 py-1 rounded-full border border-[#4ECDC4]/20">
                  <View className="w-2 h-2 bg-[#4ECDC4] rounded-full mr-2 shadow-sm shadow-[#4ECDC4]" />
                  <Text className="text-[#4ECDC4] text-xs font-bold uppercase tracking-wide">Compte Vérifié</Text>
                </View>
              </View>

              {/* Decorative element */}
              <View className="absolute -bottom-10 -right-10 opacity-10">
                 <SparklesIcon size={120} color="#4ECDC4" fill="#4ECDC4" />
              </View>
            </View>
            
            {/* Card decorations */}
            <View className="absolute top-0 right-0 w-32 h-32 bg-[#4ECDC4] rounded-bl-full opacity-5 -z-10" />
            <View className="absolute bottom-0 left-0 w-24 h-24 bg-blue-500 rounded-tr-full opacity-5 -z-10" />
          </Animated.View>
        </View>

        {!isProfileComplete && (
          <View className="mx-4 mb-4 bg-yellow-50 border border-yellow-200 p-4 rounded-xl flex-row items-start space-x-3">
            <ExclamationTriangleIcon size={24} color="#EAB308" />
            <View className="flex-1">
              <Text className="text-yellow-800 font-bold mb-1">Profil incomplet</Text>
              <Text className="text-yellow-700 text-sm">
                Veuillez compléter vos informations personnelles pour profiter pleinement de l'application.
              </Text>
            </View>
          </View>
        )}

        {/* Info Cards */}
        <View className="px-4 space-y-4 mb-8">
          <View className="bg-white rounded-2xl p-4 shadow-sm">
            <Text className="text-gray-900 font-bold text-lg mb-4">Informations Personnelles</Text>
            
            <View className="space-y-4">
              <View className="flex-row items-center space-x-3">
                <View className="w-8 h-8 bg-blue-50 rounded-full items-center justify-center">
                  <EnvelopeIcon size={16} color="#3B82F6" />
                </View>
                <View>
                  <Text className="text-xs text-gray-500">Email</Text>
                  <Text className="text-gray-900 font-medium">{user?.email}</Text>
                </View>
              </View>

              <View className="flex-row items-center space-x-3">
                <View className="w-8 h-8 bg-purple-50 rounded-full items-center justify-center">
                  <PhoneIcon size={16} color="#8B5CF6" />
                </View>
                <View>
                  <Text className="text-xs text-gray-500">Téléphone</Text>
                  <Text className="text-gray-900 font-medium">{user?.phone || 'Non renseigné'}</Text>
                </View>
              </View>

              <View className="flex-row items-center space-x-3">
                <View className="w-8 h-8 bg-orange-50 rounded-full items-center justify-center">
                  <MapPinIcon size={16} color="#F97316" />
                </View>
                <View>
                  <Text className="text-xs text-gray-500">Adresse</Text>
                  <Text className="text-gray-900 font-medium">
                    {[
                      user?.profile?.address,
                      user?.profile?.city,
                      user?.profile?.country
                    ].filter(Boolean).join(', ') || 'Non renseigné'}
                  </Text>
                </View>
              </View>
            </View>
          </View>

          {/* Account Actions */}
          <View className="bg-white rounded-2xl overflow-hidden shadow-sm">
            <TouchableOpacity
              onPress={() => router.push('/orders')}
              className="flex-row items-center justify-between p-4 border-b border-gray-100"
            >
              <View className="flex-row items-center space-x-3">
                <ShoppingBagIcon size={20} color="#4B5563" />
                <Text className="text-gray-700 font-medium">Mes commandes</Text>
              </View>
              <ChevronRightIcon size={20} color="#9CA3AF" />
            </TouchableOpacity>

            {/* Kara — Assistante IA (mise en avant avec accent teal + dot online) */}
            <TouchableOpacity
              onPress={() => router.push('/profile/assistant')}
              className="flex-row items-center justify-between p-4 border-b border-gray-100"
            >
              <View className="flex-row items-center" style={{ gap: 12 }}>
                <View style={{
                  width: 32, height: 32, borderRadius: 10,
                  backgroundColor: 'rgba(78,205,196,0.12)',
                  alignItems: 'center', justifyContent: 'center',
                  position: 'relative',
                }}>
                  <SparklesIcon size={18} color="#44A08D" />
                  <View style={{
                    position: 'absolute', bottom: -1, right: -1,
                    width: 9, height: 9, borderRadius: 999,
                    backgroundColor: '#34D399',
                    borderWidth: 2, borderColor: 'white',
                  }} />
                </View>
                <View>
                  <Text className="text-gray-700 font-medium">Kara · Assistante IA</Text>
                  <Text className="text-[11px] text-[#44A08D] font-semibold mt-0.5">En ligne · Réponse instantanée</Text>
                </View>
              </View>
              <ChevronRightIcon size={20} color="#9CA3AF" />
            </TouchableOpacity>

            <TouchableOpacity
              onPress={() => router.push('/profile/edit')}
              className="flex-row items-center justify-between p-4 border-b border-gray-100"
            >
              <View className="flex-row items-center space-x-3">
                <PencilIcon size={20} color="#4B5563" />
                <Text className="text-gray-700 font-medium">Modifier mon profil</Text>
              </View>
              <ChevronRightIcon size={20} color="#9CA3AF" />
            </TouchableOpacity>
            
            <TouchableOpacity 
              onPress={() => router.push('/profile/security')}
              className="flex-row items-center justify-between p-4 border-b border-gray-100"
            >
              <View className="flex-row items-center space-x-3">
                <ShieldCheckIcon size={20} color="#4B5563" />
                <Text className="text-gray-700 font-medium">Sécurité</Text>
              </View>
              <ChevronRightIcon size={20} color="#9CA3AF" />
            </TouchableOpacity>

            <TouchableOpacity 
              onPress={() => router.push('/profile/help')}
              className="flex-row items-center justify-between p-4"
            >
              <View className="flex-row items-center space-x-3">
                <QuestionMarkCircleIcon size={20} color="#4B5563" />
                <Text className="text-gray-700 font-medium">Aide & Support</Text>
              </View>
              <ChevronRightIcon size={20} color="#9CA3AF" />
            </TouchableOpacity>
          </View>

          {/* Logout Button */}
          <TouchableOpacity 
            onPress={handleLogout}
            className="flex-row items-center justify-center bg-red-50 p-4 rounded-2xl border border-red-100 mb-8"
          >
            <ArrowRightOnRectangleIcon size={20} color="#EF4444" />
            <Text className="text-red-500 font-bold ml-2">Se déconnecter</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>

      <LogoutModal
        visible={isLogoutModalVisible}
        onClose={() => setIsLogoutModalVisible(false)}
        onConfirm={performLogout}
      />
    </SafeAreaView>
  );
}