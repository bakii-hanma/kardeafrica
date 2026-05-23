import { View, Text, TouchableOpacity, ScrollView, TextInput, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useState } from 'react';
import { useRouter } from 'expo-router';
import { ChevronLeftIcon, LockClosedIcon, EyeIcon, EyeSlashIcon } from 'react-native-heroicons/outline';
import { AuthService } from '../../services/auth';
import { useAlert } from '../../context/AlertContext';

export default function SecurityScreen() {
  const router = useRouter();
  const { showAlert } = useAlert();
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [isSecurityLoading, setIsSecurityLoading] = useState(false);

  const handleChangePassword = async () => {
    if (!currentPassword || !newPassword) {
      showAlert({ title: 'Champs requis', message: 'Veuillez remplir tous les champs.', variant: 'warning' });
      return;
    }

    if (newPassword.length < 8) {
      showAlert({ title: 'Mot de passe trop court', message: 'Le nouveau mot de passe doit contenir au moins 8 caractères.', variant: 'warning' });
      return;
    }

    setIsSecurityLoading(true);
    const response = await AuthService.changePassword(currentPassword, newPassword);
    setIsSecurityLoading(false);

    if (response.status === 'success') {
      setCurrentPassword('');
      setNewPassword('');
      showAlert({
        title: 'Mot de passe modifié',
        message: 'Ton mot de passe a été changé avec succès.',
        variant: 'success',
        buttons: [{ label: 'OK', variant: 'primary', onPress: () => router.back() }],
      });
    } else {
      showAlert({
        title: 'Erreur',
        message: response.message || 'Erreur lors du changement de mot de passe.',
        variant: 'error',
      });
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      <View className="flex-row items-center p-4 border-b border-gray-100 bg-white">
        <TouchableOpacity onPress={() => router.back()} className="p-2 mr-2 bg-gray-50 rounded-full">
          <ChevronLeftIcon size={24} color="#0F172A" />
        </TouchableOpacity>
        <Text className="text-xl font-bold text-gray-900">Sécurité</Text>
      </View>

      <ScrollView className="flex-1 p-6">
        <View className="space-y-6">
          <Text className="text-gray-600">
            Modifiez votre mot de passe pour sécuriser votre compte.
          </Text>

          <View>
            <Text className="text-gray-500 mb-1 ml-1">Mot de passe actuel</Text>
            <View className="relative">
              <View className="absolute left-4 top-4 z-10">
                <LockClosedIcon size={20} color="#9CA3AF" />
              </View>
              <TextInput
                value={currentPassword}
                onChangeText={setCurrentPassword}
                className="bg-white p-4 pl-12 rounded-xl border border-gray-200 text-gray-900"
                placeholder="Votre mot de passe actuel"
                secureTextEntry={!showCurrentPassword}
              />
              <TouchableOpacity 
                onPress={() => setShowCurrentPassword(!showCurrentPassword)}
                className="absolute right-4 top-4"
              >
                {showCurrentPassword ? (
                  <EyeSlashIcon size={20} color="#9CA3AF" />
                ) : (
                  <EyeIcon size={20} color="#9CA3AF" />
                )}
              </TouchableOpacity>
            </View>
          </View>

          <View>
            <Text className="text-gray-500 mb-1 ml-1">Nouveau mot de passe</Text>
            <View className="relative">
              <View className="absolute left-4 top-4 z-10">
                <LockClosedIcon size={20} color="#9CA3AF" />
              </View>
              <TextInput
                value={newPassword}
                onChangeText={setNewPassword}
                className="bg-white p-4 pl-12 rounded-xl border border-gray-200 text-gray-900"
                placeholder="Votre nouveau mot de passe"
                secureTextEntry={!showNewPassword}
              />
              <TouchableOpacity 
                onPress={() => setShowNewPassword(!showNewPassword)}
                className="absolute right-4 top-4"
              >
                {showNewPassword ? (
                  <EyeSlashIcon size={20} color="#9CA3AF" />
                ) : (
                  <EyeIcon size={20} color="#9CA3AF" />
                )}
              </TouchableOpacity>
            </View>
            <Text className="text-xs text-gray-400 mt-1 ml-1">Minimum 8 caractères</Text>
          </View>
        </View>
      </ScrollView>

      <View className="p-6 border-t border-gray-100 bg-white">
        <TouchableOpacity 
          onPress={handleChangePassword}
          disabled={isSecurityLoading}
          className="bg-[#0F172A] p-4 rounded-xl items-center justify-center flex-row"
        >
          {isSecurityLoading ? (
            <ActivityIndicator color="white" className="mr-2" />
          ) : null}
          <Text className="text-white font-bold text-lg">Changer le mot de passe</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}
