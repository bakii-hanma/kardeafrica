import { View, Text, Image, Animated, ActivityIndicator } from 'react-native';
import { useEffect, useRef } from 'react';
import { SafeAreaView } from 'react-native-safe-area-context';
import BouncingDots from '../components/BouncingDots';

export default function SplashScreen() {
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const scaleAnim = useRef(new Animated.Value(0.8)).current;
  const floatAnim = useRef(new Animated.Value(0)).current;
  const textAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Animation d'entrée
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 800,
        useNativeDriver: true,
      }),
      Animated.spring(scaleAnim, {
        toValue: 1,
        friction: 8,
        tension: 40,
        useNativeDriver: true,
      }),
      Animated.timing(textAnim, {
        toValue: 1,
        duration: 1000,
        delay: 300,
        useNativeDriver: true,
      })
    ]).start();

    // Animation de flottement continue
    Animated.loop(
      Animated.sequence([
        Animated.timing(floatAnim, {
          toValue: 1,
          duration: 2000,
          useNativeDriver: true,
        }),
        Animated.timing(floatAnim, {
          toValue: 0,
          duration: 2000,
          useNativeDriver: true,
        }),
      ])
    ).start();
  }, []);

  const translateY = floatAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0, -20]
  });

  const rotateZ = floatAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['-2deg', '2deg']
  });

  return (
    <SafeAreaView className="flex-1 bg-[#4ECDC4] items-center justify-center">
      <Animated.View 
        style={{
          opacity: fadeAnim,
          transform: [
            { scale: scaleAnim },
            { translateY },
            { rotateZ },
            { perspective: 1000 }
          ]
        }}
        className="items-center"
      >
        <View className="bg-white/10 rounded-full p-8 shadow-2xl border border-white/20">
          <Image 
            source={require('./assets/FAVCON-KARDAFRICA-.png')}
            className="w-32 h-32"
            resizeMode="contain"
          />
        </View>
      </Animated.View>
      
      <Animated.View 
        style={{
          opacity: textAnim,
          transform: [{
            translateY: textAnim.interpolate({
              inputRange: [0, 1],
              outputRange: [20, 0]
            })
          }]
        }}
        className="mt-8 items-center"
      >
        <Text className="text-white text-3xl font-bold mb-2 shadow-black/20">
          KarDAfrica
        </Text>
        <Text className="text-white/90 text-lg font-medium mb-8">
          Cartes numériques en un clic !
        </Text>
        
        <View className="items-center">
          <BouncingDots color="#FFFFFF" />
        </View>
      </Animated.View>
    </SafeAreaView>
  );
}