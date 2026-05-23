import React from 'react';
import { View, Text, TouchableOpacity, Modal, TouchableWithoutFeedback } from 'react-native';
import { useRouter } from 'expo-router';
import { UserIcon, XMarkIcon } from 'react-native-heroicons/outline';

interface LoginRequiredModalProps {
  visible: boolean;
  onClose: () => void;
  message?: string;
}

export default function LoginRequiredModal({ 
  visible, 
  onClose, 
  message = "Vous devez être connecté pour effectuer cette action." 
}: LoginRequiredModalProps) {
  const router = useRouter();

  const handleLogin = () => {
    onClose();
    router.push('/login');
  };

  return (
    <Modal
      visible={visible}
      transparent={true}
      animationType="slide"
      onRequestClose={onClose}
    >
      <TouchableWithoutFeedback onPress={onClose}>
        <View className="flex-1 bg-black/50 justify-end">
          <TouchableWithoutFeedback onPress={(e) => e.stopPropagation()}>
            <View className="bg-white rounded-t-3xl p-6 pb-10 shadow-xl">
              <View className="flex-row justify-between items-center mb-6">
                <Text className="text-xl font-bold text-gray-900">Connexion requise</Text>
                <TouchableOpacity onPress={onClose} className="p-2 bg-gray-100 rounded-full">
                  <XMarkIcon size={20} color="#6B7280" />
                </TouchableOpacity>
              </View>

              <View className="items-center mb-8">
                <View className="w-16 h-16 bg-blue-50 rounded-full items-center justify-center mb-4">
                  <UserIcon size={32} color="#3B82F6" />
                </View>
                <Text className="text-gray-600 text-center text-base leading-6">
                  {message}
                </Text>
              </View>

              <View className="space-y-3">
                <TouchableOpacity 
                  onPress={handleLogin}
                  className="w-full bg-[#1F2937] py-4 rounded-xl items-center justify-center flex-row shadow-lg shadow-gray-900/20"
                >
                  <Text className="text-white font-bold text-lg mr-2">Se connecter</Text>
                  <UserIcon size={20} color="white" />
                </TouchableOpacity>
                
                <TouchableOpacity 
                  onPress={onClose}
                  className="w-full bg-gray-100 py-4 rounded-xl items-center justify-center"
                >
                  <Text className="text-gray-700 font-bold text-lg">Plus tard</Text>
                </TouchableOpacity>
              </View>
            </View>
          </TouchableWithoutFeedback>
        </View>
      </TouchableWithoutFeedback>
    </Modal>
  );
}
