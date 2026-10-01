const CACHE_NAME = "ministry-pwa-v10";
const SW_VERSION = "v10";
const OFFLINE_URL = "/offline.html";
const DEFAULT_NOTIFICATION_URL = "/app/dashboard";

// The Firebase compat SDK MUST be imported during the worker's initial
// evaluation: importScripts() is forbidden after installation, so loading
// it lazily inside a message handler always throws — and blob-based
// fallbacks are impossible because they rely on an API that service
// workers do not provide. Same-origin copies (public/firebase/) avoid the
// gstatic/CDN failures seen in the field; if these imports fail the
// install fails and the browser retries, which is the correct failure mode.
importScripts("/firebase/firebase-app-compat.js");
importScripts("/firebase/firebase-messaging-compat.js");

let firebaseMessaging = null;

// Assets to precache on install
const PRECACHE_ASSETS = [
    OFFLINE_URL,
    "/manifest.json",
    "/icons/icon-192x192.png",
    "/icons/icon-512x512.png",
    "/icons/apple-touch-icon.png",
    "/firebase/firebase-app-compat.js",
    "/firebase/firebase-messaging-compat.js",
];

// Static asset patterns - use stale-while-revalidate
const STATIC_ASSET_PATTERNS = [
    /\/build\/assets\//,
    /\/icons\//,
    /\/images\//,
    /\.(?:png|jpg|jpeg|svg|gif|webp|ico)$/,
    /\.(?:woff|woff2|ttf|eot)$/,
];

// Bound Cache Storage growth on mobile: keep only the newest entries.
const MAX_CACHE_ENTRIES = 80;

async function trimCache(cache) {
    try {
        const keys = await cache.keys();
        if (keys.length <= MAX_CACHE_ENTRIES) return;
        await Promise.all(
            keys.slice(0, keys.length - MAX_CACHE_ENTRIES).map((key) => cache.delete(key)),
        );
    } catch {
        // Cache maintenance must never break serving.
    }
}

// Paths to never intercept
const SKIP_PATHS = [
    "/_pwa/ping",
    "/fcm-token",
    "/login",
    "/logout",
    "/language",
    "/login-code",
    // Vite build output is content-hashed + immutable via HTTP cache headers.
    // Intercepting it breaks <link rel="preload"> (cross-world mismatch
    // warnings) and adds zero offline value, so let the browser handle it.
    "/build/",
];

function parseBooleanish(value, fallback = false) {
    if (typeof value === "boolean") {
        return value;
    }

    if (typeof value === "string") {
        return value === "true";
    }

    return fallback;
}

function parseVibrationPattern(value) {
    if (Array.isArray(value)) {
        return value
            .map((item) => Number(item))
            .filter((item) => Number.isFinite(item));
    }

    if (typeof value !== "string" || value.length === 0) {
        return [];
    }

    try {
        const parsed = JSON.parse(value);

        return Array.isArray(parsed)
            ? parsed
                  .map((item) => Number(item))
                  .filter((item) => Number.isFinite(item))
            : [];
    } catch {
        return [];
    }
}

function safeNotificationTarget(value) {
    if (typeof value !== "string" || value.length === 0) {
        return DEFAULT_NOTIFICATION_URL;
    }

    try {
        const target = new URL(value, self.location.origin);

        if (target.origin !== self.location.origin) {
            return DEFAULT_NOTIFICATION_URL;
        }

        const path = target.pathname;
        const isAppPath = path === "/app" || path.startsWith("/app/");
        const isAdminPath = path === "/admin" || path.startsWith("/admin/");

        if (!isAppPath && !isAdminPath) {
            return DEFAULT_NOTIFICATION_URL;
        }

        return `${path}${target.search}${target.hash}`;
    } catch {
        return DEFAULT_NOTIFICATION_URL;
    }
}

function notificationDirection(payload) {
    const locale = payload.data?.locale;

    if (locale === "en") {
        return "ltr";
    }

    if (locale === "ar") {
        return "rtl";
    }

    return "auto";
}

