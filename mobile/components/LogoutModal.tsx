import React from 'react';
import { View, Text, TouchableOpacity, Modal, TouchableWithoutFeedback } from 'react-native';
import { ArrowRightOnRectangleIcon, XMarkIcon, ExclamationTriangleIcon } from 'react-native-heroicons/outline';

interface LogoutModalProps {
  visible: boolean;
  onClose: () => void;
  onConfirm: () => void;
}

export default function LogoutModal({ 
  visible, 
  onClose, 
  onConfirm 
}: LogoutModalProps) {

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
                <Text className="text-xl font-bold text-gray-900">Déconnexion</Text>
                <TouchableOpacity onPress={onClose} className="p-2 bg-gray-100 rounded-full">
                  <XMarkIcon size={20} color="#6B7280" />
                </TouchableOpacity>
              </View>

              <View className="items-center mb-8">
                <View className="w-16 h-16 bg-red-50 rounded-full items-center justify-center mb-4">
                  <ExclamationTriangleIcon size={32} color="#EF4444" />
                </View>
                <Text className="text-gray-900 font-semibold text-lg mb-2">
                  Êtes-vous sûr ?
                </Text>
                <Text className="text-gray-600 text-center text-base leading-6">
                  Vous devrez vous reconnecter pour accéder à votre profil et passer des commandes.
                </Text>
              </View>

              <View className="space-y-3">
                <TouchableOpacity 
                  onPress={onConfirm}
                  className="w-full bg-red-500 py-4 rounded-xl items-center justify-center flex-row shadow-lg shadow-red-500/20"
                >
                  <ArrowRightOnRectangleIcon size={20} color="white" />
                  <Text className="text-white font-bold text-lg ml-2">Se déconnecter</Text>
                </TouchableOpacity>
                
                <TouchableOpacity 
                  onPress={onClose}
                  className="w-full bg-gray-100 py-4 rounded-xl items-center justify-center"
                >
                  <Text className="text-gray-700 font-bold text-lg">Annuler</Text>
                </TouchableOpacity>
              </View>
            </View>
          </TouchableWithoutFeedback>
        </View>
      </TouchableWithoutFeedback>
    </Modal>
  );
}
