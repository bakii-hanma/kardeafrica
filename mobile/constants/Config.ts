export const Config = {
  // Production Backend
  // HTTPS obligatoire : iOS App Transport Security et Android API 28+ bloquent
  // par défaut le cleartext HTTP. Sans ça, axios échoue silencieusement et le
  // mobile retombe sur le fallback afrikard page 0 → biais "Call of Duty XBOX".
  API_URL: 'https://kardafrica.com/api',
  BASE_URL: 'https://kardafrica.com',
  STORAGE_URL: 'https://kardafrica.com/storage',

  // External APIs — fallback catalogue direct (utilisé seulement si l'API
  // kardafrica est injoignable). Pointe vers le VPS afrikard actuel ; l'ancien
  // hôte afrikard-api.duckdns.org est éteint (aucune réponse).
  CATALOG_API_URL: 'https://srv1882929.hstgr.cloud/api/v1',

  // Payment APIs
  PAYMENT_API_INIT: 'https://emoneygabon.alwaysdata.net/la-map-gabon/api/payment/init.php',
  PAYMENT_API_CHECK: 'https://emoneygabon.alwaysdata.net/la-map-gabon/api/payment/check_status.php',
  PAYMENT_API_EBILLING: 'https://stg.billing-easy.com/api/v1/merchant/e_bills',
  PAYMENT_PORTAL_BASE: 'https://staging.billing-easy.net/?invoice=',
};
