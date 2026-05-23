// Service Worker minimal pour le portail vendeur Kardafrica.
// Critères PWA installable :
//  - Manifest avec icônes 192/512 (✓ vendor-manifest.webmanifest)
//  - HTTPS (✓ kardafrica.com)
//  - Service Worker enregistré avec un fetch handler (ce fichier)
//
// Stratégie simple : network-first avec fallback offline minimal.
// On ne pré-cache PAS les pages dynamiques (dashboard, ordres) car elles
// changent en permanence. On cache uniquement le shell statique.

const CACHE_NAME = 'kardafrica-vendor-v1';
const SHELL_ASSETS = [
    '/assets/logo/FAVCON-KARDAFRICA-.png',
    '/assets/logo/LOGO KARDAFRICA .png',
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_ASSETS).catch(() => {}))
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // On ne touche QUE le scope /vendor/ et les assets — laisse passer le reste
    const url = new URL(req.url);
    const sameOrigin = url.origin === self.location.origin;
    if (!sameOrigin) return;

    // Network-first pour les pages HTML et l'API
    if (req.mode === 'navigate' || req.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(req).catch(() => caches.match(req).then((r) => r || caches.match('/vendor/dashboard')))
        );
        return;
    }

    // Cache-first pour les assets statiques (images, css, js)
    if (req.method === 'GET' && (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/build/'))) {
        event.respondWith(
            caches.match(req).then((cached) => {
                if (cached) return cached;
                return fetch(req).then((res) => {
                    if (res.ok) {
                        const copy = res.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
                    }
                    return res;
                });
            })
        );
    }
});
