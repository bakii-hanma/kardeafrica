import React, { useEffect, useState, useRef } from 'react';
import { View, Text, TouchableOpacity, Modal, Animated } from 'react-native';
import { CheckCircleIcon } from 'react-native-heroicons/outline';

interface SuccessModalProps {
  visible: boolean;
  title?: string;
  message: string;
  buttonText?: string;
  onClose: () => void;
  autoClose?: number;
}

const SuccessModal: React.FC<SuccessModalProps> = ({
  visible,
  title = "Succès",
  message,
  buttonText = "Continuer",
  onClose,
  autoClose = 0
}) => {
  const [showModal, setShowModal] = useState(visible);
  const scaleValue = useRef(new Animated.Value(0)).current;
  const opacityValue = useRef(new Animated.Value(0)).current;
  const checkScale = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (visible) {
      setShowModal(true);
      checkScale.setValue(0);
      Animated.parallel([
        Animated.spring(scaleValue, {
          toValue: 1,
          friction: 5,
          tension: 40,
          useNativeDriver: true
        }),
        Animated.timing(opacityValue, {
          toValue: 1,
          duration: 200,
          useNativeDriver: true
        })
      ]).start(() => {
        Animated.spring(checkScale, {
          toValue: 1,
          friction: 3,
          tension: 100,
          useNativeDriver: true
        }).start();
      });

      if (autoClose > 0) {
        const timer = setTimeout(onClose, autoClose);
        return () => clearTimeout(timer);
      }
    } else {
      Animated.parallel([
        Animated.timing(scaleValue, {
          toValue: 0,
          duration: 200,
          useNativeDriver: true
        }),
        Animated.timing(opacityValue, {
          toValue: 0,
          duration: 200,
          useNativeDriver: true
        })
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
      <View className="flex-1 justify-center items-center bg-black/50 px-6">
        <Animated.View
          style={{
            transform: [{ scale: scaleValue }],
            opacity: opacityValue
          }}
          className="bg-white w-full max-w-sm rounded-3xl overflow-hidden shadow-2xl"
        >
          {/* Header vert */}
          <View className="bg-[#44A08D] pt-10 pb-12 items-center relative overflow-hidden">
            <View className="absolute -top-8 -right-8 w-24 h-24 rounded-full"
                  style={{ backgroundColor: 'rgba(255,255,255,0.1)' }} />
            <View className="absolute -bottom-6 -left-6 w-20 h-20 rounded-full"
                  style={{ backgroundColor: 'rgba(255,255,255,0.08)' }} />

            <Animated.View style={{ transform: [{ scale: checkScale }] }}>
              <View className="w-20 h-20 rounded-full items-center justify-center"
                    style={{ backgroundColor: 'rgba(255,255,255,0.2)', borderWidth: 2, borderColor: 'rgba(255,255,255,0.4)' }}>
                <View className="w-14 h-14 bg-white rounded-full items-center justify-center">
                  <CheckCircleIcon size={32} color="#44A08D" />
                </View>
              </View>
            </Animated.View>
          </View>

          {/* Contenu */}
          <View className="px-6 pt-6 pb-2 items-center" style={{ marginTop: -20 }}>
            <View className="bg-white rounded-2xl px-5 pt-5 pb-4 items-center w-full"
                  style={{ shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.06, shadowRadius: 8, elevation: 3 }}>
              <Text className="text-xl font-bold text-gray-900 mb-2 text-center">
                {title}
              </Text>
              <Text className="text-gray-500 text-center leading-5 text-sm">
                {message}
              </Text>
            </View>
          </View>

          {/* Bouton */}
          <View className="px-6 pt-2 pb-6">
            <TouchableOpacity
              onPress={onClose}
              className="w-full bg-[#44A08D] py-4 rounded-xl items-center active:opacity-80"
              style={{ shadowColor: '#44A08D', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 8, elevation: 4 }}
            >
              <Text className="text-white text-center font-bold text-base">
                {buttonText}
              </Text>
            </TouchableOpacity>
          </View>
        </Animated.View>
      </View>
    </Modal>
  );
};

export default SuccessModal;
