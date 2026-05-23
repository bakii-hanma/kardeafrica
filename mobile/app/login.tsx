import React, { useState, useEffect, useRef } from 'react';
import { View, Text, TextInput, TouchableOpacity, Image, Dimensions, StatusBar, Animated, Easing } from 'react-native';
import { useRouter } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { AuthService } from '../services/auth';
import ErrorModal from '../components/ErrorModal';
import SuccessModal from '../components/SuccessModal';
import VibratingText from '../components/VibratingText';
import CountryPicker, { Country } from '../components/CountryPicker';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeftIcon, EnvelopeIcon, LockClosedIcon, UserIcon, EyeIcon, EyeSlashIcon, PhoneIcon, ChevronRightIcon, ChevronLeftIcon, CheckCircleIcon, XCircleIcon, SparklesIcon } from 'react-native-heroicons/outline';

const { width, height } = Dimensions.get('window');

// Floating Card Component
const FloatingCard = ({ delay, rotate, style, children }: any) => {
  const translateY = useRef(new Animated.Value(0)).current;
  
  useEffect(() => {
    setTimeout(() => {
      Animated.loop(
        Animated.sequence([
          Animated.timing(translateY, { toValue: -15, duration: 2000, useNativeDriver: true }),
          Animated.timing(translateY, { toValue: 0, duration: 2000, useNativeDriver: true })
        ])
      ).start();
    }, delay);
  }, []);

  return (
    <Animated.View style={[style, { transform: [{ translateY }, { rotate: rotate || '0deg' }] }]}>
      {children}
    </Animated.View>
  );
};

