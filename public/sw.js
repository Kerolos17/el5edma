/*
 * Legacy kill-switch service worker.
 *
 * The canonical worker now lives at /sw-v9.js. Devices that still hold the
 * old /sw.js registration (registered by earlier bundles, re-fetched from
 * the CDN cache) keep re-installing the stale v8 worker, which serves
 * outdated cached assets and 503 fallbacks. This replacement self-destructs:
 * it clears every cache except the current v9 one, unregisters itself, and
 * reloads its clients so they come back under the v9 worker.
 */
const KEEP_CACHE = "ministry-pwa-v9";

self.addEventListener("install", () => {
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        (async () => {
            const names = await caches.keys();
            await Promise.all(
                names
                    .filter((name) => name !== KEEP_CACHE)
                    .map((name) => caches.delete(name)),
            );

            await self.registration.unregister();

            const clients = await self.clients.matchAll({ type: "window" });
            for (const client of clients) {
                try {
                    await client.navigate(client.url);
                } catch {
                    // Cross-origin or privileged client — leave it alone.
                }
            }
        })(),
    );
});

// No fetch handler: once active, this worker must never intercept requests.
