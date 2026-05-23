import React, { useEffect, useRef } from 'react';
import { View, Text, Animated, Image } from 'react-native';

interface StackedCardsProps {
  scale?: number;
}

const CreditCard = ({ 
  style, 
  color, 
  logo, 
  index,
  textColor = "white",
  badgeText = "Gift Card",
  badgeColor = "white"
}: any) => {
  const cardFloatAnim = useRef(new Animated.Value(0)).current;
  
  useEffect(() => {
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
            ...(style.transform || []),
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
      
      <View className="flex-1 items-center justify-center">
        {logo}
      </View>
      
      <View className="absolute right-4 bottom-4">
         <Text className="text-4xl">🎁</Text>
      </View>
      
      <View className="absolute top-0 right-0 w-full h-full rounded-2xl overflow-hidden opacity-10 pointer-events-none">
         <View className="w-full h-full bg-white transform rotate-45 translate-x-20 -translate-y-20" />
      </View>
    </Animated.View>
  );
};

export default function StackedCards({ scale = 0.8 }: StackedCardsProps) {
  return (
    <View className="items-center justify-center h-60 w-full" style={{ transform: [{ scale }] }}>
      <View className="relative w-full h-full items-center justify-center">
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
                    source={require('../app/assets/FAVCON-KARDAFRICA-.png')}
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
                    source={require('../app/assets/FAVCON-KARDAFRICA-.png')}
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
              { translateY: 0 },
              { scale: 1 }
            ]
          }}
          logo={
            <View className="items-center">
                <Image 
                    source={require('../app/assets/FAVCON-KARDAFRICA-.png')}
                    className="w-24 h-24"
                    resizeMode="contain"
                />
            </View>
          }
        />
      </View>
    </View>
  );
}