const LoginScreen = () => {
  const router = useRouter();
  const [isLogin, setIsLogin] = useState(true);
  const rotateAnim = useRef(new Animated.Value(0)).current;

  // Login Form State
  const [loginEmail, setLoginEmail] = useState('');
  const [loginPassword, setLoginPassword] = useState('');
  const [showLoginPassword, setShowLoginPassword] = useState(false);

  // Register Form State
  const [registerStep, setRegisterStep] = useState(1);
  const [registerFirstName, setRegisterFirstName] = useState('');
  const [registerLastName, setRegisterLastName] = useState('');
  const [registerEmail, setRegisterEmail] = useState('');
  const [registerPhone, setRegisterPhone] = useState('');
  const [registerPassword, setRegisterPassword] = useState('');
  const [registerConfirmPassword, setRegisterConfirmPassword] = useState('');
  const [showRegisterPassword, setShowRegisterPassword] = useState(false);
  const [showRegisterConfirmPassword, setShowRegisterConfirmPassword] = useState(false);
  
  const [countryPickerVisible, setCountryPickerVisible] = useState(false);
  const [selectedCountryCode, setSelectedCountryCode] = useState('+241'); // Gabon as default
  const [isLoading, setIsLoading] = useState(false);
  
  // Validation & Error State
  const [errors, setErrors] = useState<{[key: string]: string}>({});
  const [errorModalVisible, setErrorModalVisible] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');

  // Password Logic for Register
  const generatePassword = () => {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
    let retVal = "";
    for (let i = 0, n = charset.length; i < length; ++i) {
        retVal += charset.charAt(Math.floor(Math.random() * n));
    }
    setRegisterPassword(retVal);
    setRegisterConfirmPassword(retVal);
    setShowRegisterPassword(true);
    setShowRegisterConfirmPassword(true);
  };

  const getPasswordStrength = (pass: string) => {
    let score = 0;
    if (!pass) return 0;
    if (pass.length >= 8) score += 1;
    if (/[A-Z]/.test(pass)) score += 1;
    if (/[a-z]/.test(pass)) score += 1;
    if (/[0-9]/.test(pass)) score += 1;
    if (/[^A-Za-z0-9]/.test(pass)) score += 1;
    return score;
  };

  const frontRotate = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '180deg']
  });

  const backRotate = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['180deg', '360deg']
  });

  const frontOpacity = rotateAnim.interpolate({
    inputRange: [0, 0.5, 0.501, 1],
    outputRange: [1, 1, 0, 0]
  });

  const backOpacity = rotateAnim.interpolate({
    inputRange: [0, 0.5, 0.501, 1],
    outputRange: [0, 0, 1, 1]
  });

  const frontZIndex = rotateAnim.interpolate({
    inputRange: [0, 0.5, 0.501, 1],
    outputRange: [1, 1, 0, 0]
  });

  const backZIndex = rotateAnim.interpolate({
    inputRange: [0, 0.5, 0.501, 1],
    outputRange: [0, 0, 1, 1]
  });

  const toggleAuthMode = () => {
    // Clear errors when switching forms
    setErrors({});
    
    if (isLogin) {
      Animated.timing(rotateAnim, {
        toValue: 1,
        duration: 800,
        useNativeDriver: true,
      }).start();
      // Reset register step when flipping to register
      setTimeout(() => setRegisterStep(1), 400);
    } else {
      Animated.timing(rotateAnim, {
        toValue: 0,
        duration: 800,
        useNativeDriver: true,
      }).start();
    }
    setIsLogin(!isLogin);
  };

  const [successTitle, setSuccessTitle] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [successModalVisible, setSuccessModalVisible] = useState(false);
  const [successButtonText, setSuccessButtonText] = useState('Continuer');
  const [successAutoClose, setSuccessAutoClose] = useState(0);
  const [onSuccessClose, setOnSuccessClose] = useState<() => void>(() => () => {});

  const handleLogin = async () => {
    // Clear previous errors
    setErrors({});
    let newErrors: {[key: string]: string} = {};
    let hasError = false;

    if (!loginEmail) {
      newErrors.email = 'L\'adresse email est requise';
      hasError = true;
    }
    if (!loginPassword) {
      newErrors.password = 'Le mot de passe est requis';
      hasError = true;
    }

    if (hasError) {
      setErrors(newErrors);
      return;
    }

    setIsLoading(true);
    try {
      const response = await AuthService.login(loginEmail, loginPassword);

      if (response.status === 'success' && response.data) {
        await AsyncStorage.setItem('token', response.data.token);
        await AsyncStorage.setItem('user', JSON.stringify(response.data.user));

        setSuccessTitle('Connexion réussie');
        setSuccessMessage(`Bienvenue, ${response.data.user.name || 'Utilisateur'} !`);
        setSuccessButtonText('Continuer');
        setSuccessAutoClose(2500);
        setOnSuccessClose(() => () => {
          setSuccessModalVisible(false);
          router.replace('/(tabs)');
        });
        setSuccessModalVisible(true);
      } else {
        setErrorMessage(response.message || 'Identifiants incorrects');
        setErrorModalVisible(true);
      }
    } catch (error) {
      setErrorMessage('Une erreur est survenue lors de la connexion. Vérifiez votre connexion internet.');
      setErrorModalVisible(true);
    } finally {
      setIsLoading(false);
    }
  };

  const handleRegisterNextStep = () => {
    // Clear previous errors
    setErrors({});
    let newErrors: {[key: string]: string} = {};
    let hasError = false;

    if (registerStep === 1) {
      if (!registerFirstName) {
        newErrors.firstName = 'Le prénom est requis';
        hasError = true;
      }
      if (!registerLastName) {
        newErrors.lastName = 'Le nom est requis';
        hasError = true;
      }
      if (!registerEmail) {
        newErrors.email = 'L\'adresse email est requise';
        hasError = true;
      }
      // Simple email validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (registerEmail && !emailRegex.test(registerEmail)) {
        newErrors.email = 'L\'adresse email est invalide';
        hasError = true;
      }

      if (hasError) {
        setErrors(newErrors);
        return;
      }
      setRegisterStep(2);
    } else if (registerStep === 2) {
      if (!registerPhone) {
        newErrors.phone = 'Le numéro de téléphone est requis';
        hasError = true;
      }

      if (hasError) {
        setErrors(newErrors);
        return;
      }
      setRegisterStep(3);
    }
  };

  const handleCountrySelect = (country: Country) => {
    setSelectedCountryCode(country.code);
    setCountryPickerVisible(false);
  };

  const handleRegister = async () => {
    // Clear previous errors
    setErrors({});
    let newErrors: {[key: string]: string} = {};
    let hasError = false;

    if (!registerPassword) {
      newErrors.password = 'Le mot de passe est requis';
      hasError = true;
    } else {
      if (registerPassword.length < 8) {
        newErrors.password = 'Le mot de passe doit contenir au moins 8 caractères';
        hasError = true;
      }
      if (registerPassword !== registerConfirmPassword) {
        newErrors.confirmPassword = 'Les mots de passe ne correspondent pas';
        hasError = true;
      }
    }

    if (hasError) {
      setErrors(newErrors);
      return;
    }

    setIsLoading(true);
    try {
      // Format phone number with country code
      const formattedPhone = `${selectedCountryCode}${registerPhone.trim()}`;
      const response = await AuthService.register(registerFirstName, registerLastName, registerEmail, registerPassword, formattedPhone);
      
      if (response.status === 'success' && response.data) {
        setSuccessTitle('Inscription réussie');
        setSuccessMessage('Votre compte a été créé avec succès. Veuillez vous connecter.');
        setSuccessButtonText('Se connecter');
        setSuccessAutoClose(0);
        setOnSuccessClose(() => () => {
          setSuccessModalVisible(false);
          setLoginEmail(registerEmail);
          toggleAuthMode();
        });
        setSuccessModalVisible(true);
      } else {
        const errorMsg = response.errors 
          ? Object.values(response.errors).flat().join('\n')
          : response.message || 'Erreur lors de l\'inscription';
        setErrorMessage(errorMsg);
        setErrorModalVisible(true);
      }
    } catch (error) {
      setErrorMessage('Une erreur est survenue lors de l\'inscription. Vérifiez votre connexion internet.');
      setErrorModalVisible(true);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      <StatusBar barStyle="dark-content" />
      
      {/* Decorative Background Elements */}
      <View className="absolute top-0 left-0 w-full h-full overflow-hidden">
        <View className="absolute -top-20 -right-20 w-64 h-64 bg-[#44A08D]/10 rounded-full blur-3xl" />
        <View className="absolute top-40 -left-20 w-72 h-72 bg-blue-500/5 rounded-full blur-3xl" />
        <View className="absolute bottom-0 right-0 w-80 h-80 bg-purple-500/5 rounded-full blur-3xl" />
      </View>
      
      {/* Header */}
      <View className="px-6 py-4 flex-row items-center justify-between z-10">
        <TouchableOpacity 
          onPress={() => router.back()} 
          className="w-10 h-10 bg-white/80 backdrop-blur-md rounded-full items-center justify-center shadow-sm border border-white/50"
        >
          <ArrowLeftIcon size={20} color="#1F2937" />
        </TouchableOpacity>
        <Image 
          source={require('./assets/FAVCON-KARDAFRICA-.png')} 
          className="w-10 h-10 rounded-full"
          style={{ width: 40, height: 40, borderRadius: 20 }}
          resizeMode="contain"
        />
      </View>

      <View className="flex-1 items-center justify-center px-6 -mt-8">
        
        {/* Floating Animated Cards */}
      <View className="flex-row items-end justify-center space-x-6 mb-4 z-0" style={{ height: 100 }}>
        <FloatingCard delay={0} rotate="-12deg" style={{ zIndex: 1 }}>
             <View className="w-12 h-16 bg-rose-500/90 rounded-xl shadow-lg border border-white/30 items-center justify-center backdrop-blur-md">
                <Text className="text-xl">🎁</Text>
             </View>
          </FloatingCard>
           <FloatingCard delay={500} rotate="0deg" style={{ zIndex: 2, marginBottom: 15 }}>
             <View className="w-16 h-24 bg-[#44A08D] rounded-xl shadow-lg border border-white/30 items-center justify-center">
                <Image 
                  source={require('./assets/FAVCON-KARDAFRICA-.png')} 
                  className="w-10 h-10"
                  style={{ width: 40, height: 40 }}
                  resizeMode="contain"
                />
             </View>
          </FloatingCard>
           <FloatingCard delay={1000} rotate="12deg" style={{ zIndex: 1 }}>
             <View className="w-12 h-16 bg-blue-500/90 rounded-xl shadow-lg border border-white/30 items-center justify-center backdrop-blur-md">
                 <Text className="text-xl">🎮</Text>
             </View>
          </FloatingCard>
        </View>

        <View className="w-full relative" style={{ height: 600 }}>
          
          {/* LOGIN CARD (FRONT) */}
          <Animated.View 
            style={[
              {
                position: 'absolute',
                width: '100%',
                height: '100%',
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                borderRadius: 32,
                padding: 24,
                justifyContent: 'space-between',
                borderWidth: 1,
                borderColor: 'rgba(255, 255, 255, 0.6)',
                shadowColor: '#111827',
                shadowOffset: { width: 0, height: 10 },
                shadowOpacity: 0.1,
                shadowRadius: 10,
                elevation: 10,
                backfaceVisibility: 'hidden',
                transform: [
                  { perspective: 1000 },
                  { rotateY: frontRotate }
                ],
                opacity: frontOpacity,
                zIndex: frontZIndex as any
              }
            ]}
          >
            <View>
              <View className="items-center mb-4">
                <Text className="text-2xl font-bold text-[#1F2937] text-center">Bon retour !</Text>
                <Text className="text-gray-400 text-sm text-center mt-1">Connectez-vous à votre espace</Text>
              </View>

              <View className="space-y-3">
                <View>
                    <Text className="text-[10px] font-bold text-gray-500 ml-1 mb-1.5 uppercase tracking-wider">Email</Text>
                    <View className={`flex-row items-center bg-gray-50 border ${errors.email ? 'border-red-500' : 'border-gray-100'} rounded-xl px-3 py-3`}>
                      <EnvelopeIcon size={18} color={errors.email ? "#EF4444" : "#9CA3AF"} />
                      <TextInput 
                        className="flex-1 ml-2 text-gray-800 font-medium text-sm"
                        placeholder="votre@email.com"
                        placeholderTextColor="#9CA3AF"
                        keyboardType="email-address"
                        autoCapitalize="none"
                        value={loginEmail}
                        onChangeText={(text) => {
                          setLoginEmail(text);
                          if (errors.email) setErrors({...errors, email: ''});
                        }}
                      />
                    </View>
                    <VibratingText visible={!!errors.email} message={errors.email} />
                  </View>

                <View>
                    <Text className="text-[10px] font-bold text-gray-500 ml-1 mb-1.5 uppercase tracking-wider">Mot de passe</Text>
                    <View className={`flex-row items-center bg-gray-50 border ${errors.password ? 'border-red-500' : 'border-gray-100'} rounded-xl px-3 py-3`}>
                      <LockClosedIcon size={18} color={errors.password ? "#EF4444" : "#9CA3AF"} />
                      <TextInput 
                        className="flex-1 ml-2 text-gray-800 font-medium text-sm"
                        placeholder="••••••••"
                        placeholderTextColor="#9CA3AF"
                        secureTextEntry={!showLoginPassword}
                        value={loginPassword}
                        onChangeText={(text) => {
                          setLoginPassword(text);
                          if (errors.password) setErrors({...errors, password: ''});
                        }}
                      />
                      <TouchableOpacity onPress={() => setShowLoginPassword(!showLoginPassword)}>
                        {showLoginPassword ? (
                          <EyeSlashIcon size={18} color="#9CA3AF" />
                        ) : (
                          <EyeIcon size={18} color="#9CA3AF" />
                        )}
                      </TouchableOpacity>
                    </View>
                    <VibratingText visible={!!errors.password} message={errors.password} />
                  </View>

                {/* Mot de passe oublié retiré */}
              </View>
            </View>

            <View className="mt-6">
              <TouchableOpacity 
                className={`bg-[#1F2937] py-4 rounded-xl shadow-lg shadow-gray-900/20 items-center justify-center mb-3 active:scale-95 transition-transform ${isLoading ? 'opacity-70' : ''}`}
                onPress={handleLogin}
                disabled={isLoading}
              >
                <Text className="text-white font-bold text-base tracking-wide">
                  {isLoading ? 'Connexion...' : 'Se connecter'}
                </Text>
              </TouchableOpacity>

              <View className="flex-row justify-center items-center">
                <Text className="text-gray-400 text-sm font-medium">Pas encore de compte ? </Text>
                <TouchableOpacity onPress={toggleAuthMode} className="py-2 px-1">
                  <Text className="text-[#44A08D] font-bold text-sm">S'inscrire</Text>
                </TouchableOpacity>
              </View>
            </View>
          </Animated.View>

          {/* REGISTER CARD (BACK) - Multi-step */}
          <Animated.View 
            style={[
              {
                position: 'absolute',
                width: '100%',
                height: '100%',
                backgroundColor: '#1F2937',
                borderRadius: 32,
                padding: 24,
                justifyContent: 'space-between',
                borderWidth: 1,
                borderColor: '#374151',
                shadowColor: '#111827',
                shadowOffset: { width: 0, height: 10 },
                shadowOpacity: 0.3,
                shadowRadius: 10,
                elevation: 10,
                backfaceVisibility: 'hidden',
                transform: [
                  { perspective: 1000 },
                  { rotateY: backRotate }
                ],
                opacity: backOpacity,
                zIndex: backZIndex as any
              }
            ]}
          >
            <View>
              <View className="items-center mb-4 relative">
                 {registerStep > 1 && (
                    <TouchableOpacity 
                      onPress={() => setRegisterStep(registerStep - 1)}
                      className="absolute left-0 top-1 p-1 z-10"
                    >
                      <ArrowLeftIcon size={20} color="white" />
                    </TouchableOpacity>
                 )}
                <Text className="text-2xl font-bold text-white text-center mb-2">Inscription</Text>
                
                {/* Animated Step Indicator */}
                <View className="flex-row items-center justify-center space-x-2">
                  {[1, 2, 3].map((step) => (
                    <View key={step} className="flex-row items-center">
                      <View 
                        style={{ 
                          height: 8,
                          borderRadius: 9999,
                          backgroundColor: step <= registerStep ? '#44A08D' : '#374151',
                          width: step === registerStep ? 24 : 8 
                        }}
                      />
                    </View>
                  ))}
                </View>
                <Text className="text-gray-400 text-xs text-center mt-2">Étape {registerStep} sur 3</Text>
              </View>

              {registerStep === 1 && (
                <View className="space-y-3">
                  <View className="flex-row space-x-3">
                    <View className="flex-1">
                      <Text className="text-[10px] font-bold text-gray-400 ml-1 mb-1.5 uppercase tracking-wider">Prénom</Text>
                      <View className={`flex-row items-center bg-gray-800/50 border ${errors.firstName ? 'border-red-500' : 'border-gray-700'} rounded-xl px-3 py-3`}>
                        <UserIcon size={18} color={errors.firstName ? "#EF4444" : "#9CA3AF"} />
                        <TextInput 
                          className="flex-1 ml-2 text-white font-medium text-sm"
                          placeholder="Prénom"
                          placeholderTextColor="#6B7280"
                          value={registerFirstName}
                          onChangeText={(text) => {
                            setRegisterFirstName(text);
                            if (errors.firstName) setErrors({...errors, firstName: ''});
                          }}
                        />
                      </View>
                      <VibratingText visible={!!errors.firstName} message={errors.firstName} />
                    </View>

                    <View className="flex-1">
                      <Text className="text-[10px] font-bold text-gray-400 ml-1 mb-1.5 uppercase tracking-wider">Nom</Text>
                      <View className={`flex-row items-center bg-gray-800/50 border ${errors.lastName ? 'border-red-500' : 'border-gray-700'} rounded-xl px-3 py-3`}>
                        <TextInput 
                          className="flex-1 ml-2 text-white font-medium text-sm"
                          placeholder="Nom"
                          placeholderTextColor="#6B7280"
                          value={registerLastName}
                          onChangeText={(text) => {
                            setRegisterLastName(text);
                            if (errors.lastName) setErrors({...errors, lastName: ''});
                          }}
                        />
                      </View>
                      <VibratingText visible={!!errors.lastName} message={errors.lastName} />
                    </View>
                  </View>

                  <View>
                    <Text className="text-[10px] font-bold text-gray-400 ml-1 mb-1.5 uppercase tracking-wider">Email</Text>
                    <View className={`flex-row items-center bg-gray-800/50 border ${errors.email ? 'border-red-500' : 'border-gray-700'} rounded-xl px-3 py-3`}>
                      <EnvelopeIcon size={18} color={errors.email ? "#EF4444" : "#9CA3AF"} />
                      <TextInput 
                        className="flex-1 ml-2 text-white font-medium text-sm"
                        placeholder="votre@email.com"
                        placeholderTextColor="#6B7280"
                        keyboardType="email-address"
                        autoCapitalize="none"
                        value={registerEmail}
                        onChangeText={(text) => {
                          setRegisterEmail(text);
                          if (errors.email) setErrors({...errors, email: ''});
                        }}
                      />
                    </View>
                    <VibratingText visible={!!errors.email} message={errors.email} />
                  </View>

                  <TouchableOpacity 
                    className={`bg-[#44A08D] py-4 rounded-xl shadow-lg shadow-[#44A08D]/20 items-center justify-center mt-2 active:scale-95 transition-transform`}
                    onPress={handleRegisterNextStep}
                  >
                    <Text className="text-white font-bold text-base tracking-wide">
                      Suivant
                    </Text>
                  </TouchableOpacity>
                </View>
              )}

              {registerStep === 2 && (
                <View className="space-y-3">
                  <View>
                    <Text className="text-[10px] font-bold text-gray-400 ml-1 mb-1.5 uppercase tracking-wider">Téléphone</Text>
                    <View className={`flex-row items-center bg-gray-800/50 border ${errors.phone ? 'border-red-500' : 'border-gray-700'} rounded-xl px-3 py-3`}>
                      <TouchableOpacity 
                        className="flex-row items-center mr-2 border-r border-gray-600 pr-2"
                        onPress={() => setCountryPickerVisible(true)}
                      >
                        <Text className="text-white text-sm font-bold">{selectedCountryCode}</Text>
                        <ChevronRightIcon size={12} color="#9CA3AF" className="ml-1" />
                      </TouchableOpacity>
                      <PhoneIcon size={18} color={errors.phone ? "#EF4444" : "#9CA3AF"} />
                      <TextInput 
                        className="flex-1 ml-2 text-white font-medium text-sm"
                        placeholder="00 00 00 00"
                        placeholderTextColor="#6B7280"
                        keyboardType="phone-pad"
                        value={registerPhone}
                        onChangeText={(text) => {
                          setRegisterPhone(text);
                          if (errors.phone) setErrors({...errors, phone: ''});
                        }}
                      />
                    </View>
                    <VibratingText visible={!!errors.phone} message={errors.phone} />
                  </View>

                  <TouchableOpacity 
                    className={`bg-[#44A08D] py-4 rounded-xl shadow-lg shadow-[#44A08D]/20 items-center justify-center mt-2 active:scale-95 transition-transform`}
                    onPress={handleRegisterNextStep}
                  >
                    <Text className="text-white font-bold text-base tracking-wide">
                      Suivant
                    </Text>
                  </TouchableOpacity>
                </View>
              )}

              {registerStep === 3 && (
                <View className="space-y-3">
                  <View>
                    <View className="flex-row justify-between items-center mb-1.5">
                      <Text className="text-[10px] font-bold text-gray-400 ml-1 uppercase tracking-wider">Mot de passe</Text>
                      <TouchableOpacity 
                        onPress={generatePassword}
                        className="flex-row items-center bg-gray-700/50 px-2 py-1 rounded-full border border-gray-600"
                      >
                        <SparklesIcon size={10} color="#44A08D" />
                        <Text className="text-[10px] text-gray-300 ml-1">Générer</Text>
                      </TouchableOpacity>
                    </View>
                    <View className={`flex-row items-center bg-gray-800/50 border ${errors.password ? 'border-red-500' : 'border-gray-700'} rounded-xl px-3 py-3`}>
                      <LockClosedIcon size={18} color={errors.password ? "#EF4444" : "#9CA3AF"} />
                      <TextInput 
                        className="flex-1 ml-2 text-white font-medium text-sm"
                        placeholder="••••••••"
                        placeholderTextColor="#6B7280"
                        secureTextEntry={!showRegisterPassword}
                        value={registerPassword}
                        onChangeText={(text) => {
                          setRegisterPassword(text);
                          if (errors.password) setErrors({...errors, password: ''});
                        }}
                      />
                      <TouchableOpacity onPress={() => setShowRegisterPassword(!showRegisterPassword)}>
                        {showRegisterPassword ? (
                          <EyeSlashIcon size={18} color="#9CA3AF" />
                        ) : (
                          <EyeIcon size={18} color="#9CA3AF" />
                        )}
                      </TouchableOpacity>
                    </View>
                    <VibratingText visible={!!errors.password} message={errors.password} />
                    
                    {/* Password Strength Indicators */}
                    <View className="flex-row flex-wrap justify-between mt-2 px-1 mb-2">
                      <View className="flex-row items-center mb-1 w-1/2">
                        {registerPassword.length >= 8 ? 
                          <CheckCircleIcon size={12} color="#44A08D" /> : 
                          <View className="w-3 h-3 rounded-full border border-gray-600" />
                        }
                        <Text className={`text-[10px] ml-1.5 ${registerPassword.length >= 8 ? 'text-[#44A08D]' : 'text-gray-400'}`}>8 caractères min.</Text>
                      </View>
                      <View className="flex-row items-center mb-1 w-1/2">
                        {/[A-Z]/.test(registerPassword) ? 
                          <CheckCircleIcon size={12} color="#44A08D" /> : 
                          <View className="w-3 h-3 rounded-full border border-gray-600" />
                        }
                        <Text className={`text-[10px] ml-1.5 ${/[A-Z]/.test(registerPassword) ? 'text-[#44A08D]' : 'text-gray-400'}`}>Une majuscule</Text>
                      </View>
                      <View className="flex-row items-center mb-1 w-1/2">
                         {/[0-9]/.test(registerPassword) ? 
                          <CheckCircleIcon size={12} color="#44A08D" /> : 
                          <View className="w-3 h-3 rounded-full border border-gray-600" />
                        }
                        <Text className={`text-[10px] ml-1.5 ${/[0-9]/.test(registerPassword) ? 'text-[#44A08D]' : 'text-gray-400'}`}>Un chiffre</Text>
                      </View>
                      <View className="flex-row items-center mb-1 w-1/2">
                        {/[^A-Za-z0-9]/.test(registerPassword) ? 
                          <CheckCircleIcon size={12} color="#44A08D" /> : 
                          <View className="w-3 h-3 rounded-full border border-gray-600" />
                        }
                        <Text className={`text-[10px] ml-1.5 ${/[^A-Za-z0-9]/.test(registerPassword) ? 'text-[#44A08D]' : 'text-gray-400'}`}>Un caractère spécial</Text>
                      </View>
                    </View>
                  </View>
                  <View>
                    <View className={`flex-row items-center bg-gray-800/50 border ${errors.confirmPassword ? 'border-red-500' : 'border-gray-700'} rounded-xl px-3 py-3`}>
                      <LockClosedIcon size={18} color={errors.confirmPassword ? "#EF4444" : "#9CA3AF"} />
                      <TextInput 
                        className="flex-1 ml-2 text-white font-medium text-sm"
                        placeholder="Confirmer mot de passe"
                        placeholderTextColor="#6B7280"
                        secureTextEntry={!showRegisterConfirmPassword}
                        value={registerConfirmPassword}
                        onChangeText={(text) => {
                          setRegisterConfirmPassword(text);
                          if (errors.confirmPassword) setErrors({...errors, confirmPassword: ''});
                        }}
                      />
                      <TouchableOpacity onPress={() => setShowRegisterConfirmPassword(!showRegisterConfirmPassword)}>
                        {showRegisterConfirmPassword ? (
                          <EyeSlashIcon size={18} color="#9CA3AF" />
                        ) : (
                          <EyeIcon size={18} color="#9CA3AF" />
                        )}
                      </TouchableOpacity>
                    </View>
                    <VibratingText visible={!!errors.confirmPassword} message={errors.confirmPassword} />
                  </View>

                  <TouchableOpacity 
                    className={`bg-[#44A08D] py-4 rounded-xl shadow-lg shadow-[#44A08D]/20 items-center justify-center mt-2 active:scale-95 transition-transform ${isLoading ? 'opacity-70' : ''}`}
                    onPress={handleRegister}
                    disabled={isLoading}
                  >
                    <Text className="text-white font-bold text-base tracking-wide">
                      {isLoading ? 'Inscription...' : 'Créer mon compte'}
                    </Text>
                  </TouchableOpacity>
                </View>
              )}
            </View>

            <View className="mt-6">
              <View className="flex-row justify-center items-center">
                <Text className="text-gray-400 text-sm font-medium">Déjà un compte ? </Text>
                <TouchableOpacity onPress={toggleAuthMode} className="py-2 px-1">
                  <Text className="text-[#44A08D] font-bold text-sm">Se connecter</Text>
                </TouchableOpacity>
              </View>
            </View>
          </Animated.View>

        </View>
      </View>

      <SuccessModal
        visible={successModalVisible}
        title={successTitle}
        message={successMessage}
        buttonText={successButtonText}
        onClose={onSuccessClose}
        autoClose={successAutoClose}
      />

      <ErrorModal 
        visible={errorModalVisible}
        message={errorMessage}
        onClose={() => setErrorModalVisible(false)}
      />

      <CountryPicker
        visible={countryPickerVisible}
        onClose={() => setCountryPickerVisible(false)}
        onSelect={handleCountrySelect}
      />
    </SafeAreaView>
  );
};

export default LoginScreen;
