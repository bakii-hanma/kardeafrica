import React, { useEffect, useRef } from 'react';
import { Text, View, Animated, Easing } from 'react-native';

interface VibratingTextProps {
  visible: boolean;
  message: string;
}

const VibratingText: React.FC<VibratingTextProps> = ({ visible, message }) => {
  const translateX = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (visible) {
      // Shake animation
      Animated.sequence([
        Animated.timing(translateX, { toValue: -10, duration: 50, useNativeDriver: true }),
        Animated.loop(
          Animated.sequence([
            Animated.timing(translateX, { toValue: 10, duration: 100, useNativeDriver: true }),
            Animated.timing(translateX, { toValue: -10, duration: 100, useNativeDriver: true }),
          ]),
          { iterations: 5 }
        ),
        Animated.timing(translateX, { toValue: 0, duration: 50, useNativeDriver: true })
      ]).start();
    }
  }, [visible, message]);

  if (!visible) return null;

  return (
    <Animated.View style={{ transform: [{ translateX }] }}>
      <Text className="text-red-500 text-xs mt-1 ml-1 font-medium">
        {message}
      </Text>
    </Animated.View>
  );
};

export default VibratingText;
