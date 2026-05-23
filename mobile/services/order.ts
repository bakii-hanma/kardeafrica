import AsyncStorage from '@react-native-async-storage/async-storage';
import { Config } from '../constants/Config';

const API_URL = Config.API_URL;

export interface OrderItem {
  id: number;
  order_id: number;
  product_id: string;
  name: string;
  quantity: number;
  unit_price: number;
  total_price: number;
  image_url?: string;
}

export interface Order {
  id: number;
  order_number: string;
  status: string;
  payment_status: string;
  subtotal: number;
  tax_amount: number;
  total_amount: number;
  currency: string;
  payment_method: string;
  notes?: string;
  completed_at?: string;
  created_at: string;
  updated_at: string;
  order_items: OrderItem[];
}

export const OrderService = {
  async getOrders(): Promise<Order[]> {
    try {
      const token = await AsyncStorage.getItem('token');
      if (!token) throw new Error('No token found');

      const response = await fetch(`${API_URL}/orders`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      const data = await response.json();

      if (data.success && data.data) {
        return data.data.data || data.data; // Handle pagination or direct array
      }

      return [];
    } catch (error) {
      console.error('Error fetching orders:', error);
      throw error;
    }
  },

  /**
   * Relance la récupération des cartes pour une commande payée dont la livraison
   * a échoué (afrikard down, timeout, job stuck). Mirror du bouton "Relancer"
   * sur la page web /orders. Réponses possibles :
   *  - 200 success + cards livrées
   *  - 202 cards_pending (fallback async lancé)
   *  - 200 already_delivered (idempotent)
   *  - 422 / 503 erreur (paiement KO, catalogue indispo)
   */
  async retryDelivery(orderId: number): Promise<{
    success: boolean;
    cards_pending?: boolean;
    already_delivered?: boolean;
    message?: string;
    cards?: any[];
  }> {
    try {
      const token = await AsyncStorage.getItem('token');
      if (!token) throw new Error('No token found');

      const response = await fetch(`${API_URL}/orders/${orderId}/retry-delivery`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });
      return await response.json();
    } catch (error: any) {
      console.error('Error retrying delivery:', error);
      return { success: false, message: error?.message ?? 'Erreur réseau.' };
    }
  },

  async getOrder(id: number): Promise<{ order: Order; cards: any[] }> {
    try {
      const token = await AsyncStorage.getItem('token');
      if (!token) throw new Error('No token found');

      const response = await fetch(`${API_URL}/orders/${id}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      const data = await response.json();

      if (data.success) {
        return {
          order: data.order,
          cards: data.cards || [],
        };
      }

      throw new Error(data.message || 'Failed to load order');
    } catch (error) {
      console.error('Error fetching order:', error);
      throw error;
    }
  },

  formatStatus(status: string): string {
    const statusMap: Record<string, string> = {
      'pending': 'En attente',
      'processing': 'En cours',
      'completed': 'Terminee',
      'cancelled': 'Annulee',
      'failed': 'Echouee',
    };
    return statusMap[status] || status;
  },

  getStatusColor(status: string): string {
    const colorMap: Record<string, string> = {
      'pending': '#F59E0B',
      'processing': '#3B82F6',
      'completed': '#10B981',
      'cancelled': '#EF4444',
      'failed': '#EF4444',
    };
    return colorMap[status] || '#6B7280';
  },

  formatAmount(amount: number | string | null | undefined, currency: string = 'XAF'): string {
    // Laravel cast `decimal:2` renvoie des strings ("12500.00"), pas des nombres.
    // Sans coercion, Intl.NumberFormat.format("NaN") sort "NaN FCFA".
    const n = Number(amount);
    return new Intl.NumberFormat('fr-FR').format(Number.isFinite(n) ? n : 0) + ' FCFA';
  },
};