function showNotificationFromPayload(payload) {
    const notificationData = payload.data ?? {};
    const notificationTitle = payload.notification?.title || "إشعار جديد";
    const notificationOptions = {
        body: payload.notification?.body || "",
        icon: "/icons/icon-192x192.png",
        badge: "/icons/icon-72x72.png",
        dir: notificationDirection(payload),
        tag: notificationData.tag || "ministry-generic",
        renotify: parseBooleanish(notificationData.renotify, false),
        requireInteraction: parseBooleanish(
            notificationData.require_interaction,
            false,
        ),
        vibrate: parseVibrationPattern(notificationData.vibrate),
        silent: false,
        data: {
            ...notificationData,
            url: safeNotificationTarget(notificationData.url),
        },
    };

    return self.registration.showNotification(
        notificationTitle,
        notificationOptions,
    );
}

async function ensureFirebaseMessaging(config) {
    if (!config) {
        return null;
    }

    if (firebaseMessaging) {
        return firebaseMessaging;
    }

    try {
        // firebase was imported at the top level (see above). If that import
        // ever fails the install fails and the browser retries — there is no
        // legal lazy fallback inside a service worker.
        if (typeof firebase === "undefined") {
            throw new Error("Firebase compat SDK missing from worker global");
        }

        if (!firebase.apps.length) {
            firebase.initializeApp(config);
        }

        firebaseMessaging = firebase.messaging();
        firebaseMessaging.onBackgroundMessage((payload) => {
            console.log("[sw.js] Background Firebase message:", payload);

            // FCM automatically displays notification+data payloads in the
            // background. Only data-only payloads need manual rendering here.
            const displayPromise = payload.notification
                ? Promise.resolve()
                : showNotificationFromPayload(payload);

            // Forward the payload to any open clients so Livewire can refresh.
            const clientPromise = clients
                .matchAll({ type: "window", includeUncontrolled: true })
                .then((clientList) => {
                    for (const client of clientList) {
                        try {
                            client.postMessage({
                                type: "FCM_BACKGROUND_MESSAGE",
                                payload,
                            });
                        } catch {
                            // Ignore individual client post failures.
                        }
                    }
                })
                .catch((error) => {
                    console.warn(
                        "[sw.js] Failed to postMessage to clients:",
                        error,
                    );
                });

            return Promise.all([displayPromise, clientPromise]);
        });

        return firebaseMessaging;
    } catch (error) {
        console.error(
            "[sw.js] Firebase messaging initialization error:",
            error,
        );

        return null;
    }
}

self.addEventListener("message", (event) => {
    try {
        console.log(
            "[sw.js] Received message in SW:",
            event.data?.type || event.data,
        );
    } catch {}

    if (event.data?.type === "FIREBASE_CONFIG") {
        console.log(
            "[sw.js] Initializing Firebase messaging with config from page",
        );
        event.waitUntil(
            ensureFirebaseMessaging(event.data.config).catch((error) => {
                console.error("[sw.js] Firebase init failed:", error);
            }),
        );
    }
});

// ---- Install ----------------------------------------------------------------
self.addEventListener("install", (event) => {
    console.log(`[sw ${SW_VERSION}] Installing`);
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then(async (cache) => {
                await Promise.allSettled(
                    PRECACHE_ASSETS.map(async (asset) => {
                        const response = await fetch(asset, {
                            cache: "no-cache",
                        });

                        if (!response.ok) {
                            throw new Error(
                                `Failed to precache ${asset}: ${response.status}`,
                            );
                        }

                        await cache.put(asset, response);
                    }),
                );
            })
            .then(() => self.skipWaiting()),
    );
});

// ---- Activate: clean old caches --------------------------------------------
self.addEventListener("activate", (event) => {
    console.log(`[sw ${SW_VERSION}] Activated`);
    event.waitUntil(
        caches
            .keys()
            .then((names) =>
                Promise.all(
                    names
                        .filter((n) => n !== CACHE_NAME)
                        .map((n) => caches.delete(n)),
                ),
            ),
    );
    self.clients.claim();
});

