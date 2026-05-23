import React, { useRef, useEffect, useState } from 'react';
import { View, Text, ScrollView, NativeSyntheticEvent, NativeScrollEvent, StyleSheet, Platform, TouchableOpacity } from 'react-native';

interface WheelPickerProps {
  items: { label: string; value: any }[];
  selectedValue: any;
  onValueChange: (value: any) => void;
  itemHeight?: number;
  width?: number | string;
}

const WheelPicker = ({ items, selectedValue, onValueChange, itemHeight = 50, width = '33%' }: WheelPickerProps) => {
  const scrollViewRef = useRef<ScrollView>(null);
  const [scrollIndex, setScrollIndex] = useState(0);

  // Initialize position
  useEffect(() => {
    const index = items.findIndex(i => i.value === selectedValue);
    if (index >= 0) {
      setScrollIndex(index);
      // Timeout to ensure layout is ready
      setTimeout(() => {
        scrollViewRef.current?.scrollTo({ y: index * itemHeight, animated: false });
      }, 100);
    }
  }, []); // Run once on mount to set initial pos

  // Handle external updates if needed, but be careful of loops
  useEffect(() => {
    const index = items.findIndex(i => i.value === selectedValue);
    if (index >= 0 && index !== scrollIndex) {
      // Only scroll if significantly different to avoid jitter during drag
      // checking if we are not currently scrolling would be better, but simple check:
      // We rely on parent to update selectedValue.
    }
  }, [selectedValue]);

  const handleScrollEnd = (e: NativeSyntheticEvent<NativeScrollEvent>) => {
    const y = e.nativeEvent.contentOffset.y;
    const index = Math.round(y / itemHeight);
    const validIndex = Math.max(0, Math.min(index, items.length - 1));
    
    setScrollIndex(validIndex);
    const item = items[validIndex];
    if (item) {
      onValueChange(item.value);
    }
  };

  return (
    <View style={{ height: itemHeight * 5, width: width, overflow: 'hidden' }}>
      {/* Selection Overlay */}
      <View 
        style={[
          styles.selectionOverlay, 
          { top: itemHeight * 2, height: itemHeight }
        ]} 
        pointerEvents="none"
      />
      
      <ScrollView
        ref={scrollViewRef}
        snapToInterval={itemHeight}
        decelerationRate="fast"
        showsVerticalScrollIndicator={false}
        onMomentumScrollEnd={handleScrollEnd}
        onScrollEndDrag={handleScrollEnd}
        contentContainerStyle={{ paddingVertical: itemHeight * 2 }}
      >
        {items.map((item, index) => (
          <TouchableOpacity 
            key={index} 
            activeOpacity={1}
            onPress={() => {
              scrollViewRef.current?.scrollTo({ y: index * itemHeight, animated: true });
              onValueChange(item.value);
            }}
            style={{ 
              height: itemHeight, 
              justifyContent: 'center', 
              alignItems: 'center',
              opacity: selectedValue === item.value ? 1 : 0.4,
              transform: [{ scale: selectedValue === item.value ? 1.1 : 0.9 }]
            }}
          >
            <Text style={{ 
              fontSize: 18, 
              color: '#000',
              fontWeight: selectedValue === item.value ? '600' : '400'
            }}>
              {item.label}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  selectionOverlay: {
    position: 'absolute',
    left: 5,
    right: 5,
    borderTopWidth: 1,
    borderBottomWidth: 1,
    borderColor: '#E5E7EB', // gray-200
    backgroundColor: 'rgba(243, 244, 246, 0.5)', // gray-100 with opacity
    zIndex: -1,
    borderRadius: 8,
  }
});

export default WheelPicker;
