import { View, Text, TouchableOpacity, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { ChevronLeftIcon, EnvelopeIcon, PhoneIcon } from 'react-native-heroicons/outline';

export default function HelpScreen() {
  const router = useRouter();

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      <View className="flex-row items-center p-4 border-b border-gray-100 bg-white">
        <TouchableOpacity onPress={() => router.back()} className="p-2 mr-2 bg-gray-50 rounded-full">
          <ChevronLeftIcon size={24} color="#0F172A" />
        </TouchableOpacity>
        <Text className="text-xl font-bold text-gray-900">Aide & Support</Text>
      </View>

      <ScrollView className="flex-1 p-6">
        <View className="space-y-6">
          <View className="bg-blue-50 p-4 rounded-xl flex-row items-center space-x-4">
            <View className="bg-blue-100 p-2 rounded-full">
              <EnvelopeIcon size={24} color="#3B82F6" />
            </View>
            <View className="flex-1">
              <Text className="font-bold text-gray-900">Contactez-nous par email</Text>
              <Text className="text-gray-600">support@kardafrica.com</Text>
            </View>
          </View>

          <View className="bg-green-50 p-4 rounded-xl flex-row items-center space-x-4">
            <View className="bg-green-100 p-2 rounded-full">
              <PhoneIcon size={24} color="#22C55E" />
            </View>
            <View className="flex-1">
              <Text className="font-bold text-gray-900">Appelez-nous</Text>
              <Text className="text-gray-600">+241 01 23 45 67</Text>
            </View>
          </View>

          <View className="mt-4">
            <Text className="font-bold text-lg text-gray-900 mb-4">FAQ</Text>
            
            <View className="space-y-4">
              <View className="bg-white border border-gray-200 rounded-xl p-4">
                <Text className="font-bold text-gray-800 mb-2">Comment recharger mon compte ?</Text>
                <Text className="text-gray-600">Vous pouvez recharger votre compte via Mobile Money ou carte bancaire dans la section Portefeuille.</Text>
              </View>

              <View className="bg-white border border-gray-200 rounded-xl p-4">
                <Text className="font-bold text-gray-800 mb-2">Les transactions sont-elles sécurisées ?</Text>
                <Text className="text-gray-600">Oui, toutes nos transactions sont cryptées et sécurisées selon les normes internationales.</Text>
              </View>
            </View>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
