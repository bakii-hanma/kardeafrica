import { View, Text, ScrollView, Image, TouchableOpacity, ActivityIndicator, RefreshControl, Animated, Easing, Share } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { ArrowLeftIcon, ClipboardDocumentIcon, ShareIcon, EyeIcon, CheckIcon, InformationCircleIcon } from 'react-native-heroicons/outline';
import { useState, useEffect, useRef } from 'react';
import { SafeAreaView } from 'react-native-safe-area-context';
import { CardService, Card } from '../../services/card';
import { Config } from '../../constants/Config';
import * as Clipboard from 'expo-clipboard';
import { useAlert } from '../../context/AlertContext';

// Helper to generate brand colors (consistent with Home screen)
const getBrandColor = (brandName: string) => {
  const colors: Record<string, string> = {
    'Netflix': '#E50914',
    'Spotify': '#1DB954',
    'Apple': '#000000',
    'iTunes': '#D60017',
    'Amazon': '#FF9900',
    'PlayStation': '#00439C',
    'Xbox': '#107C10',
    'Google': '#4285F4',
    'Steam': '#171A21',
    'Nintendo': '#E60012',
    'Uber': '#000000',
    'Roblox': '#00A2FF',
  };
  
  if (!brandName) return '#4B5563';

  for (const [key, color] of Object.entries(colors)) {
    if (brandName.toLowerCase().includes(key.toLowerCase())) {
      return color;
    }
  }
  
  // Hash fallback
  const fallbackColors = ['#000000', '#1DB954', '#FF9900', '#E50914', '#00A4EF', '#FF0000', '#663399'];
  let hash = 0;
  for (let i = 0; i < brandName.length; i++) {
    hash = brandName.charCodeAt(i) + ((hash << 5) - hash);
  }
  return fallbackColors[Math.abs(hash % fallbackColors.length)];
};

// Helper to get text color based on background
const getTextColor = (backgroundColor: string) => {
  const lightColors = ['#FF9900', '#FCD34D', '#FFFFFF'];
  return lightColors.includes(backgroundColor) ? '#000000' : '#FFFFFF';
};

