import React, { useEffect, useRef } from 'react';
import { View, Image, Text, StyleSheet, Animated, Easing } from 'react-native';

const LOGO = require('../app/assets/FAVCON-KARDAFRICA-.png');

const Card = ({ color, rotate, delay, isLogo = false }: { color: string, rotate: string, delay: number, isLogo?: boolean }) => {
  const rotation = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const animation = Animated.loop(
      Animated.sequence([
        Animated.timing(rotation, {
          toValue: 1,
          duration: 1000,
          easing: Easing.inOut(Easing.sin),
          useNativeDriver: true,
        }),
        Animated.timing(rotation, {
          toValue: -1,
          duration: 1000,
          easing: Easing.inOut(Easing.sin),
          useNativeDriver: true,
        }),
        Animated.timing(rotation, {
          toValue: 0,
          duration: 1000,
          easing: Easing.inOut(Easing.sin),
          useNativeDriver: true,
        })
      ])
    );

    // Add delay before starting
    setTimeout(() => {
      animation.start();
    }, delay);

    return () => animation.stop();
  }, []);

  const rotateInterpolation = rotation.interpolate({
    inputRange: [-1, 1],
    outputRange: ['-15deg', '15deg']
  });

  // Calculate base rotation + animated rotation
  // Since we can't easily add string degrees in RN Animated without complex interpolation,
  // we'll wrap the animated rotation in a view that has the base rotation.
  
  return (
    <View style={[styles.cardContainer, { transform: [{ rotate: `${rotate}deg` }] }, { zIndex: isLogo ? 10 : 1 }]}>
        <Animated.View style={[{ transform: [{ rotate: rotateInterpolation }, { translateY: 20 }] }]}>
            <View style={[styles.card, { backgroundColor: color }]}>
                {isLogo ? (
                <Image source={LOGO} style={[styles.logo, { width: 30, height: 30 }]} resizeMode="contain" />
                ) : (
                <View style={styles.hole} />
                )}
            </View>
            <View style={styles.connector} />
        </Animated.View>
    </View>
  );
};

export default function LoadingKeychain({ showText = false, textColor = '#6B7280' }: { showText?: boolean, textColor?: string }) {
  const handRotation = useRef(new Animated.Value(0)).current;
  const handTranslateY = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(handRotation, {
          toValue: 1,
          duration: 500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(handRotation, {
          toValue: -1,
          duration: 500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(handRotation, {
          toValue: 0,
          duration: 500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        })
      ])
    ).start();

    Animated.loop(
      Animated.sequence([
        Animated.timing(handTranslateY, {
          toValue: 1,
          duration: 1000,
          easing: Easing.inOut(Easing.quad),
          useNativeDriver: true,
        }),
        Animated.timing(handTranslateY, {
          toValue: 0,
          duration: 1000,
          easing: Easing.inOut(Easing.quad),
          useNativeDriver: true,
        })
      ])
    ).start();
  }, []);

  const rotateInterpolation = handRotation.interpolate({
    inputRange: [-1, 1],
    outputRange: ['-5deg', '5deg']
  });

  const translateInterpolation = handTranslateY.interpolate({
    inputRange: [0, 1],
    outputRange: [-5, 5]
  });

  return (
    <View style={styles.container}>
      <Animated.View style={[styles.handContainer, { transform: [{ rotate: rotateInterpolation }, { translateY: translateInterpolation }] }]}>
        <View style={styles.ring} />
        <View style={styles.cardsWrapper}>
          <Card color="#E50914" rotate="-25" delay={0} />
          <Card color="#1DB954" rotate="-10" delay={100} />
          <Card color="#00439C" rotate="25" delay={200} />
          <Card color="#FFFFFF" rotate="5" delay={150} isLogo />
        </View>
      </Animated.View>
      {showText && <Text style={[styles.loadingText, { color: textColor }]}>Chargement...</Text>}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'flex-start',
    padding: 20,
    paddingTop: 40,
    minHeight: 200, // Reserve space for hanging cards
  },
  handContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 0,
  },
  hand: {
    fontSize: 40,
    zIndex: 20,
    marginBottom: -15,
  },
  ring: {
    width: 30,
    height: 30,
    borderRadius: 15,
    borderWidth: 4,
    borderColor: '#9CA3AF',
    zIndex: 15,
    backgroundColor: 'transparent',
  },
  cardsWrapper: {
    position: 'absolute',
    top: 35,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cardContainer: {
    position: 'absolute',
    top: 0,
    alignItems: 'center',
    width: 60,
    height: 100,
    // transformOrigin is not supported in RN styles directly for Views in the same way, 
    // but we handle rotation via nested views or anchor points. 
    // For simplicity here we just rotate the container.
  },
  connector: {
    position: 'absolute',
    top: -10,
    width: 4,
    height: 15,
    backgroundColor: '#9CA3AF',
    borderRadius: 2,
  },
  card: {
    width: 50,
    height: 80,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 5,
  },
  hole: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#F3F4F6',
    position: 'absolute',
    top: 6,
  },
  logo: {
    width: 30,
    height: 30,
  },
  loadingText: {
    marginTop: 100,
    color: '#6B7280',
    fontSize: 14,
    fontWeight: '500',
    display: 'none', // Hide loading text globally
  }
});
