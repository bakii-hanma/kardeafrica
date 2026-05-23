import { View, Text, TouchableOpacity, ScrollView, TextInput, ActivityIndicator, Image } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useState, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useRouter } from 'expo-router';
import { ChevronLeftIcon, CameraIcon, UserIcon, MapPinIcon } from 'react-native-heroicons/outline';
import * as ImagePicker from 'expo-image-picker';
import { AuthService } from '../../services/auth';
import CustomDatePicker from '../../components/CustomDatePicker';
import CountryPicker, { COUNTRIES } from '../../components/CountryPicker';
import SuccessModal from '../../components/SuccessModal';
import { useAlert } from '../../context/AlertContext';

export default function EditProfileScreen() {
  const router = useRouter();
  const { showAlert } = useAlert();
  const [isLoading, setIsLoading] = useState(false);
  
  const [editFirstName, setEditFirstName] = useState('');
  const [editLastName, setEditLastName] = useState('');
  const [editPhone, setEditPhone] = useState('');
  const [editDateOfBirth, setEditDateOfBirth] = useState('');
  const [editGender, setEditGender] = useState('');
  const [editCountry, setEditCountry] = useState('GA'); // Default to Gabon (ISO)
  const [editCity, setEditCity] = useState('');
  const [editAddress, setEditAddress] = useState('');
  
  const [avatar, setAvatar] = useState<{ uri: string; type: string; name: string } | null>(null);
  const [currentAvatarUrl, setCurrentAvatarUrl] = useState<string | null>(null);
  const [imageError, setImageError] = useState(false);
  const [countryPickerVisible, setCountryPickerVisible] = useState(false);
  const [showSuccessModal, setShowSuccessModal] = useState(false);

  // Helper to get country name for display
  const getCountryName = (codeOrName: string) => {
    if (!codeOrName) return '';
    const country = COUNTRIES.find(c => c.iso === codeOrName || c.name === codeOrName);
    return country ? country.name : codeOrName;
  };

  // Helper to get country ISO for saving (if we have name)
  const getCountryIso = (codeOrName: string) => {
    if (!codeOrName) return '';
    const country = COUNTRIES.find(c => c.iso === codeOrName || c.name === codeOrName);
    return country ? country.iso : codeOrName;
  };

  useEffect(() => {
    loadUserData();
  }, []);

  const loadUserData = async () => {
    try {
      const userData = await AsyncStorage.getItem('user');
      if (userData) {
        const user = JSON.parse(userData);
        setEditFirstName(user?.profile?.first_name || '');
        setEditLastName(user?.profile?.last_name || '');
        setEditPhone(user?.phone || '');
        setEditDateOfBirth(user?.profile?.date_of_birth || '');
        setEditGender(user?.profile?.gender || '');
        // Ensure we try to store ISO code if possible, or keep what we have
        const countryVal = user?.profile?.country || 'GA';
        setEditCountry(getCountryIso(countryVal));
        setEditCity(user?.profile?.city || '');
        setEditAddress(user?.profile?.address || '');
        // Assuming the backend returns the full avatar URL in user.profile.avatar or user.avatar
        setCurrentAvatarUrl(user?.profile?.avatar || user?.avatar || null);
      }
    } catch (error) {
      console.error('Error loading user data:', error);
    }
  };

  const pickImage = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      allowsEditing: true,
      aspect: [1, 1],
      quality: 0.5,
    });

    if (!result.canceled) {
      const asset = result.assets[0];
      setAvatar({
        uri: asset.uri,
        type: asset.mimeType || 'image/jpeg',
        name: asset.fileName || 'avatar.jpg',
      });
    }
  };

  const handleSaveProfile = async () => {
    setIsLoading(true);
    const response = await AuthService.updateProfile(
      editFirstName, 
      editLastName, 
      editPhone, 
      editDateOfBirth,
      editGender,
      editCountry,
      editCity,
      editAddress,
      avatar
    );
    setIsLoading(false);
    
    if (response.status === 'success' && response.data) {
      const updatedUser = response.data.user;
      await AsyncStorage.setItem('user', JSON.stringify(updatedUser));
      setShowSuccessModal(true);
    } else {
      showAlert({
        title: 'Erreur',
        message: response.message || 'Erreur lors de la mise à jour de ton profil.',
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
        <Text className="text-xl font-bold text-gray-900">Modifier le profil</Text>
      </View>

      <ScrollView className="flex-1 p-6">
        <View className="items-center mb-8">
          <TouchableOpacity onPress={pickImage} className="relative">
            <View className="w-28 h-28 rounded-full bg-white items-center justify-center overflow-hidden border-4 border-white shadow-sm">
              {avatar ? (
                <Image source={{ uri: avatar.uri }} className="w-full h-full" resizeMode="cover" />
              ) : currentAvatarUrl && !imageError ? (
                <Image 
                  source={{ uri: currentAvatarUrl }} 
                  className="w-full h-full" 
                  resizeMode="cover" 
                  onError={() => setImageError(true)}
                />
              ) : (
                <View className="w-full h-full bg-gray-100 items-center justify-center">
                  <UserIcon size={48} color="#9CA3AF" />
                </View>
              )}
            </View>
            <View className="absolute bottom-0 right-0 bg-[#0F172A] p-2.5 rounded-full border-4 border-gray-50">
              <CameraIcon size={16} color="white" />
            </View>
          </TouchableOpacity>
          <Text className="text-gray-500 text-sm mt-3">Appuyez pour modifier la photo</Text>
        </View>

        <View className="space-y-4 pb-8">
          <View>
            <Text className="text-gray-500 mb-1 ml-1">Prénom</Text>
            <TextInput
              value={editFirstName}
              onChangeText={setEditFirstName}
              className="bg-white p-4 rounded-xl border border-gray-200 text-gray-900"
              placeholder="Votre prénom"
            />
          </View>

          <View>
            <Text className="text-gray-500 mb-1 ml-1">Nom</Text>
            <TextInput
              value={editLastName}
              onChangeText={setEditLastName}
              className="bg-white p-4 rounded-xl border border-gray-200 text-gray-900"
              placeholder="Votre nom"
            />
          </View>

          <View>
            <Text className="text-gray-500 mb-1 ml-1">Téléphone</Text>
            <TextInput
              value={editPhone}
              onChangeText={setEditPhone}
              className="bg-white p-4 rounded-xl border border-gray-200 text-gray-900"
              placeholder="Votre numéro de téléphone"
              keyboardType="phone-pad"
            />
          </View>

          <View>
            <CustomDatePicker
              label="Date de naissance"
              value={editDateOfBirth}
              onChange={(date) => {
                const year = date.getFullYear();
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const day = date.getDate().toString().padStart(2, '0');
                setEditDateOfBirth(`${year}-${month}-${day}`);
              }}
              placeholder="Sélectionner votre date de naissance"
              maximumDate={new Date()} // Date max = aujourd'hui
            />
          </View>

          <View>
            <Text className="text-gray-500 mb-1 ml-1">Genre</Text>
            <View className="flex-row space-x-2">
              {['male', 'female', 'other'].map((gender) => (
                <TouchableOpacity
                  key={gender}
                  onPress={() => setEditGender(gender)}
                  className={`flex-1 p-3 rounded-xl border ${
                    editGender === gender 
                      ? 'bg-[#0F172A] border-[#0F172A]' 
                      : 'bg-white border-gray-200'
                  } items-center`}
                >
                  <Text className={`font-medium ${
                    editGender === gender ? 'text-white' : 'text-gray-900'
                  }`}>
                    {gender === 'male' ? 'Homme' : gender === 'female' ? 'Femme' : 'Autre'}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>

          <View>
            <Text className="text-gray-500 mb-1 ml-1">Pays</Text>
            <TouchableOpacity 
              onPress={() => setCountryPickerVisible(true)}
              className="bg-white p-4 rounded-xl border border-gray-200 flex-row justify-between items-center"
            >
              <Text className={`text-base ${editCountry ? 'text-gray-900' : 'text-gray-400'}`}>
                {getCountryName(editCountry) || 'Sélectionner un pays'}
              </Text>
              <MapPinIcon size={20} color="#9CA3AF" />
            </TouchableOpacity>
            
            <CountryPicker
              visible={countryPickerVisible}
              onClose={() => setCountryPickerVisible(false)}
              onSelect={(country) => {
                setEditCountry(country.iso);
                setCountryPickerVisible(false);
              }}
              selectedCode={editCountry}
            />
          </View>

          <View>
            <Text className="text-gray-500 mb-1 ml-1">Ville</Text>
            <TextInput
              value={editCity}
              onChangeText={setEditCity}
              className="bg-white p-4 rounded-xl border border-gray-200 text-gray-900"
              placeholder="Votre ville"
            />
          </View>

          <View>
            <Text className="text-gray-500 mb-1 ml-1">Adresse</Text>
            <TextInput
              value={editAddress}
              onChangeText={setEditAddress}
              className="bg-white p-4 rounded-xl border border-gray-200 text-gray-900"
              placeholder="Votre adresse complète"
              multiline
              numberOfLines={3}
              style={{ textAlignVertical: 'top' }}
            />
          </View>
        </View>
      </ScrollView>

      <View className="p-6 border-t border-gray-100 bg-white">
        <TouchableOpacity 
          onPress={handleSaveProfile}
          disabled={isLoading}
          className="bg-[#0F172A] p-4 rounded-xl items-center justify-center flex-row"
        >
          {isLoading ? (
            <ActivityIndicator color="white" className="mr-2" />
          ) : null}
          <Text className="text-white font-bold text-lg">Enregistrer les modifications</Text>
        </TouchableOpacity>
      </View>

      <SuccessModal
        visible={showSuccessModal}
        title="Succès !"
        message="Votre profil a été mis à jour avec succès."
        onClose={() => {
          setShowSuccessModal(false);
          router.back();
        }}
      />
    </SafeAreaView>
  );
}