export default function CardDetailScreen() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const { showAlert } = useAlert();

  const [card, setCard] = useState<Card | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [isFlipped, setIsFlipped] = useState(false);

  // Animation values
  const rotation = useRef(new Animated.Value(0)).current;
  const floatY = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Start floating animation
    Animated.loop(
      Animated.sequence([
        Animated.timing(floatY, { toValue: -15, duration: 2000, useNativeDriver: true, easing: Easing.inOut(Easing.sin) }),
        Animated.timing(floatY, { toValue: 0, duration: 2000, useNativeDriver: true, easing: Easing.inOut(Easing.sin) })
      ])
    ).start();
    
    loadCard();
  }, [id]);

  const loadCard = async () => {
    try {
      setLoading(true);
      const data = await CardService.getCard(Number(id));
      setCard(data);
    } catch (error) {
      console.error('Error loading card:', error);
      showAlert({ title: 'Erreur', message: 'Impossible de charger la carte.', variant: 'error' });
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadCard();
  };

  const handleCardPress = () => {
    const toValue = isFlipped ? 0 : 1;
    
    Animated.timing(rotation, {
      toValue,
      duration: 800,
      easing: Easing.inOut(Easing.cubic),
      useNativeDriver: true
    }).start(() => {
      setIsFlipped(!isFlipped);
    });
  };

  const copyToClipboard = async () => {
    if (!card?.code) return;
    await Clipboard.setStringAsync(card.code);
    showAlert({ title: 'Code copié !', message: 'Le code a été copié dans le presse-papier.', variant: 'success' });
  };

  const shareCard = async () => {
    if (!card) return;
    try {
      await Share.share({
        message: `Voici mon code ${card.brand || card.name}: ${card.code}`,
      });
    } catch (error) {
      console.error(error);
    }
  };

  const spin = rotation.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '180deg']
  });

  const frontAnimatedStyle = {
    transform: [
      { perspective: 1000 },
      { rotateY: spin },
      { translateY: floatY }
    ],
    backfaceVisibility: 'hidden' as 'hidden', // Explicit type casting
    zIndex: isFlipped ? 0 : 1,
  };

  const backAnimatedStyle = {
    transform: [
      { perspective: 1000 },
      { rotateY: rotation.interpolate({ inputRange: [0, 1], outputRange: ['180deg', '360deg'] }) },
      { translateY: floatY }
    ],
    backfaceVisibility: 'hidden' as 'hidden',
    position: 'absolute' as 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    zIndex: isFlipped ? 1 : 0,
  };

  if (loading && !refreshing) {
    return (
      <SafeAreaView className="flex-1 bg-white items-center justify-center">
        <ActivityIndicator size="large" color="#1F2937" />
      </SafeAreaView>
    );
  }

  if (!card) {
    return (
      <SafeAreaView className="flex-1 bg-white items-center justify-center">
        <Text className="text-gray-500">Carte non trouvée</Text>
        <TouchableOpacity onPress={() => router.back()} className="mt-4 p-2 bg-gray-200 rounded-lg">
          <Text>Retour</Text>
        </TouchableOpacity>
      </SafeAreaView>
    );
  }

  const brandName = card.brand || card.name;
  const brandColor = getBrandColor(brandName);
  const textColor = getTextColor(brandColor);
  const formattedBalance = CardService.formatBalance(card.balance, card.currency);

  return (
    <SafeAreaView className="flex-1 bg-white">
      {/* Header */}
      <View className="flex-row justify-between items-center px-4 py-3 border-b border-gray-100">
        <TouchableOpacity onPress={() => router.back()}>
          <ArrowLeftIcon size={24} color="#1A202C" />
        </TouchableOpacity>
        <Text className="text-lg font-bold text-gray-800">Ma Carte</Text>
        <TouchableOpacity onPress={shareCard}>
          <ShareIcon size={24} color="#1A202C" />
        </TouchableOpacity>
      </View>

      <ScrollView 
        className="flex-1" 
        contentContainerStyle={{ paddingBottom: 100 }}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#1F2937']} tintColor="#1F2937" />
        }
      >
        {/* Card Visual Container */}
        <View className="bg-gray-50 p-6 items-center justify-center z-10 py-10 min-h-[400px]">
          <TouchableOpacity activeOpacity={1} onPress={handleCardPress} style={{ width: '100%', height: 260 }}>
            
            {/* Front Face */}
            <Animated.View 
              style={[frontAnimatedStyle]} 
              className="w-full h-full rounded-3xl shadow-2xl bg-white overflow-hidden absolute"
            >
              {/* Top Section */}
              <View style={{ backgroundColor: brandColor, height: '55%' }} className="p-6 relative overflow-hidden">
                 <Text className="text-white/80 font-bold text-xs tracking-widest uppercase mb-1">
                   {brandName}
                 </Text>
                 <Text style={{ color: textColor }} className="font-black text-4xl tracking-tighter shadow-sm" numberOfLines={1}>
                   {card.name}
                 </Text>
                 
                 <View className="absolute -right-6 -bottom-6 opacity-20">
                   <View className="w-32 h-32 rounded-full bg-white/30" />
                 </View>
                 <View className="absolute right-6 top-6 opacity-80">
                    <Text className="text-4xl">🎁</Text>
                 </View>
              </View>

              {/* Bottom Section */}
              <View className="flex-1 bg-white relative px-6 pb-6 pt-10 flex justify-between">
                 {/* Floating Logo */}
                 <View className="absolute -top-8 left-6 w-16 h-16 bg-white rounded-full p-1 shadow-md items-center justify-center z-20">
                    <View className="w-full h-full rounded-full bg-gray-50 items-center justify-center overflow-hidden border border-gray-100">
                       {card.image ? (
                         <Image 
                           source={{ uri: card.image.startsWith('http') ? card.image : `${Config.API_URL.replace('/api', '')}/storage/${card.image}` }} 
                           className="w-full h-full" 
                           resizeMode="cover"
                         />
                       ) : (
                         <Text className="text-xl font-bold text-gray-800">{brandName.charAt(0)}</Text>
                       )}
                    </View>
                 </View>
                 
                 <View className="flex-row justify-between items-start mt-2">
                    <View className="flex-1 pr-2">
                        <Text className="font-bold text-gray-900 text-lg line-clamp-2" numberOfLines={2}>
                            {card.name}
                        </Text>
                    </View>
                    <View className="bg-orange-50 px-2 py-1.5 rounded-lg border border-orange-100 shrink-0">
                        <Text className="text-orange-600 font-bold text-xs">
                            {formattedBalance}
                        </Text>
                    </View>
                 </View>

                 <View className="w-full mt-2">
                    <View className="flex-row justify-between text-xs mb-1">
                        <Text className="text-gray-400 text-xs">Expire le</Text>
                        <Text className="text-gray-600 text-xs font-medium">
                            {card.expiry_date ? new Date(card.expiry_date).toLocaleDateString('fr-FR') : 'Jamais'}
                        </Text>
                    </View>
                    <View className="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                        <View style={{ backgroundColor: brandColor, width: '100%' }} className="h-full rounded-full" />
                    </View>
                 </View>
              </View>
            </Animated.View>

            {/* Back Face */}
            <Animated.View 
              style={[backAnimatedStyle]} 
              className="w-full h-full rounded-3xl shadow-2xl bg-gray-900 overflow-hidden absolute justify-center items-center p-6"
            >
                <View className="absolute top-4 right-4 opacity-20">
                    <Text className="text-white text-6xl font-black">CODE</Text>
                </View>
                
                <Text className="text-gray-400 text-sm font-medium uppercase tracking-widest mb-2">Code Secret</Text>
                
                <View className="bg-white/10 px-6 py-4 rounded-xl border border-white/20 w-full items-center mb-6 backdrop-blur-md">
                    <Text className="text-white text-2xl font-mono font-bold tracking-widest text-center">
                        {card.code}
                    </Text>
                </View>

                <TouchableOpacity 
                    onPress={copyToClipboard}
                    className="flex-row items-center bg-white px-6 py-3 rounded-full space-x-2"
                >
                    <ClipboardDocumentIcon size={20} color="#000" />
                    <Text className="font-bold text-black">Copier le code</Text>
                </TouchableOpacity>

                <Text className="text-gray-500 text-xs mt-8 text-center">
                    Ne partagez ce code avec personne.
                </Text>
            </Animated.View>

          </TouchableOpacity>
          
          <Text className="text-gray-400 text-xs mt-6 text-center">
            Appuyez sur la carte pour {isFlipped ? 'voir les détails' : 'révéler le code'}
          </Text>
        </View>

        {/* Details Section */}
        <View className="px-6 py-6">
            <View className="flex-row items-center mb-6 bg-green-50 p-3 rounded-xl border border-green-100">
                <View className="bg-green-100 p-1.5 rounded-full mr-3">
                    <CheckIcon size={16} color="#16A34A" />
                </View>
                <Text className="text-green-800 text-sm font-medium">
                    Acheté le {new Date(card.created_at).toLocaleDateString('fr-FR')}
                </Text>
            </View>

            <View className="flex-row mb-4 border-b border-gray-100">
                <View className="border-b-2 border-black pb-2 px-4">
                    <Text className="font-bold text-gray-900">Description</Text>
                </View>
                <View className="pb-2 px-4">
                    <Text className="text-gray-400">Instructions</Text>
                </View>
            </View>
            
            <Text className="text-gray-600 leading-6">
                {card.description || `Cette carte cadeau ${brandName} d'une valeur de ${formattedBalance} est valide jusqu'à épuisement du solde.`}
            </Text>
            
            <View className="mt-6 bg-blue-50 p-4 rounded-xl border border-blue-100">
                <View className="flex-row items-start space-x-3">
                    <InformationCircleIcon size={20} color="#3B82F6" />
                    <View className="flex-1">
                        <Text className="text-blue-800 font-bold mb-1">Bon à savoir</Text>
                        <Text className="text-blue-600 text-xs leading-4">
                            Le code est unique et personnel. Une fois utilisé, le solde sera ajouté à votre compte sur la plateforme correspondante.
                        </Text>
                    </View>
                </View>
            </View>
        </View>

      </ScrollView>

      {/* Bottom Action Bar */}
      <View className="absolute bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 pb-8 shadow-lg">
        <TouchableOpacity 
            onPress={isFlipped ? copyToClipboard : handleCardPress}
            className="w-full bg-[#1F2937] py-4 rounded-2xl flex-row items-center justify-center space-x-2 shadow-lg active:bg-gray-800"
        >
            {isFlipped ? (
                <>
                    <ClipboardDocumentIcon size={24} color="white" />
                    <Text className="text-white font-bold text-lg">Copier le code</Text>
                </>
            ) : (
                <>
                    <EyeIcon size={24} color="white" />
                    <Text className="text-white font-bold text-lg">Révéler le code</Text>
                </>
            )}
        </TouchableOpacity>
      </View>

    </SafeAreaView>
  );
}

