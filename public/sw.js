/*
 * KardAfrica — Service Worker (PWA)
 * Stratégie prudente :
 *  - Navigations (pages HTML) : NETWORK-FIRST → jamais de page connectée périmée
 *    servie depuis le cache ; repli sur le cache / l'accueil si hors-ligne.
 *  - Assets statiques (css/js/img/fonts) : CACHE-FIRST → app instantanée.
 *  - On ne met JAMAIS en cache les requêtes non-GET, cross-origin, l'admin,
 *    l'API, le panier/checkout/paiement (données sensibles/dynamiques).
 */
const CACHE = 'kardafrica-v1';
const ASSET_RE = /\.(?:css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf)$/i;
const BYPASS_RE = /^\/(admin|api|proprietaire|vendor|cart|checkout|payment|logout|connexion-whatsapp)(\/|$)/i;

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(caches.open(CACHE).then((c) => c.addAll(['/icons/icon-192.png'])).catch(() => {}));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;   // pas de cross-origin
  if (BYPASS_RE.test(url.pathname)) return;           // zones dynamiques/sensibles

  // Pages : network-first (fraîcheur d'abord)
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
          return res;
        })
        .catch(() => caches.match(req).then((r) => r || caches.match('/')))
    );
    return;
  }

  // Assets : cache-first
  if (ASSET_RE.test(url.pathname)) {
    event.respondWith(
      caches.match(req).then((cached) =>
        cached ||
        fetch(req).then((res) => {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
          return res;
        }).catch(() => cached)
      )
    );
  }
});
