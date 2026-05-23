import React, { useEffect, useState, useRef } from 'react';
import { View, Text, Modal, TouchableOpacity, Dimensions, Image, StyleSheet, Animated, Easing } from 'react-native';
import { CheckCircleIcon, XMarkIcon } from 'react-native-heroicons/outline';
import { ShoppingBagIcon } from 'react-native-heroicons/solid';
import { convertAndFormatToFCFA } from '../utils/currency';

const { width, height } = Dimensions.get('window');

interface AddToCartModalProps {
  visible: boolean;
  onClose: () => void;
  onGoToCart: () => void;
  product: {
    name: string;
    brandName?: string;
    image?: string;
    price: number;
    currency: string;
    color?: string;
  } | null;
}

export default function AddToCartModal({ visible, onClose, onGoToCart, product }: AddToCartModalProps) {
  const [showModal, setShowModal] = useState(visible);
  const translateY = useRef(new Animated.Value(height)).current;
  const floatY = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (visible) {
      setShowModal(true);
      // Reset values
      translateY.setValue(height);
      scale.setValue(0);
      
      // Animate in
      Animated.parallel([
        Animated.spring(translateY, { toValue: 0, friction: 8, tension: 40, useNativeDriver: true }),
        Animated.spring(scale, { toValue: 1, friction: 8, tension: 40, useNativeDriver: true })
      ]).start();
      
      // Floating effect - Hyper light bounce
      Animated.loop(
        Animated.sequence([
          Animated.timing(floatY, { toValue: -3, duration: 2500, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
          Animated.timing(floatY, { toValue: 0, duration: 2500, easing: Easing.inOut(Easing.sin), useNativeDriver: true })
        ])
      ).start();

    } else {
      // Animate out
      Animated.timing(translateY, { 
        toValue: height, 
        duration: 300, 
        useNativeDriver: true 
      }).start(({ finished }) => {
        if (finished) {
          setShowModal(false);
        }
      });
    }
  }, [visible]);

  const handleClose = () => {
    onClose();
  };

  const handleGoToCart = () => {
    // We delay the navigation slightly to allow the modal to start closing
    onClose();
    setTimeout(() => {
        onGoToCart();
    }, 300);
  };

  if (!showModal) return null;

  return (
    <Modal
      transparent
      visible={showModal}
      animationType="none"
      onRequestClose={handleClose}
    >
      <View className="flex-1 justify-end bg-black/50">
        <TouchableOpacity 
          style={StyleSheet.absoluteFill} 
          activeOpacity={1} 
          onPress={handleClose}
        />
        
        <Animated.View 
          style={{ transform: [{ translateY }] }}
          className="bg-white rounded-t-3xl p-6 shadow-xl"
        >
          {/* Header with Close Button */}
          <View className="flex-row justify-between items-center mb-6">
            <View className="flex-row items-center">
              <View className="bg-green-100 p-2 rounded-full mr-3">
                <CheckCircleIcon size={24} color="#16a34a" />
              </View>
              <Text className="text-lg font-bold text-gray-800">Ajouté au panier !</Text>
            </View>
            <TouchableOpacity onPress={handleClose} className="bg-gray-100 p-2 rounded-full">
              <XMarkIcon size={20} color="#6b7280" />
            </TouchableOpacity>
          </View>

          {/* Product Preview Card */}
          <Animated.View 
            style={{ 
              transform: [
                { translateY: floatY },
                { scale }
              ] 
            }}
            className="flex-row items-center bg-gray-50 p-4 rounded-xl border border-gray-100 mb-8"
          >
            <View className={`w-16 h-16 rounded-lg items-center justify-center mr-4 ${product?.color || 'bg-gray-200'}`}>
               {product?.image ? (
                  <Image source={{ uri: product.image }} className="w-10 h-10" resizeMode="contain" />
               ) : (
                  <Text className="text-2xl">🎁</Text>
               )}
            </View>
            <View className="flex-1">
              <Text className="text-xs text-gray-500 font-medium uppercase mb-1">{product?.brandName || 'Carte Cadeau'}</Text>
              <Text className="font-bold text-gray-800 text-base mb-1" numberOfLines={1}>{product?.name}</Text>
              <Text className="font-bold text-indigo-600">
                {product ? convertAndFormatToFCFA(product.price, product.currency) : '0 FCFA'}
              </Text>
            </View>
          </Animated.View>

          {/* Action Buttons */}
          <View className="gap-3 mb-4">
            <TouchableOpacity 
              onPress={handleGoToCart}
              className="w-full bg-[#1F2937] py-4 rounded-xl flex-row justify-center items-center shadow-lg active:bg-gray-800"
            >
              <ShoppingBagIcon size={20} color="white" />
              <Text className="text-white font-bold ml-2 text-base">Voir le panier</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              onPress={handleClose}
              className="w-full bg-white py-4 rounded-xl border border-gray-200 items-center active:bg-gray-50"
            >
              <Text className="text-gray-600 font-bold text-base">Continuer mes achats</Text>
            </TouchableOpacity>
          </View>
        </Animated.View>
      </View>
    </Modal>
  );
}
