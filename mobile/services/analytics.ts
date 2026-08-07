/**
 * Analytics — Meta / Facebook App Events (react-native-fbsdk-next).
 *
 * Équivalent "app" du Pixel web : mesure installs, sessions et conversions
 * (Purchase) côté application mobile. L'App ID / client token sont injectés au
 * build via le plugin `react-native-fbsdk-next` dans app.json.
 *
 * Le SDK est requis en lazy (require) pour ne pas planter dans un environnement
 * où le module natif n'est pas présent (Expo Go). En dev build / build EAS, il
 * est disponible et les événements remontent dans Events Manager.
 */

let fb: any = null;
try {
  // eslint-disable-next-line @typescript-eslint/no-var-requires
  fb = require('react-native-fbsdk-next');
} catch {
  fb = null;
}

let initialized = false;

export function initAnalytics(): void {
  if (initialized || !fb?.Settings) return;
  try {
    // Consentement collecte (ATT iOS géré par le prompt système si activé).
    fb.Settings.setAdvertiserTrackingEnabled(true);
    fb.Settings.initializeSDK();
    initialized = true;
  } catch (e) {
    // silencieux — pas bloquant pour l'app
  }
}

/** Achat confirmé — l'événement de conversion n°1. */
export function logPurchase(amount: number, currency: string = 'XAF', params: Record<string, any> = {}): void {
  if (!fb?.AppEventsLogger || !amount || amount <= 0) return;
  try {
    fb.AppEventsLogger.logPurchase(Number(amount), currency, params);
  } catch (e) {}
}

/** Ajout au panier. */
export function logAddToCart(amount: number, currency: string = 'XAF', params: Record<string, any> = {}): void {
  if (!fb?.AppEventsLogger) return;
  try {
    fb.AppEventsLogger.logEvent('AddToCart', Number(amount) || 0, { fb_currency: currency, ...params });
  } catch (e) {}
}

/** Début de checkout. */
export function logInitiateCheckout(amount: number, currency: string = 'XAF', params: Record<string, any> = {}): void {
  if (!fb?.AppEventsLogger) return;
  try {
    fb.AppEventsLogger.logEvent('InitiatedCheckout', Number(amount) || 0, { fb_currency: currency, ...params });
  } catch (e) {}
}

/** Événement générique. */
export function logEvent(name: string, params: Record<string, any> = {}): void {
  if (!fb?.AppEventsLogger) return;
  try {
    fb.AppEventsLogger.logEvent(name, params);
  } catch (e) {}
}
