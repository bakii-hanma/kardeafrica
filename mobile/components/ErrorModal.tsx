import React, { useEffect, useState, useRef } from 'react';
import { View, Text, TouchableOpacity, Modal, Animated } from 'react-native';
import { X, AlertTriangle } from 'lucide-react-native';

interface ErrorModalProps {
  visible: boolean;
  message: string;
  onClose: () => void;
}

const ErrorModal: React.FC<ErrorModalProps> = ({ visible, message, onClose }) => {
  const [showModal, setShowModal] = useState(visible);
  const translateY = useRef(new Animated.Value(100)).current;
  const opacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (visible) {
      setShowModal(true);
      Animated.parallel([
        Animated.spring(translateY, { toValue: 0, friction: 6, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 1, duration: 200, useNativeDriver: true })
      ]).start();
    } else {
      Animated.parallel([
        Animated.timing(translateY, { toValue: 100, duration: 200, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 0, duration: 200, useNativeDriver: true })
      ]).start(() => {
        setShowModal(false);
      });
    }
  }, [visible]);

  if (!showModal) return null;

  return (
    <Modal
      transparent
      visible={showModal}
      animationType="none"
      onRequestClose={onClose}
    >
      <View className="flex-1 justify-end items-center pb-10 bg-black/30">
        <TouchableOpacity 
          className="absolute inset-0" 
          activeOpacity={1} 
          onPress={onClose}
        />
        
        <Animated.View 
          style={{
            transform: [{ translateY }],
            opacity,
          }}
          className="w-[90%] bg-white rounded-2xl shadow-xl overflow-hidden border-l-4 border-red-500"
        >
          <View className="p-4 flex-row items-start">
            <View className="bg-red-100 p-2 rounded-full mr-3">
              <AlertTriangle size={24} color="#ef4444" />
            </View>
            
            <View className="flex-1 pt-1">
              <Text className="text-lg font-bold text-gray-800 mb-1">
                Oups !
              </Text>
              <Text className="text-gray-600 text-sm leading-5">
                {message}
              </Text>
            </View>

            <TouchableOpacity 
              onPress={onClose}
              className="p-1 -mt-1 -mr-1"
            >
              <X size={20} color="#9ca3af" />
            </TouchableOpacity>
          </View>
          
          <View className="h-1 bg-gray-100 w-full" />
          
          <TouchableOpacity 
            onPress={onClose}
            className="w-full py-3 items-center active:bg-gray-50"
          >
            <Text className="text-red-500 font-semibold text-sm">
              Fermer
            </Text>
          </TouchableOpacity>
        </Animated.View>
      </View>
    </Modal>
  );
};

export default ErrorModal;
