import React, { useEffect, useRef } from 'react';
import { View, Text, TouchableOpacity, Dimensions, Animated, Easing } from 'react-native';
import { ArrowPathIcon } from 'react-native-heroicons/outline';

interface EmptyStateProps {
  title?: string;
  message?: string;
  onRefresh?: () => void;
  buttonText?: string;
}

const FloatingCard = ({ index, color, rotate, translateX, translateY }: any) => {
  const floatY = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const animation = Animated.loop(
      Animated.sequence([
        Animated.timing(floatY, {
          toValue: -10,
          duration: 2000,
          easing: Easing.inOut(Easing.sin),
          useNativeDriver: true,
        }),
        Animated.timing(floatY, {
          toValue: 0,
          duration: 2000,
          easing: Easing.inOut(Easing.sin),
          useNativeDriver: true,
        })
      ])
    );

    // Delay
    setTimeout(() => {
      animation.start();
    }, index * 500);

    return () => animation.stop();
  }, []);

  return (
    <Animated.View 
      style={[
        {
          transform: [
            { rotate: rotate },
            { translateX: translateX },
            { translateY: Animated.add(floatY, new Animated.Value(translateY)) }
          ]
        }
      ]}
      className={`absolute w-24 h-36 rounded-xl shadow-sm border border-white/20 ${color} items-center justify-center`}
    >
      <View className="w-12 h-12 rounded-full bg-white/20 mb-2" />
      <View className="w-16 h-2 bg-white/20 rounded-full mb-1" />
      <View className="w-10 h-2 bg-white/20 rounded-full" />
    </Animated.View>
  );
};

export default function EmptyState({ 
  title = "Aucune donnée trouvée", 
  message = "Tirez vers le bas pour rafraîchir ou réessayez.",
  onRefresh,
  buttonText = "Recharger"
}: EmptyStateProps) {
  return (
    <View className="flex-1 items-center justify-center py-10 px-6">
      {/* Floating Cards Animation */}
      <View className="h-48 w-full items-center justify-center mb-8 relative">
        <FloatingCard 
          index={0} 
          color="bg-gray-300" 
          rotate="-15deg" 
          translateX={-40} 
          translateY={10} 
        />
        <FloatingCard 
          index={1} 
          color="bg-gray-400" 
          rotate="15deg" 
          translateX={40} 
          translateY={-10} 
        />
        <FloatingCard 
          index={2} 
          color="bg-[#1F2937]" 
          rotate="0deg" 
          translateX={0} 
          translateY={0} 
        />
      </View>

      <Text className="text-xl font-bold text-gray-800 text-center mb-2">{title}</Text>
      <Text className="text-gray-500 text-center mb-8 px-4 leading-5">{message}</Text>

      {onRefresh && (
        <TouchableOpacity 
          onPress={onRefresh}
          className="flex-row items-center bg-[#1F2937] px-6 py-3.5 rounded-xl shadow-lg active:bg-gray-800"
        >
          <ArrowPathIcon size={20} color="white" />
          <Text className="text-white font-semibold ml-2">{buttonText}</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}
