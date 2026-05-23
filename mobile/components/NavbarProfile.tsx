import React, { useState, useCallback } from 'react';
import { View, Text, Image, TouchableOpacity } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useFocusEffect, useRouter } from 'expo-router';
import { UserIcon } from 'react-native-heroicons/solid';

const NavbarProfile = () => {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [imageError, setImageError] = useState(false);

  useFocusEffect(
    useCallback(() => {
      const loadUser = async () => {
        try {
          const userData = await AsyncStorage.getItem('user');
          if (userData) {
            setUser(JSON.parse(userData));
          } else {
            setUser(null);
          }
        } catch (error) {
          console.error('Failed to load user', error);
        }
      };

      loadUser();
    }, [])
  );

  const handlePress = () => {
    router.push('/(tabs)/profile');
  };

  const getAvatarUrl = () => {
    if (!user) return null;
    return user.profile?.avatar || user.avatar || null;
  };

  const getInitials = () => {
    if (!user) return 'U';
    const firstName = user.profile?.first_name || user.first_name || '';
    const lastName = user.profile?.last_name || user.last_name || '';
    if (firstName) return firstName[0].toUpperCase();
    if (lastName) return lastName[0].toUpperCase();
    return 'U';
  };

  const avatarUrl = getAvatarUrl();

  if (!user) {
    return (
      <TouchableOpacity onPress={handlePress}>
        <View className="w-10 h-10 rounded-full bg-gray-700 items-center justify-center border border-gray-600">
           <UserIcon size={20} color="#9CA3AF" />
        </View>
      </TouchableOpacity>
    );
  }

  return (
    <TouchableOpacity onPress={handlePress}>
      {avatarUrl && !imageError ? (
        <Image 
          source={{ uri: avatarUrl }} 
          className="w-10 h-10 rounded-full bg-gray-700 border border-gray-600"
          onError={() => setImageError(true)}
        />
      ) : (
        <View className="w-10 h-10 rounded-full bg-indigo-600 items-center justify-center border border-indigo-400 shadow-sm">
          <Text className="text-white font-bold text-lg">
            {getInitials()}
          </Text>
        </View>
      )}
    </TouchableOpacity>
  );
};

export default NavbarProfile;
