import { View, Text, Image, TouchableOpacity, StatusBar, Dimensions, Animated } from 'react-native';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useEffect, useRef, useState } from 'react';

const { width } = Dimensions.get('window');

export default function OnboardingScreen() {
  const router = useRouter();
  const [currentStep, setCurrentStep] = useState(0);
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const slideAnim = useRef(new Animated.Value(50)).current;

  // Étape 1 - Cartes 3D qui flottent
  const card1Anim = useRef(new Animated.Value(0)).current;
  const card2Anim = useRef(new Animated.Value(0)).current;
  const card3Anim = useRef(new Animated.Value(0)).current;
  const floatAnim = useRef(new Animated.Value(0)).current;
  
  // Étape 2 - Carte qui tourne en 3D
  const rotateYAnim = useRef(new Animated.Value(0)).current;
  const scale3DAnim = useRef(new Animated.Value(0)).current;
  
  // Étape 3 - Logo 3D
  const logoAnim = useRef(new Animated.Value(0)).current;
  const logoRotateAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeAnim, { toValue: 1, duration: 800, useNativeDriver: true }),
      Animated.timing(slideAnim, { toValue: 0, duration: 800, useNativeDriver: true }),
    ]).start(() => {
      startStepAnimation(0);
    });

    // Animation de flottement continue
    Animated.loop(
      Animated.sequence([
        Animated.timing(floatAnim, { toValue: 1, duration: 2000, useNativeDriver: true }),
        Animated.timing(floatAnim, { toValue: 0, duration: 2000, useNativeDriver: true }),
      ])
    ).start();
  }, []);

  const startStepAnimation = (step: number) => {
    if (step === 0) {
      // Animation cartes 3D séquentielles
      Animated.stagger(200, [
        Animated.spring(card1Anim, { toValue: 1, friction: 6, tension: 40, useNativeDriver: true }),
        Animated.spring(card2Anim, { toValue: 1, friction: 6, tension: 40, useNativeDriver: true }),
        Animated.spring(card3Anim, { toValue: 1, friction: 6, tension: 40, useNativeDriver: true }),
      ]).start();
    }
    // Les autres étapes utilisent maintenant l'animation interne de CreditCard
  };

  const nextStep = () => {
    if (currentStep < 2) {
      setCurrentStep(currentStep + 1);
      startStepAnimation(currentStep + 1);
    } else {
      handleContinue();
    }
  };

  const prevStep = () => {
    if (currentStep > 0) {
      setCurrentStep(currentStep - 1);
    }
  };

  const handleContinue = async () => {
    try {
      await AsyncStorage.setItem('hasSeenOnboarding', 'true');
      router.replace('/(tabs)');
    } catch (error) {
      console.error('Erreur lors de la sauvegarde:', error);
    }
  };

  const CreditCard = ({ 
    style, 
    color, 
    logo, 
    index,
    textColor = "white",
    badgeText = "Gift Card",
    badgeColor = "white"
  }: any) => {
    // Animation de flottement indépendante pour chaque carte
    const cardFloatAnim = useRef(new Animated.Value(0)).current;
    
    useEffect(() => {
      // Délai basé sur l'index pour créer un effet de vague
      setTimeout(() => {
        Animated.loop(
          Animated.sequence([
            Animated.timing(cardFloatAnim, { toValue: 1, duration: 2500, useNativeDriver: true }),
            Animated.timing(cardFloatAnim, { toValue: 0, duration: 2500, useNativeDriver: true }),
          ])
        ).start();
      }, index * 400);
    }, []);

    const floatY = cardFloatAnim.interpolate({
      inputRange: [0, 1],
      outputRange: [0, -10]
    });

    return (
      <Animated.View 
        style={[
          style,
          {
            transform: [
              ...style.transform || [],
              { translateY: floatY }
            ]
          }
        ]}
        className={`absolute w-72 h-44 rounded-2xl p-5 shadow-2xl border border-white/10 ${color}`}
      >
        <View className="flex-row justify-between items-start w-full mb-2">
          <Text className="font-bold text-xs tracking-widest uppercase opacity-80" style={{ color: textColor }}>KarDAfrica</Text>
          <View className="px-2 py-1 rounded-full bg-white/20">
              <Text className="text-[10px] font-bold uppercase" style={{ color: badgeColor }}>{badgeText}</Text>
          </View>
        </View>
        
        {/* Logo centered */}
        <View className="flex-1 items-center justify-center">
          {logo}
        </View>
        
        {/* Decorative Gift Box */}
        <View className="absolute right-4 bottom-4">
           <Text className="text-4xl">🎁</Text>
        </View>
        
        {/* Shine effect */}
        <View className="absolute top-0 right-0 w-full h-full rounded-2xl overflow-hidden opacity-10 pointer-events-none">
           <View className="w-full h-full bg-white transform rotate-45 translate-x-20 -translate-y-20" />
        </View>
      </Animated.View>
    );
  };



  const renderStep = () => {
    switch (currentStep) {
      case 0:
        return (
          <View className="flex-1 items-center justify-center px-4">
            <View className="w-full h-80 items-center justify-center relative mb-8">
              {/* Card 1 (Back) - Dark Premium */}
              <CreditCard 
                index={0}
                color="bg-[#0F172A]"
                badgeText="Premium"
                style={{
                  zIndex: 10,
                  transform: [
                    { rotate: '-15deg' }, 
                    { translateY: -40 },
                    { translateX: -40 },
                    { scale: 0.9 }
                  ]
                }}
                logo={
                  <View className="items-center">
                    <Image 
                        source={require('./assets/FAVCON-KARDAFRICA-.png')}
                        className="w-16 h-16 opacity-80"
                        resizeMode="contain"
                    />
                    <Text className="text-white font-bold text-lg mt-2 tracking-wider">PLATINUM</Text>
                  </View>
                }
              />
              
              {/* Card 2 (Middle) - Brand Teal */}
              <CreditCard 
                index={1}
                color="bg-[#44A08D]"
                badgeText="Standard"
                style={{
                  zIndex: 20,
                  transform: [
                    { rotate: '15deg' }, 
                    { translateY: -20 },
                    { translateX: 40 },
                    { scale: 0.95 }
                  ]
                }}
                logo={
                   <View className="items-center">
                      <Image 
                          source={require('./assets/FAVCON-KARDAFRICA-.png')}
                          className="w-20 h-20"
                          resizeMode="contain"
                      />
                  </View>
                }
              />

              {/* Card 3 (Front) - White/Clean */}
              <CreditCard 
                index={2}
                color="bg-white"
                textColor="#44A08D"
                badgeText="Official"
                badgeColor="#44A08D"
                style={{
                  zIndex: 30,
                  transform: [
                    { rotate: '-5deg' }, 
                    { translateY: 20 }
                  ]
                }}
                logo={
                  <View className="items-center">
                      <Image 
                          source={require('./assets/FAVCON-KARDAFRICA-.png')}
                          className="w-24 h-24"
                          resizeMode="contain"
                      />
                  </View>
                }
              />
            </View>
            <Text className="text-3xl font-bold text-gray-900 mb-4 text-center mt-12">
              Vos Services Préférés
            </Text>
            <Text className="text-gray-600 text-lg text-center px-4 leading-relaxed">
              Retrouvez toutes vos cartes cadeaux au format bancaire sécurisé et profitez de nos offres exclusives.
            </Text>
          </View>
        );
      
      case 1:
        return (
          <View className="flex-1 items-center justify-center px-8">
            <View className="w-full h-80 items-center justify-center relative mb-8">
               <CreditCard 
                index={0}
                color="bg-[#44A08D]"
                style={{
                  zIndex: 10,
                  transform: [
                    { rotate: '-5deg' }, 
                    { scale: 1 }
                  ]
                }}
                logo={
                  <View className="items-center">
                    <Text className="text-6xl mb-2">🎁</Text>
                    <Text className="text-white font-bold text-lg tracking-widest">CADEAU</Text>
                  </View>
                }
              />
            </View>
            <Text className="text-3xl font-bold text-gray-900 mb-4 text-center mt-8">
              Carte Reçue Instantanément
            </Text>
            <Text className="text-gray-600 text-lg text-center px-4 leading-relaxed">
              Recevez votre code par SMS et utilisez-le immédiatement
            </Text>
          </View>
        );
      
      case 2:
        return (
          <View className="flex-1 items-center justify-center px-8">
            <View className="w-full h-80 items-center justify-center relative mb-8">
              <CreditCard 
                index={0}
                color="bg-[#F9FAFB]"
                textColor="#000000"
                style={{
                  zIndex: 10,
                  transform: [
                    { rotate: '5deg' }, 
                    { scale: 1 }
                  ]
                }}
                logo={
                  <Image 
                    source={require('../assets/splash-icon.png')}
                    className="w-32 h-32"
                    style={{ width: 128, height: 128 }}
                    resizeMode="contain"
                  />
                }
              />
            </View>
            <Text className="text-4xl font-bold text-gray-900 mb-4 text-center mt-8">
              KarDAfrica
            </Text>
            <Text className="text-gray-600 text-lg text-center px-4 leading-relaxed">
              Votre plateforme de cartes cadeaux numériques en Afrique
            </Text>
          </View>
        );
      
      default:
        return null;
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-gradient-to-br from-gray-50 to-white">
      <StatusBar barStyle="dark-content" backgroundColor="#F9FAFB" />
      
      <Animated.View 
        style={{ opacity: fadeAnim, transform: [{ translateY: slideAnim }] }}
        className="flex-1"
      >
        {/* Header avec indicateurs d'étape */}
        <View className="flex-row justify-center items-center py-6">
          {[0, 1, 2].map((step) => (
            <Animated.View
              key={step}
              style={{
                opacity: fadeAnim,
                transform: [{ scale: currentStep === step ? 1.2 : 1 }]
              }}
              className={`w-3 h-3 rounded-full mx-2 ${
                step === currentStep ? 'bg-[#44A08D]' : 'bg-gray-300'
              }`}
            />
          ))}
        </View>

        {/* Contenu principal */}
        <View className="flex-1">
          {renderStep()}
        </View>

        {/* Boutons de navigation */}
        <View className="flex-row justify-between items-center px-8 py-8">
          <TouchableOpacity
            onPress={prevStep}
            className={`px-6 py-3 rounded-full ${
              currentStep === 0 ? 'opacity-0' : 'bg-gray-200'
            }`}
            disabled={currentStep === 0}
          >
            <Text className="text-gray-700 font-semibold">Précédent</Text>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={nextStep}
            className="px-8 py-3 rounded-full"
            style={{ backgroundColor: '#44A08D' }}
          >
            <Text className="text-white font-semibold">
              {currentStep === 2 ? 'Commencer' : 'Suivant'}
            </Text>
          </TouchableOpacity>
        </View>
      </Animated.View>
    </SafeAreaView>
  );
}