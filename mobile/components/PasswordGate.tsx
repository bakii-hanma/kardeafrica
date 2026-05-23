import { View, Text, Modal, TouchableOpacity, TextInput, ActivityIndicator } from 'react-native';
import { useState, useRef, useEffect, useCallback } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LockClosedIcon, EyeIcon, EyeSlashIcon, XMarkIcon } from 'react-native-heroicons/outline';
import { AuthService } from '../services/auth';

const STORAGE_KEY = 'cards-unlock-exp';
const UNLOCK_DURATION_MS = 5 * 60 * 1000; // 5 minutes

/**
 * Verifie si les cartes sont actuellement deverrouillees (5 min apres dernier check).
 */
export async function isCardsUnlocked(): Promise<boolean> {
  try {
    const exp = await AsyncStorage.getItem(STORAGE_KEY);
    if (!exp) return false;
    return parseInt(exp, 10) > Date.now();
  } catch {
    return false;
  }
}

/**
 * Marque les cartes comme deverrouillees pour 5 minutes.
 */
export async function setCardsUnlocked(): Promise<void> {
  await AsyncStorage.setItem(STORAGE_KEY, String(Date.now() + UNLOCK_DURATION_MS));
}

/**
 * Hook qui retourne une fn `requireUnlock(callback)` :
 * - Execute le callback immediatement si deja deverrouille
 * - Sinon ouvre la modal et execute le callback apres verif reussie
 */
export function usePasswordGate() {
  const [open, setOpen] = useState(false);
  const callbackRef = useRef<(() => void) | null>(null);

  const requireUnlock = useCallback(async (callback: () => void) => {
    if (await isCardsUnlocked()) {
      callback();
      return;
    }
    callbackRef.current = callback;
    setOpen(true);
  }, []);

  const onSuccess = useCallback(async () => {
    await setCardsUnlocked();
    setOpen(false);
    const cb = callbackRef.current;
    callbackRef.current = null;
    if (cb) cb();
  }, []);

  const onClose = useCallback(() => {
    setOpen(false);
    callbackRef.current = null;
  }, []);

  return {
    requireUnlock,
    PasswordGateModal: () => <PasswordGate visible={open} onClose={onClose} onSuccess={onSuccess} />,
  };
}

interface PasswordGateProps {
  visible: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export default function PasswordGate({ visible, onClose, onSuccess }: PasswordGateProps) {
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const inputRef = useRef<TextInput>(null);

  useEffect(() => {
    if (visible) {
      setPassword('');
      setShowPassword(false);
      setError(null);
      // Auto focus apres l'animation
      const t = setTimeout(() => inputRef.current?.focus(), 250);
      return () => clearTimeout(t);
    }
  }, [visible]);

  const handleVerify = async () => {
    if (!password) return;
    setLoading(true);
    setError(null);
    const res = await AuthService.verifyPassword(password);
    setLoading(false);
    if (res.ok) {
      onSuccess();
    } else {
      setError(res.message || 'Mot de passe incorrect.');
    }
  };

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose} statusBarTranslucent>
      <View className="flex-1 items-center justify-center px-4" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
        <View className="w-full max-w-sm bg-white rounded-3xl overflow-hidden" style={{ elevation: 24, shadowColor: '#000', shadowOpacity: 0.25, shadowRadius: 24, shadowOffset: { width: 0, height: 12 } }}>

          {/* Header */}
          <View className="px-5 py-4 border-b border-slate-100 flex-row items-center bg-slate-50">
            <View className="w-9 h-9 rounded-lg items-center justify-center mr-3" style={{ backgroundColor: '#44A08D' }}>
              <LockClosedIcon size={18} color="#FFFFFF" />
            </View>
            <View className="flex-1">
              <Text className="font-bold text-base text-slate-900">Confirmation requise</Text>
              <Text className="text-[11px] text-slate-500 mt-0.5">Entrez votre mot de passe pour révéler</Text>
            </View>
            <TouchableOpacity onPress={onClose} className="w-9 h-9 rounded-lg items-center justify-center bg-white">
              <XMarkIcon size={18} color="#64748B" />
            </TouchableOpacity>
          </View>

          {/* Body */}
          <View className="p-5">
            <Text className="text-[10px] uppercase font-bold text-slate-400 mb-2" style={{ letterSpacing: 1.5 }}>Mot de passe</Text>
            <View className="relative">
              <TextInput
                ref={inputRef}
                value={password}
                onChangeText={setPassword}
                placeholder="••••••••"
                placeholderTextColor="#94A3B8"
                secureTextEntry={!showPassword}
                autoCapitalize="none"
                autoComplete="current-password"
                returnKeyType="done"
                onSubmitEditing={handleVerify}
                className="w-full pl-3 pr-10 py-3 rounded-lg bg-slate-50 border border-slate-200 text-sm text-slate-900"
              />
              <TouchableOpacity
                onPress={() => setShowPassword(!showPassword)}
                className="absolute right-2 top-2 p-2"
              >
                {showPassword ? (
                  <EyeSlashIcon size={16} color="#64748B" />
                ) : (
                  <EyeIcon size={16} color="#64748B" />
                )}
              </TouchableOpacity>
            </View>

            {error && (
              <View className="flex-row items-center gap-1.5 mt-2 bg-rose-50 border border-rose-200 px-3 py-2 rounded-lg">
                <Text className="text-xs text-rose-600 flex-1">{error}</Text>
              </View>
            )}

            <View className="flex-row gap-2 mt-4">
              <TouchableOpacity
                onPress={onClose}
                className="flex-1 px-4 py-3 rounded-lg border border-slate-200 items-center justify-center active:bg-slate-50"
              >
                <Text className="text-slate-700 font-semibold text-sm">Annuler</Text>
              </TouchableOpacity>
              <TouchableOpacity
                onPress={handleVerify}
                disabled={loading || !password}
                className="flex-1 px-4 py-3 rounded-lg items-center justify-center flex-row gap-2"
                style={{ backgroundColor: !password || loading ? '#94CFC4' : '#44A08D', opacity: !password ? 0.6 : 1 }}
              >
                {loading && <ActivityIndicator size="small" color="#FFFFFF" />}
                <Text className="text-white font-bold text-sm">
                  {loading ? 'Vérification...' : 'Confirmer'}
                </Text>
              </TouchableOpacity>
            </View>

            <Text className="text-[10px] text-slate-400 text-center mt-3">
              Verrouillage automatique après 5 minutes d'inactivité.
            </Text>
          </View>
        </View>
      </View>
    </Modal>
  );
}
