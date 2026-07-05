/* SuperLMS PWA service worker — conservative, network-first.
 *
 * Goals:
 *  - Make the app installable (has a fetch handler + manifest).
 *  - NEVER break the live app: online users always get fresh HTML (network-first),
 *    Livewire/API/cross-origin requests are passed straight through, and only
 *    hashed build assets + static images/fonts are cached.
 *  - Provide a small offline fallback page for navigations while offline.
 *
 * Bump CACHE_VERSION to force old caches to clear on the next deploy.
 */
const CACHE_VERSION = 'v1';
const CACHE = 'superlms-' + CACHE_VERSION;
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/website-image/Logo.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((c) => c.addAll(PRECACHE))
            .catch(() => {})
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.map((k) => (k === CACHE ? Promise.resolve() : caches.delete(k))));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    let url;
    try { url = new URL(req.url); } catch (e) { return; }

    // Only handle our own origin; leave CDNs, APIs and analytics alone.
    if (url.origin !== self.location.origin) return;

    // Never intercept dynamic/back-end endpoints.
    if (url.pathname.startsWith('/livewire') ||
        url.pathname.startsWith('/v1') ||
        url.pathname.startsWith('/api') ||
        url.pathname.startsWith('/broadcasting') ||
        url.pathname.startsWith('/sanctum')) {
        return;
    }

    // Hashed build assets + static media/fonts → cache-first (safe, content-addressed).
    const isStatic = url.pathname.startsWith('/build/') ||
        /\.(?:css|js|mjs|png|jpe?g|svg|webp|gif|ico|woff2?|ttf|eot)$/i.test(url.pathname);

    if (isStatic) {
        event.respondWith((async () => {
            const cached = await caches.match(req);
            if (cached) return cached;
            try {
                const res = await fetch(req);
                if (res && res.status === 200 && res.type === 'basic') {
                    const cache = await caches.open(CACHE);
                    cache.put(req, res.clone());
                }
                return res;
            } catch (e) {
                return cached || Response.error();
            }
        })());
        return;
    }

    // Page navigations → network-first, fall back to the offline page.
    if (req.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                return await fetch(req);
            } catch (e) {
                const cache = await caches.open(CACHE);
                return (await cache.match(OFFLINE_URL)) || Response.error();
            }
        })());
    }
    // Everything else (e.g. wire:navigate HTML fetches) → default network handling.
});
