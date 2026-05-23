import React, { useEffect, useRef } from 'react';
import { View, Text, Image, TouchableOpacity, Animated, Easing } from 'react-native';
import { useRouter } from 'expo-router';
import { UserIcon } from 'react-native-heroicons/outline';
import StackedCards from './StackedCards';

interface AuthRequiredProps {
  message?: string;
}

export default function AuthRequired({ message = "Connectez-vous pour accéder à cette fonctionnalité" }: AuthRequiredProps) {
  const router = useRouter();
  const floatAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(floatAnim, {
          toValue: -5,
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
  }, []);

  return (
    <View className="flex-1 bg-gray-50 items-center justify-center px-6">
      <View className="items-center mb-12 w-full">
        {/* 3D Floating Cards Animation */}
        <View className="mb-8 w-full items-center">
          <StackedCards scale={0.85} />
        </View>
        
        <Text className="text-2xl font-bold text-gray-900 text-center mb-3 mt-4">
          Bienvenue sur KardAfrica
        </Text>
        <Text className="text-gray-500 text-center text-base px-4">
          {message}
        </Text>
      </View>

      <Animated.View style={{ transform: [{ translateY: floatAnim }] }} className="w-full px-8">
        <TouchableOpacity 
          className="bg-[#1F2937] py-4 rounded-2xl shadow-xl shadow-gray-900/20 items-center justify-center flex-row"
          onPress={() => {
            router.push('/login');
          }}
        >
          <Text className="text-white font-bold text-lg mr-2">Se connecter</Text>
          <UserIcon size={24} color="white" />
        </TouchableOpacity>
      </Animated.View>
    </View>
  );
}
