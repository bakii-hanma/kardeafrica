import { Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getToken } from './tokenStore';
import { Config } from '../constants/Config';

// --- Constants ---
const API_URL_INIT = Config.PAYMENT_API_INIT;
const API_URL_CHECK = Config.PAYMENT_API_CHECK;
const PORTAL_URL_BASE = Config.PAYMENT_PORTAL_BASE;

// C6 — la clé marchand billing-easy N'EST PLUS dans l'app : la création d'e-bill
// passe par le serveur (POST /api/payment/create-ebill), clé dans le .env serveur.

// --- Types ---

export interface PaymentInitResponse {
  success: boolean;
  data?: {
    mysql_id: number;
  };
  message?: string;
}

export interface EBillResponse {
  success?: boolean;
  bill_id?: string | null;
  portal_url?: string | null;
  amount?: number;
  e_bill?: {
    bill_id: string;
  };
  message?: string;
}

export interface PaymentStatusResponse {
  success: boolean;
  data?: {
    status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled' | 'expired';
    amount?: number;
    wallet_credited?: boolean;
  };
}

export interface CheckoutCard {
  id: number;
  name: string;
  type: string;
  value: number;
  balance: number;
  currency: string;
  code: string;
  pin?: string;
  serial_number?: string;
  expiry_date?: string;
  image?: string;
  status: 'active' | 'expired' | 'used';
  brand?: string;
  order_id?: number;
  created_at: string;
  updated_at: string;
}

export interface FinalizeResponse {
  success: boolean;
  message?: string;
  order_id?: number;
  order?: any;
  cards?: CheckoutCard[];
  redirect_url?: string;
}

// --- Helper Functions ---

export function generateExternalReference(): string {
  const timestamp = Date.now();
  const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
  return `KARD_${timestamp}_${random}`;
}

export function formatPhoneNumber(phone?: string): string {
  if (!phone) return '24174000000';

  let cleaned = phone.replace(/\D/g, '');

  if (cleaned.startsWith('00')) cleaned = cleaned.substring(2);
  if (cleaned.startsWith('241')) return cleaned;
  if (cleaned.startsWith('0')) return '241' + cleaned.substring(1);
  return '241' + cleaned;
}

// --- API Functions ---

/**
 * Step 1: Initialize transaction in backend
 */
export async function initBackendTransaction(
  userId: string,
  amount: number,
  phoneNumber: string,
  description: string,
  externalReference: string
): Promise<PaymentInitResponse> {
  try {
    const response = await fetch(API_URL_INIT, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        user_id: userId,
        amount: Math.round(amount),
        phone_number: formatPhoneNumber(phoneNumber),
        payment_system: 'ebilling',
        transaction_type: 'deposit',
        currency: 'XAF',
        description: description.substring(0, 100),
        external_reference: externalReference,
      }),
    });

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error initializing backend transaction:', error);
    return { success: false, message: 'Network error connecting to backend' };
  }
}

/**
 * Step 2: Create E-Bill — VIA LE SERVEUR (C6).
 *
 * La clé marchand billing-easy ne vit plus dans l'app (elle était extractible de
 * l'APK). On appelle un endpoint Laravel authentifié (Sanctum) qui crée l'e-bill
 * avec la clé côté serveur ET un montant recalculé serveur depuis le catalogue
 * (on envoie les product_id + quantités, jamais un montant).
 */
export async function createEBill(
  items: Array<{ product_id: string; quantity: number }>,
  description: string,
  externalReference: string,
  email: string,
  phoneNumber: string,
  name: string
): Promise<EBillResponse> {
  try {
    const token = await getToken();
    const response = await fetch(`${Config.API_URL}/payment/create-ebill`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        items,
        external_reference: externalReference,
        phone: phoneNumber,
        email,
        name,
        description: description.substring(0, 100),
      }),
    });

    const data = await response.json();
    return data; // { success, bill_id, portal_url, e_bill }
  } catch (error) {
    console.error('Error creating e-bill:', error);
    return { message: 'Network error connecting to payment server' };
  }
}

/**
 * Step 3: Check Status (Polling)
 */
export async function checkPaymentStatus(externalReference: string): Promise<PaymentStatusResponse> {
  try {
    const response = await fetch(`${API_URL_CHECK}?external_reference=${externalReference}`, {
      method: 'GET',
    });
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error checking status:', error);
    return { success: false };
  }
}

/**
 * Step 4: Finalize payment - calls Laravel API which calls checkout API
 * and saves the cards to user_cards table
 */
export async function finalizePayment(externalReference: string): Promise<FinalizeResponse> {
  try {
    const token = await getToken();
    if (!token) {
      return { success: false, message: 'Non authentifie' };
    }

    const response = await fetch(`${Config.API_URL}/payment/finalize`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        external_reference: externalReference,
      }),
    });

    const data = await response.json();
    console.log('Finalize response:', data);
    return data;
  } catch (error) {
    console.error('Error finalizing payment:', error);
    return { success: false, message: 'Erreur reseau lors de la finalisation' };
  }
}

/**
 * Helper to get the portal URL
 */
export function getPortalUrl(billId: string): string {
  return `${PORTAL_URL_BASE}${billId}`;
}

// ============================================================
//  SIMULATE PAYMENT (DEV ONLY)
// ============================================================
export interface SimulatePaymentResponse {
  success: boolean;
  simulated?: boolean;
  order?: any;
  order_id?: number;
  cards_delivered?: number;
  delivery_error?: string | null;
  message?: string;
}

/**
 * POST /api/orders/simulate — paiement simulé pour le dev
 * Crée l'order, le marque payé et tente la livraison via afrikard.
 */
export async function simulatePayment(items: Array<{
  product_id: string;
  name: string;
  price: number;
  quantity: number;
  image_url?: string;
}>): Promise<SimulatePaymentResponse> {
  try {
    const token = await getToken();
    if (!token) return { success: false, message: 'Non authentifié' };

    const response = await fetch(`${Config.API_URL}/orders/simulate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({ items }),
    });

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error simulating payment:', error);
    return { success: false, message: 'Erreur réseau lors de la simulation' };
  }
}
