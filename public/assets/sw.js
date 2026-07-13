/**
 * Service Worker — basic cache-first strategy for static assets
 * Improves page load speed on repeat visits and provides limited offline support.
 */
const CACHE_NAME = 'medcore-v1';
const PRECACHE_URLS = [
    '/',
    '/login',
    '/health',
    '/public/assets/css/style.css',
    '/public/assets/js/app.js',
    '/public/assets/manifest.json',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS).catch(() => {}))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    // Only handle GET
    if (req.method !== 'GET') return;

    // Skip cross-origin requests (CDN assets will be re-fetched)
    const url = new URL(req.url);
    if (url.origin !== location.origin) return;

    // Skip admin/perf endpoints
    if (url.pathname.startsWith('/admin') || url.pathname === '/perf' || url.pathname === '/download-logs') return;

    // For navigation requests: network-first, fall back to cache
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req)
                .then((res) => {
                    const clone = res.clone();
                    caches.open(CACHE_NAME).then((c) => c.put(req, clone)).catch(() => {});
                    return res;
                })
                .catch(() => caches.match(req).then((r) => r || caches.match('/')))
        );
        return;
    }

    // For static assets: cache-first
    event.respondWith(
        caches.match(req).then((cached) => {
            if (cached) return cached;
            return fetch(req).then((res) => {
                // Cache successful responses for static assets
                if (res.ok && (req.url.includes('/public/assets/') || req.url.match(/\.(css|js|png|jpg|svg|woff2)$/))) {
                    const clone = res.clone();
                    caches.open(CACHE_NAME).then((c) => c.put(req, clone)).catch(() => {});
                }
                return res;
            });
        })
    );
});