// ---- Fetch -----------------------------------------------------------------
self.addEventListener("fetch", (event) => {
    const { request } = event;

    // Only handle GET
    if (request.method !== "GET") return;

    const url = new URL(request.url);

    // Skip external requests and auth-sensitive paths
    if (url.origin !== self.location.origin) return;
    if (SKIP_PATHS.some((p) => url.pathname.startsWith(p))) return;
    // Skip Firebase requests
    if (
        url.hostname.includes("firebase") ||
        url.hostname.includes("googleapis")
    )
        return;

    const isStaticAsset = STATIC_ASSET_PATTERNS.some((p) =>
        p.test(url.pathname),
    );

    if (isStaticAsset) {
        // Stale-while-revalidate: serve cache instantly, update in background.
        // NOTE: must always resolve to a Response object, never null/undefined.
        event.respondWith(
            (async () => {
                const cache = await caches.open(CACHE_NAME);
                const cached = await cache.match(request);

                try {
                    const networkRes = await fetch(request);

                    if (networkRes && networkRes.ok) {
                        cache.put(request, networkRes.clone());
                        trimCache(cache);
                    }

                    return (
                        cached ||
                        networkRes ||
                        new Response("", {
                            status: 504,
                            statusText: "Gateway Timeout",
                        })
                    );
                } catch {
                    return (
                        cached ||
                        new Response("", {
                            status: 504,
                            statusText: "Gateway Timeout",
                        })
                    );
                }
            })(),
        );
    } else {
        // Network-first for dynamic app pages.
        // NOTE: must always resolve to a Response object, never null/undefined,
        // and must never reject (cache lookups are guarded too).
        event.respondWith(
            (async () => {
                const offlineResponse = () =>
                    new Response("Offline", {
                        status: 503,
                        headers: {
                            "Content-Type": "text/html; charset=utf-8",
                        },
                    });

                try {
                    return await fetch(request);
                } catch {
                    // Network failed: fall back to cache, then offline page.
                }

                try {
                    const cached = await caches.match(request);

                    if (cached) {
                        return cached;
                    }

                    const offline = await caches.match(OFFLINE_URL);

                    return offline || offlineResponse();
                } catch (cacheError) {
                    console.warn(
                        `[sw ${SW_VERSION}] Cache lookup failed:`,
                        cacheError,
                    );

                    return offlineResponse();
                }
            })(),
        );
    }
});

// ---- Notification click: navigate to a safe internal record ----------------
self.addEventListener("notificationclick", (event) => {
    const notificationData = event.notification.data ?? {};

    // Firebase registers its own click handler for automatic notifications.
    // Let it handle those whenever the SDK is active. A cold worker still
    // needs this fallback because the SDK has not been initialized by a page.
    if (notificationData.FCM_MSG && firebaseMessaging) {
        return;
    }

    event.stopImmediatePropagation?.();
    event.notification.close();

    const targetUrl = safeNotificationTarget(
        notificationData.url ?? notificationData.FCM_MSG?.fcmOptions?.link,
    );
    const absoluteTargetUrl = new URL(targetUrl, self.location.origin).href;

    event.waitUntil(
        clients
            .matchAll({ type: "window", includeUncontrolled: true })
            .then(async (clientList) => {
                for (const client of clientList) {
                    try {
                        if ("navigate" in client) {
                            const navigated = await client.navigate(absoluteTargetUrl);
                            if (navigated?.focus) {
                                return navigated.focus();
                            }
                        }

                        if ("focus" in client) {
                            return client.focus();
                        }
                    } catch (error) {
                        console.warn("[sw] Could not focus an existing app window:", error);
                    }
                }

                if (clients.openWindow) {
                    return clients.openWindow(absoluteTargetUrl);
                }

                return undefined;
            }),
    );
});

// ---- Raw push fallback -------------------------------------------------------
self.addEventListener("push", (event) => {
    if (!event.data || firebaseMessaging) return;

    try {
        const data = event.data.json();
        event.waitUntil(showNotificationFromPayload(data));
    } catch (error) {
        console.error("[sw.js] Push parse error:", error);
    }
});
