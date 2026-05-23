
// Currency conversion rates relative to FCFA (XAF)
export const EXCHANGE_RATES: Record<string, number> = {
  'XAF': 1,
  'EUR': 655.957,
  'USD': 620,
  'AED': 170,
  'GBP': 780,
  'CAD': 450,
  'ARS': 0.7, // Argentine Peso
  'AUD': 405, // Australian Dollar
  'BRL': 120, // Brazilian Real
  'TRY': 20,  // Turkish Lira
  'MXN': 35,  // Mexican Peso
  'INR': 7.5, // Indian Rupee
  'ZAR': 35,  // South African Rand
  'NGN': 0.4, // Nigerian Naira
};

/**
 * Converts an amount from a source currency to FCFA (XAF).
 * @param amount The amount in the source currency.
 * @param currencyCode The source currency code (e.g., 'EUR', 'USD').
 * @returns The amount in FCFA.
 */
export const convertToFCFA = (amount: number, currencyCode: string): number => {
  const rate = EXCHANGE_RATES[currencyCode.toUpperCase()] || 0;
  if (rate === 0) {
    console.warn(`No exchange rate found for currency: ${currencyCode}`);
    return amount; // Fallback to original amount if rate not found (or should we return 0?)
  }
  return Math.round(amount * rate);
};

/**
 * Formats an amount in FCFA.
 * @param amount The amount in FCFA.
 * @returns Formatted string (e.g., "10 000 FCFA").
 */
export const formatFCFA = (amount: number): string => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount).replace('XAF', 'FCFA');
};

/**
 * Converts and formats a price to FCFA.
 * @param amount The amount in the source currency.
 * @param currencyCode The source currency code.
 * @returns Formatted string in FCFA.
 */
export const convertAndFormatToFCFA = (amount: number, currencyCode: string): string => {
  if (currencyCode.toUpperCase() === 'XAF' || currencyCode.toUpperCase() === 'FCFA') {
    return formatFCFA(amount);
  }
  const fcfaAmount = convertToFCFA(amount, currencyCode);
  return formatFCFA(fcfaAmount);
};
