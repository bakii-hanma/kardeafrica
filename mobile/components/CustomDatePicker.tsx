import React, { useState } from 'react';
import { View, Text, TouchableOpacity, Platform, Modal } from 'react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { CalendarIcon, XMarkIcon } from 'react-native-heroicons/outline';
import DateWheelPicker from './DateWheelPicker';

interface CustomDatePickerProps {
  value: Date | string;
  onChange: (date: Date) => void;
  label?: string;
  placeholder?: string;
  minimumDate?: Date;
  maximumDate?: Date;
}

const CustomDatePicker = ({ 
  value, 
  onChange, 
  label, 
  placeholder = "Sélectionner une date",
  minimumDate,
  maximumDate = new Date() // Par défaut, pas de date future pour une date de naissance
}: CustomDatePickerProps) => {
  const [showPicker, setShowPicker] = useState(false);
  const [tempDate, setTempDate] = useState(new Date());
  
  // Convertir la valeur en objet Date si c'est une string
  const dateValue = value instanceof Date ? value : (value ? new Date(value) : new Date());
  
  // Formater la date pour l'affichage (DD/MM/YYYY)
  const formatDate = (date: Date | string) => {
    if (!date) return '';
    const d = date instanceof Date ? date : new Date(date);
    if (isNaN(d.getTime())) return '';
    
    const day = d.getDate().toString().padStart(2, '0');
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const year = d.getFullYear();
    
    return `${day}/${month}/${year}`;
  };

  const handleDateChange = (event: any, selectedDate?: Date) => {
    if (selectedDate) {
      setTempDate(selectedDate);
    }
  };

  const openPicker = () => {
    const d = value instanceof Date ? value : (value ? new Date(value) : new Date());
    setTempDate(isNaN(d.getTime()) ? new Date() : d);
    setShowPicker(true);
  };

  const confirmDate = () => {
    onChange(tempDate);
    setShowPicker(false);
  };

  return (
    <View className="mb-4">
      {label && <Text className="text-gray-500 mb-1 ml-1 text-sm font-medium">{label}</Text>}
      
      <TouchableOpacity 
        onPress={openPicker}
        className="bg-white p-4 rounded-xl border border-gray-200 flex-row items-center justify-between"
      >
        <Text className={`text-base ${value ? 'text-gray-900' : 'text-gray-400'}`}>
          {value ? formatDate(value) : placeholder}
        </Text>
        <CalendarIcon size={20} color="#6B7280" />
      </TouchableOpacity>

      {/* Custom Picker Modal (Unified for both Android and iOS) */}
      <Modal
        visible={showPicker}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowPicker(false)}
      >
        <TouchableOpacity 
          style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' }}
          activeOpacity={1}
          onPress={() => setShowPicker(false)}
        >
          <TouchableOpacity 
            activeOpacity={1} 
            style={{ backgroundColor: 'white', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 16, paddingBottom: 32 }}
          >
            <View className="flex-row justify-between items-center mb-4 border-b border-gray-100 pb-4">
              <Text className="text-lg font-bold text-gray-900">Sélectionner une date</Text>
              <TouchableOpacity onPress={() => setShowPicker(false)} className="bg-gray-100 p-2 rounded-full">
                <XMarkIcon size={20} color="#374151" />
              </TouchableOpacity>
            </View>
            
            <View className="items-center justify-center">
              {Platform.OS === 'ios' ? (
                <DateTimePicker
                  value={tempDate}
                  mode="date"
                  display="spinner"
                  onChange={handleDateChange}
                  maximumDate={maximumDate}
                  minimumDate={minimumDate}
                  textColor="#000000"
                  themeVariant="light"
                  style={{ height: 200, width: '100%' }}
                />
              ) : (
                <View style={{ height: 250, width: '100%' }}>
                  <DateWheelPicker
                    value={tempDate}
                    onChange={(date) => setTempDate(date)}
                    maximumDate={maximumDate}
                    minimumDate={minimumDate}
                  />
                </View>
              )}
            </View>
            
            <TouchableOpacity 
              onPress={confirmDate}
              className="bg-[#0F172A] py-3 rounded-xl items-center mt-4"
            >
              <Text className="text-white font-bold text-base">Confirmer</Text>
            </TouchableOpacity>
          </TouchableOpacity>
        </TouchableOpacity>
      </Modal>
    </View>
  );
};

export default CustomDatePicker;
