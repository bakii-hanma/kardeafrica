import { View, Text, ScrollView, TouchableOpacity, RefreshControl } from 'react-native';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeftIcon, BellIcon, CheckCircleIcon, GiftIcon, TagIcon } from 'react-native-heroicons/outline';
import { useState } from 'react';

const NOTIFICATIONS = [
  {
    id: 1,
    type: 'promo',
    title: 'Super Promo Netflix !',
    message: 'Profitez de -20% sur toutes les cartes Netflix ce week-end uniquement.',
    time: 'Il y a 2h',
    read: false,
    icon: TagIcon,
    color: 'bg-purple-100 text-purple-600',
  },
  {
    id: 2,
    type: 'order',
    title: 'Commande confirmée',
    message: 'Votre carte cadeau Spotify a été envoyée avec succès.',
    time: 'Hier',
    read: true,
    icon: CheckCircleIcon,
    color: 'bg-green-100 text-green-600',
  },
  {
    id: 3,
    type: 'gift',
    title: 'Vous avez reçu un cadeau !',
    message: 'Alex vous a envoyé une carte cadeau Amazon de 50€.',
    time: 'Il y a 2j',
    read: true,
    icon: GiftIcon,
    color: 'bg-orange-100 text-orange-600',
  },
  {
    id: 4,
    type: 'system',
    title: 'Mise à jour de l\'application',
    message: 'Découvrez les nouvelles fonctionnalités de KardAfrica.',
    time: 'Il y a 5j',
    read: true,
    icon: BellIcon,
    color: 'bg-blue-100 text-blue-600',
  }
];

export default function NotificationsScreen() {
  const router = useRouter();
  const [refreshing, setRefreshing] = useState(false);

  const onRefresh = () => {
    setRefreshing(true);
    // Simulate refresh
    setTimeout(() => {
      setRefreshing(false);
    }, 1000);
  };

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      {/* Header */}
      <View className="bg-white px-4 pt-2 pb-4 shadow-sm z-50 flex-row items-center justify-between">
        <View className="flex-row items-center">
          <TouchableOpacity onPress={() => router.back()} className="mr-3">
            <ArrowLeftIcon size={24} color="#1F2937" />
          </TouchableOpacity>
          <Text className="text-xl font-bold text-gray-900">Notifications</Text>
        </View>
        <TouchableOpacity>
          <Text className="text-[#44A08D] text-sm font-semibold">Tout marquer lu</Text>
        </TouchableOpacity>
      </View>

      {/* Notifications List */}
      <ScrollView 
        className="flex-1 px-4 pt-4" 
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#1F2937']} tintColor="#1F2937" />
        }
      >
        {NOTIFICATIONS.map((notif) => {
          const Icon = notif.icon;
          return (
            <TouchableOpacity 
              key={notif.id}
              className={`mb-3 p-4 rounded-2xl border ${notif.read ? 'bg-white border-gray-100' : 'bg-blue-50/50 border-blue-100'}`}
            >
              <View className="flex-row">
                <View className={`w-10 h-10 rounded-full items-center justify-center mr-3 ${notif.color.split(' ')[0]}`}>
                  <Icon size={20} className={notif.color.split(' ')[1]} color="#4B5563" />
                </View>
                <View className="flex-1">
                  <View className="flex-row justify-between items-start mb-1">
                    <Text className={`text-sm font-bold flex-1 mr-2 ${notif.read ? 'text-gray-900' : 'text-gray-900'}`}>
                      {notif.title}
                    </Text>
                    <Text className="text-xs text-gray-400 font-medium">{notif.time}</Text>
                  </View>
                  <Text className="text-xs text-gray-500 leading-5" numberOfLines={2}>
                    {notif.message}
                  </Text>
                </View>
                {!notif.read && (
                  <View className="w-2 h-2 bg-blue-500 rounded-full mt-1.5 ml-2" />
                )}
              </View>
            </TouchableOpacity>
          );
        })}
        
        <View className="h-8" />
      </ScrollView>
    </SafeAreaView>
  );
}
