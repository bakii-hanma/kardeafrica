import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';

/**
 * Stockage du jeton d'authentification (H1).
 *
 * Le token Sanctum débloque les cartes cadeaux d'un utilisateur : il ne doit
 * JAMAIS être en clair dans AsyncStorage. On le range dans le keychain/keystore
 * chiffré du système via expo-secure-store.
 *
 * Migration douce : au premier accès après mise à jour, un token présent dans
 * l'ancien emplacement AsyncStorage est déplacé vers SecureStore puis effacé —
 * les utilisateurs déjà connectés ne sont pas déconnectés.
 */
const SECURE_KEY = 'auth_token';
const LEGACY_KEY = 'token';

export async function setToken(token: string): Promise<void> {
  await SecureStore.setItemAsync(SECURE_KEY, token);
  // S'assure qu'aucune copie en clair ne subsiste.
  try { await AsyncStorage.removeItem(LEGACY_KEY); } catch {}
}

export async function getToken(): Promise<string | null> {
  let token = await SecureStore.getItemAsync(SECURE_KEY);
  if (!token) {
    // Récupère et migre un éventuel ancien token en clair.
    try {
      const legacy = await AsyncStorage.getItem(LEGACY_KEY);
      if (legacy) {
        await SecureStore.setItemAsync(SECURE_KEY, legacy);
        await AsyncStorage.removeItem(LEGACY_KEY);
        token = legacy;
      }
    } catch {}
  }
  return token;
}

export async function deleteToken(): Promise<void> {
  try { await SecureStore.deleteItemAsync(SECURE_KEY); } catch {}
  try { await AsyncStorage.removeItem(LEGACY_KEY); } catch {}
}
