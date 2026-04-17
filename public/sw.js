const CACHE_NAME = 'ministry-pwa-v2';
const OFFLINE_URL = '/offline.html';

// Assets to precache on install
const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/apple-touch-icon.png',
];

// Static asset patterns - use stale-while-revalidate
const STATIC_ASSET_PATTERNS = [
    /\/build\/assets\//,
    /\/icons\//,
    /\/images\//,
    /\.(?:png|jpg|jpeg|svg|gif|webp|ico)$/,
    /\.(?:woff|woff2|ttf|eot)$/,
];

// Paths to never intercept
const SKIP_PATHS = ['/fcm-token', '/login', '/logout', '/language', '/login-code'];

// ---- Install ----------------------------------------------------------------
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// ---- Activate: clean old caches --------------------------------------------
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((names) =>
            Promise.all(
                names
                    .filter((n) => n !== CACHE_NAME)
                    .map((n) => caches.delete(n))
            )
        )
    );
    self.clients.claim();
});

// ---- Fetch -----------------------------------------------------------------
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only handle GET
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // Skip external requests and auth-sensitive paths
    if (url.origin !== self.location.origin) return;
    if (SKIP_PATHS.some((p) => url.pathname.startsWith(p))) return;
    // Skip Firebase requests
    if (url.hostname.includes('firebase') || url.hostname.includes('googleapis')) return;

    const isStaticAsset = STATIC_ASSET_PATTERNS.some((p) => p.test(url.pathname));

    if (isStaticAsset) {
        // Stale-while-revalidate: serve cache instantly, update in background
        event.respondWith(
            caches.open(CACHE_NAME).then(async (cache) => {
                const cached = await cache.match(request);
                const networkFetch = fetch(request).then((res) => {
                    if (res.ok) cache.put(request, res.clone());
                    return res;
                }).catch(() => null);
                return cached || networkFetch;
            })
        );
    } else {
        // Network-first for dynamic Filament pages
        event.respondWith(
            fetch(request).catch(() =>
                caches.match(request).then(
                    (cached) =>
                        cached ||
                        (request.mode === 'navigate'
                            ? caches.match(OFFLINE_URL)
                            : null)
                )
            )
        );
    }
});

// ---- Notification click: navigate to the specific record -------------------
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    // Read deep-link URL from notification data payload
    const targetUrl = event.notification.data?.url || '/admin';

    event.waitUntil(
        clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Focus and navigate existing window if open
                for (const client of clientList) {
                    if ('focus' in client) {
                        client.focus();
                        if ('navigate' in client) client.navigate(targetUrl);
                        return;
                    }
                }
                // Otherwise open a new window at the target URL
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
    );
});

// ---- Push fallback (if FCM SW is not handling) -----------------------------
self.addEventListener('push', (event) => {
    if (!event.data) return;
    try {
        const data = event.data.json();
        const title = data.notification?.title || 'إشعار جديد';
        event.waitUntil(
            self.registration.showNotification(title, {
                body: data.notification?.body || '',
                icon: '/icons/icon-192x192.png',
                badge: '/icons/icon-72x72.png',
                dir: 'rtl',
                data: { url: data.data?.url || '/admin', ...data.data },
            })
        );
    } catch (e) {
        console.error('[sw.js] Push parse error:', e);
    }
});
