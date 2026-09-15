const CACHE_NAME = "ministry-pwa-v5";
const OFFLINE_URL = "/offline.html";
const FIREBASE_VERSION = "12.11.0";
const DEFAULT_NOTIFICATION_URL = "/app/dashboard";

let firebaseMessaging = null;

// Assets to precache on install
const PRECACHE_ASSETS = [
    OFFLINE_URL,
    "/manifest.json",
    "/icons/icon-192x192.png",
    "/icons/icon-512x512.png",
    "/icons/apple-touch-icon.png",
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
const SKIP_PATHS = [
    "/_pwa/ping",
    "/fcm-token",
    "/login",
    "/logout",
    "/language",
    "/login-code",
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

function ensureFirebaseMessaging(config) {
    if (!config) {
        return null;
    }

    if (firebaseMessaging) {
        return firebaseMessaging;
    }

    try {
        if (typeof firebase === "undefined") {
            importScripts(
                `https://www.gstatic.com/firebasejs/${FIREBASE_VERSION}/firebase-app-compat.js`,
            );
            importScripts(
                `https://www.gstatic.com/firebasejs/${FIREBASE_VERSION}/firebase-messaging-compat.js`,
            );
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
        ensureFirebaseMessaging(event.data.config);
    }
});

// ---- Install ----------------------------------------------------------------
self.addEventListener("install", (event) => {
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
        // Stale-while-revalidate: serve cache instantly, update in background
        event.respondWith(
            caches.open(CACHE_NAME).then(async (cache) => {
                const cached = await cache.match(request);
                const networkFetch = fetch(request)
                    .then((res) => {
                        if (res.ok) cache.put(request, res.clone());
                        return res;
                    })
                    .catch(() => null);
                return cached || networkFetch || new Response("", { status: 504, statusText: "Gateway Timeout" });
            }),
        );
    } else {
        // Network-first for dynamic app pages
        event.respondWith(
            fetch(request)
                .then((res) => res)
                .catch(() =>
                    caches.match(request).then(
                        (cached) =>
                            cached ||
                            caches.match(OFFLINE_URL).then(
                                (offline) =>
                                    offline ||
                                    new Response("Offline", {
                                        status: 503,
                                        headers: { "Content-Type": "text/html; charset=utf-8" },
                                    }),
                            ),
                    ),
                ),
        );
    }
});

// ---- Notification click: navigate to a safe internal record ----------------
self.addEventListener("notificationclick", (event) => {
    event.notification.close();

    const targetUrl = safeNotificationTarget(event.notification.data?.url);

    event.waitUntil(
        clients
            .matchAll({ type: "window", includeUncontrolled: true })
            .then(async (clientList) => {
                for (const client of clientList) {
                    if (!("focus" in client)) {
                        continue;
                    }

                    if ("navigate" in client) {
                        await client.navigate(targetUrl);
                    }

                    return client.focus();
                }

                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
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
