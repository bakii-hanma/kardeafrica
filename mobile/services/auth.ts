import { Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getToken } from './tokenStore';
import { Config } from '../constants/Config';

// Use Config.API_URL for API requests
const BASE_URL = Config.API_URL;

// Interface for User
export interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
}

// Interface for Auth Response
export interface AuthResponse {
  status: string;
  message?: string;
  data?: {
    user: User;
    token: string;
  };
  errors?: any;
}

export const AuthService = {
  // Verifie le mot de passe de l'utilisateur connecte (gate pour reveal de cartes)
  verifyPassword: async (password: string): Promise<{ ok: boolean; message?: string }> => {
    try {
      const token = await getToken();
      if (!token) return { ok: false, message: 'Non connecté' };

      const response = await fetch(`${BASE_URL}/verify-password`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({ password }),
      });

      const data = await response.json();
      return { ok: !!data.ok, message: data.message };
    } catch (e: any) {
      return { ok: false, message: e?.message || 'Erreur réseau' };
    }
  },

  // Login
  login: async (email: string, password: string): Promise<AuthResponse> => {
    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000); // 15s timeout

      console.log('Attempting login to:', `${BASE_URL}/login`);
      const response = await fetch(`${BASE_URL}/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ email, password }),
        signal: controller.signal
      });
      clearTimeout(timeoutId);

      console.log('Login Response Status:', response.status);
      const data = await response.json();
      console.log('Login Response Data:', data);
      
      if (!response.ok) {
        return {
            status: 'error',
            message: data.message || 'Authentication failed',
            errors: data.errors
        };
      }
      
      return data;
    } catch (error) {
      console.error('Login Error:', error);
      return {
        status: 'error',
        message: 'Impossible de se connecter au serveur. Vérifiez votre connexion internet.',
      };
    }
  },

  // Register
  register: async (firstName: string, lastName: string, email: string, password: string, phone: string): Promise<AuthResponse> => {
    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000); // 15s timeout

      console.log('Attempting register to:', `${BASE_URL}/register`);
      const response = await fetch(`${BASE_URL}/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          first_name: firstName,
          last_name: lastName,
          email,
          password,
          password_confirmation: password,
          phone,
        }),
        signal: controller.signal
      });
      clearTimeout(timeoutId);

      console.log('Register Response Status:', response.status);
      const data = await response.json();
      console.log('Register Response Data:', data);

      if (!response.ok) {
        return {
            status: 'error',
            message: data.message || 'Registration failed',
            errors: data.errors
        };
      }

      return data;
    } catch (error) {
      console.error('Register Error:', error);
      return {
        status: 'error',
        message: 'Impossible de se connecter au serveur. Vérifiez votre connexion internet.',
      };
    }
  },

  // Logout
  logout: async (token: string): Promise<AuthResponse> => {
    try {
      const response = await fetch(`${BASE_URL}/logout`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Logout Error:', error);
      return {
        status: 'error',
        message: 'Network request failed',
      };
    }
  },

  // Get User
  getUser: async (token: string): Promise<AuthResponse> => {
    try {
      const response = await fetch(`${BASE_URL}/user`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Get User Error:', error);
      return {
        status: 'error',
        message: 'Network request failed',
      };
    }
  },

  // Update Profile
  updateProfile: async (
    firstName: string, 
    lastName: string, 
    phone: string, 
    dateOfBirth?: string,
    gender?: string,
    country?: string,
    city?: string,
    address?: string,
    avatar?: { uri: string; type: string; name: string } | null
  ): Promise<AuthResponse> => {
    try {
      const token = await getToken();
      if (!token) throw new Error('No token found');

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 30000); // Increased timeout for file upload

      const formData = new FormData();
      formData.append('first_name', firstName);
      formData.append('last_name', lastName);
      formData.append('phone', phone);
      if (dateOfBirth) formData.append('date_of_birth', dateOfBirth);
      if (gender) formData.append('gender', gender);
      if (country) formData.append('country', country);
      if (city) formData.append('city', city);
      if (address) formData.append('address', address);
      
      if (avatar) {
        formData.append('avatar', {
          uri: avatar.uri,
          type: avatar.type,
          name: avatar.name,
        } as any);
      }

      // Note: When sending FormData, do NOT set Content-Type to application/json
      // Let the browser/network stack handle the boundary
      const response = await fetch(`${BASE_URL}/profile/update`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: formData,
        signal: controller.signal
      });
      clearTimeout(timeoutId);

      const data = await response.json();
      
      if (!response.ok) {
        return {
          status: 'error',
          message: data.message || 'Update failed',
          errors: data.errors
        };
      }
      
      return data;
    } catch (error) {
      console.error('Update Profile Error:', error);
      return {
        status: 'error',
        message: 'Impossible de mettre à jour le profil.',
      };
    }
  },

  // Change Password
  changePassword: async (currentPassword: string, newPassword: string): Promise<AuthResponse> => {
    try {
      const token = await getToken();
      if (!token) throw new Error('No token found');

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000);

      const response = await fetch(`${BASE_URL}/profile/password`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          current_password: currentPassword,
          new_password: newPassword,
          new_password_confirmation: newPassword
        }),
        signal: controller.signal
      });
      clearTimeout(timeoutId);

      const data = await response.json();
      
      if (!response.ok) {
        return {
          status: 'error',
          message: data.message || 'Change password failed',
          errors: data.errors
        };
      }
      
      return data;
    } catch (error) {
      console.error('Change Password Error:', error);
      return {
        status: 'error',
        message: 'Impossible de modifier le mot de passe.',
      };
    }
  }
};
