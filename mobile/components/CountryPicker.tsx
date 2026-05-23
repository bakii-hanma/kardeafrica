import React, { useState, useMemo } from 'react';
import { View, Text, TouchableOpacity, Modal, FlatList, TextInput } from 'react-native';
import { XMarkIcon, MagnifyingGlassIcon } from 'react-native-heroicons/outline';

export interface Country {
  name: string;
  code: string;
  flag: string;
  iso: string;
}

export const COUNTRIES: Country[] = [
  { name: 'Gabon', code: '+241', flag: '🇬🇦', iso: 'GA' },
  { name: 'Sénégal', code: '+221', flag: '🇸🇳', iso: 'SN' },
  { name: 'Côte d\'Ivoire', code: '+225', flag: '🇨🇮', iso: 'CI' },
  { name: 'Mali', code: '+223', flag: '🇲🇱', iso: 'ML' },
  { name: 'Cameroun', code: '+237', flag: '🇨🇲', iso: 'CM' },
  { name: 'Bénin', code: '+229', flag: '🇧🇯', iso: 'BJ' },
  { name: 'Burkina Faso', code: '+226', flag: '🇧🇫', iso: 'BF' },
  { name: 'Congo', code: '+242', flag: '🇨🇬', iso: 'CG' },
  { name: 'RDC', code: '+243', flag: '🇨🇩', iso: 'CD' },
  { name: 'Togo', code: '+228', flag: '🇹🇬', iso: 'TG' },
  { name: 'Niger', code: '+227', flag: '🇳🇪', iso: 'NE' },
  { name: 'Tchad', code: '+235', flag: '🇹🇩', iso: 'TD' },
  { name: 'Guinée', code: '+224', flag: '🇬🇳', iso: 'GN' },
  { name: 'France', code: '+33', flag: '🇫🇷', iso: 'FR' },
  { name: 'États-Unis', code: '+1', flag: '🇺🇸', iso: 'US' },
];

interface CountryPickerProps {
  visible: boolean;
  onClose: () => void;
  onSelect: (country: Country) => void;
  selectedCode?: string;
}

const CountryPicker: React.FC<CountryPickerProps> = ({ 
  visible, 
  onClose, 
  onSelect,
  selectedCode 
}) => {
  const [search, setSearch] = useState('');

  const filteredCountries = useMemo(() => {
    return COUNTRIES.filter(country => 
      country.name.toLowerCase().includes(search.toLowerCase()) || 
      country.code.includes(search)
    );
  }, [search]);

  return (
    <Modal
      visible={visible}
      animationType="slide"
      transparent={true}
      onRequestClose={onClose}
    >
      <View className="flex-1 justify-end bg-black/50">
        <View className="bg-white rounded-t-3xl h-[70%]">
          {/* Header */}
          <View className="flex-row justify-between items-center p-4 border-b border-gray-100">
            <Text className="text-lg font-bold text-gray-800">Sélectionner un pays</Text>
            <TouchableOpacity onPress={onClose} className="p-2 bg-gray-100 rounded-full">
              <XMarkIcon size={20} color="#374151" />
            </TouchableOpacity>
          </View>

          {/* Search */}
          <View className="px-4 py-2">
            <View className="flex-row items-center bg-gray-100 rounded-xl px-3 py-2">
              <MagnifyingGlassIcon size={20} color="#9CA3AF" />
              <TextInput
                className="flex-1 ml-2 text-base text-gray-800"
                placeholder="Rechercher un pays..."
                placeholderTextColor="#9CA3AF"
                value={search}
                onChangeText={setSearch}
              />
            </View>
          </View>

          {/* List */}
          <FlatList
            data={filteredCountries}
            keyExtractor={(item) => item.code + item.name}
            className="px-4"
            showsVerticalScrollIndicator={false}
            renderItem={({ item }) => {
              const isSelected = selectedCode === item.code || selectedCode === item.iso || selectedCode === item.name;
              return (
                <TouchableOpacity
                  className={`flex-row items-center py-4 border-b border-gray-100 ${isSelected ? 'bg-gray-50' : ''}`}
                  onPress={() => {
                    onSelect(item);
                    onClose();
                  }}
                >
                  <Text className="text-2xl mr-3">{item.flag}</Text>
                  <Text className="flex-1 text-base font-medium text-gray-800">{item.name}</Text>
                  <Text className="text-gray-500 font-medium">{item.code}</Text>
                </TouchableOpacity>
              );
            }}
            ListEmptyComponent={() => (
              <View className="py-8 items-center">
                <Text className="text-gray-500">Aucun pays trouvé</Text>
              </View>
            )}
          />
        </View>
      </View>
    </Modal>
  );
};

export default CountryPicker;
