import axios from 'axios';
import { Config } from '../constants/Config';
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_URL = Config.API_URL;

export interface Card {
  id: number;
  name: string;
  type: string;
  value: number;
  balance: number;
  currency: string;
  code: string;
  expiry_date?: string;
  image?: string;
  status: 'active' | 'expired' | 'used';
  description?: string;
  brand?: string;
  created_at: string;
  updated_at: string;
}

export const CardService = {
  async getCards(): Promise<Card[]> {
    try {
      const token = await AsyncStorage.getItem('token');
      if (!token) throw new Error('No token found');

      const response = await axios.get(`${API_URL}/cards`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching cards:', error);
      throw error;
    }
  },

  async getCard(id: number): Promise<Card> {
    try {
      const token = await AsyncStorage.getItem('token');
      if (!token) throw new Error('No token found');

      const response = await axios.get(`${API_URL}/cards/${id}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching card:', error);
      throw error;
    }
  },
  
  // Helper to format balance
  formatBalance(amount: number, currency: string): string {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: currency,
    }).format(amount);
  }
};
