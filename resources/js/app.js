import "./bootstrap";

import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage, isSupported } from "firebase/messaging";

const ROOT_SERVICE_WORKER_URL = "/sw.js";

const isNotificationSoundMuted = () =>
    localStorage.getItem("notification-sound-muted") === "true";

const parseBooleanish = (value, fallback = false) => {
    if (typeof value === "boolean") {
        return value;
    }

    if (typeof value === "string") {
        return value === "true";
    }

    return fallback;
};

const parseVibrationPattern = (value) => {
    if (Array.isArray(value)) {
        return value.map(Number).filter(Number.isFinite);
    }

    if (typeof value !== "string" || value.length === 0) {
        return [];
    }

    try {
        const parsed = JSON.parse(value);

        return Array.isArray(parsed)
            ? parsed.map(Number).filter(Number.isFinite)
            : [];
    } catch {
        return [];
    }
};

const playForegroundNotificationTone = (mode = "soft") => {
    if (isNotificationSoundMuted()) {
        return;
    }

    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;

        if (!AudioCtx) {
            return;
        }

        const ctx = new AudioCtx();
        const patterns = {
            soft: [
                { at: 0, frequency: 720, duration: 0.12, gain: 0.16 },
                { at: 0.18, frequency: 660, duration: 0.16, gain: 0.14 },
            ],
            alert: [
                { at: 0, frequency: 990, duration: 0.16, gain: 0.28 },
                { at: 0.2, frequency: 880, duration: 0.18, gain: 0.24 },
                { at: 0.44, frequency: 990, duration: 0.22, gain: 0.26 },
            ],
            alarm: [
                { at: 0, frequency: 1140, duration: 0.22, gain: 0.36 },
                { at: 0.26, frequency: 820, duration: 0.22, gain: 0.34 },
                { at: 0.56, frequency: 1140, duration: 0.28, gain: 0.38 },
                { at: 0.92, frequency: 820, duration: 0.34, gain: 0.34 },
            ],
        };

        for (const note of patterns[mode] ?? patterns.soft) {
            const oscillator = ctx.createOscillator();
            const gainNode = ctx.createGain();

            oscillator.type = "square";
            oscillator.frequency.setValueAtTime(
                note.frequency,
                ctx.currentTime + note.at,
            );
            gainNode.gain.setValueAtTime(0.0001, ctx.currentTime + note.at);
            gainNode.gain.exponentialRampToValueAtTime(
                note.gain,
                ctx.currentTime + note.at + 0.02,
            );
            gainNode.gain.exponentialRampToValueAtTime(
                0.0001,
                ctx.currentTime + note.at + note.duration,
            );

            oscillator.connect(gainNode);
            gainNode.connect(ctx.destination);
            oscillator.start(ctx.currentTime + note.at);
            oscillator.stop(ctx.currentTime + note.at + note.duration + 0.02);
        }

        setTimeout(() => ctx.close().catch(() => {}), 2000);
    } catch (error) {
        console.warn(
            "[app.js] Unable to play foreground notification tone:",
            error,
        );
    }
};

const buildBrowserNotificationOptions = (payload) => {
    const data = payload.data ?? {};

    return {
        body: payload.notification?.body || "",
        icon: "/icons/icon-192x192.png",
        badge: "/icons/icon-72x72.png",
        tag: data.tag || "ministry-generic",
        renotify: parseBooleanish(data.renotify, false),
        requireInteraction: parseBooleanish(data.require_interaction, false),
        vibrate: parseVibrationPattern(data.vibrate),
        silent: false,
        data: {
            url: data.url || "/admin",
            ...data,
        },
    };
};

const showForegroundBrowserNotification = async (payload) => {
    if (
        !("serviceWorker" in navigator) ||
        Notification.permission !== "granted"
    ) {
        return;
    }

    const registration = await navigator.serviceWorker.ready;

    await registration.showNotification(
        payload.notification?.title || "إشعار جديد",
        buildBrowserNotificationOptions(payload),
    );
};

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
};

const isFirebaseConfigReady = Object.values(firebaseConfig).every(
    (value) =>
        typeof value === "string" &&
        value.length > 0 &&
        !value.includes("YOUR_"),
);

const sendConfigToSW = async (registration) => {
    const serviceWorker =
        registration.active ?? registration.waiting ?? registration.installing;

    if (!serviceWorker) {
        return;
    }

    try {
        console.log("[app.js] Posting FIREBASE_CONFIG to SW", {
            scriptURL: serviceWorker.scriptURL,
            scope: registration.scope,
        });

        serviceWorker.postMessage({
            type: "FIREBASE_CONFIG",
            config: firebaseConfig,
        });
    } catch (e) {
        console.warn(
            "[app.js] Failed to postMessage FIREBASE_CONFIG to SW:",
            e,
        );
    }
};

const cleanupLegacyFirebaseWorker = async () => {
    const registrations = await navigator.serviceWorker.getRegistrations();

    await Promise.all(
        registrations
            .filter((registration) =>
                registration.active?.scriptURL?.endsWith(
                    "/firebase-messaging-sw.js",
                ),
            )
            .map((registration) => registration.unregister()),
    );
};

const getRootServiceWorkerRegistration = async () => {
    const existingRegistration =
        await navigator.serviceWorker.getRegistration("/");

    if (
        existingRegistration?.active?.scriptURL?.endsWith(
            ROOT_SERVICE_WORKER_URL,
        )
    ) {
        return existingRegistration;
    }

    return navigator.serviceWorker.register(ROOT_SERVICE_WORKER_URL);
};

// التحقق من وجود الـ config قبل التهيئة
if (!isFirebaseConfigReady) {
    console.warn(
        "[app.js] Firebase config is missing or contains placeholder values. Push Notifications will not work.",
    );
} else {
    try {
        const app = initializeApp(firebaseConfig);
        const supported = await isSupported();

        if (!supported) {
            console.warn("[app.js] Firebase Messaging is not supported in this browser. Push notifications disabled.");
        } else {
            const messaging = getMessaging(app);

        // Request Permission and Generate Token
        const requestPermissionAndGetToken = async () => {
            try {
                if (
                    !("Notification" in window) ||
                    !("serviceWorker" in navigator)
                ) {
                    return;
                }

                const permission = await Notification.requestPermission();
                if (permission === "granted") {
                    await cleanupLegacyFirebaseWorker();

                    const swRegistration =
                        await getRootServiceWorkerRegistration();
                    const readyRegistration =
                        await navigator.serviceWorker.ready;

                    await sendConfigToSW(
                        swRegistration.active
                            ? swRegistration
                            : readyRegistration,
                    );

                    const currentToken = await getToken(messaging, {
                        vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
                        serviceWorkerRegistration: readyRegistration,
                    });

                    if (currentToken) {
                        await sendTokenToServer(currentToken);
                    } else {
                        console.warn(
                            "[app.js] No FCM token received. Check VAPID key and permissions.",
                        );
                    }
                } else {
                    console.log("[app.js] Notification permission denied.");
                }
            } catch (error) {
                console.error("[app.js] Error retrieving FCM token:", error);
            }
        };

        const sendTokenToServer = async (token) => {
            try {
                await axios.post("/fcm-token", { fcm_token: token });
                console.log("[app.js] FCM Token saved on server successfully.");
            } catch (error) {
                console.error("[app.js] Error saving token to server:", error);
            }
        };

        // عند تحميل الصفحة
        document.addEventListener("DOMContentLoaded", () => {
            setTimeout(() => {
                if (
                    !("Notification" in window) ||
                    !("serviceWorker" in navigator)
                ) {
                    return;
                }

                if (Notification.permission === "default") {
                    requestPermissionAndGetToken();
                } else if (Notification.permission === "granted") {
                    requestPermissionAndGetToken();
                }
            }, 3000);
        });

        // Listen for messages posted from the service worker (background FCM)
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.addEventListener("message", (event) => {
                try {
                    const data = event.data;
                    if (!data) return;

                    if (data.type === "FCM_BACKGROUND_MESSAGE") {
                        console.log(
                            "[app.js] Background message forwarded from SW:",
                            data.payload,
                        );

                        const soundMode =
                            data.payload?.data?.sound_mode || "soft";
                        playForegroundNotificationTone(soundMode);
                        document.dispatchEvent(
                            new CustomEvent("new-notification-sound", {
                                detail: soundMode,
                            }),
                        );

                        try {
                            if (
                                window.Livewire &&
                                typeof window.Livewire.dispatch === "function"
                            ) {
                                window.Livewire.dispatch(
                                    "fcmMessageReceived",
                                    { payload: data.payload },
                                );
                            }
                        } catch (e) {
                            // Livewire dispatch failed
                        }
                    }
                } catch (e) {
                    console.warn(
                        "[app.js] Error handling serviceWorker message:",
                        e,
                    );
                }
            });
        }

        // استقبال الإشعارات عند فتح التطبيق (Foreground)
        onMessage(messaging, async (payload) => {
            console.log("[app.js] Foreground message received:", payload);

            const soundMode = payload.data?.sound_mode || "soft";

            playForegroundNotificationTone(soundMode);
            document.dispatchEvent(
                new CustomEvent("new-notification-sound", {
                    detail: soundMode,
                }),
            );

            if (
                document.visibilityState !== "visible" ||
                parseBooleanish(payload.data?.require_interaction, false) ||
                payload.data?.severity === "critical"
            ) {
                await showForegroundBrowserNotification(payload);
            }

            document.dispatchEvent(
                new CustomEvent("fcm-message-received", { detail: payload }),
            );

            try {
                if (
                    window.Livewire &&
                    typeof window.Livewire.dispatch === "function"
                ) {
                    window.Livewire.dispatch("fcmMessageReceived", { payload });
                }
            } catch (e) {
                // Livewire dispatch failed
            }
        });
        }
    } catch (error) {
        console.error("[app.js] Firebase initialization error:", error);
    }
}

// Subscribe to server broadcasts via Echo and forward to Livewire
document.addEventListener("DOMContentLoaded", () => {
    try {
        // attempt to locate a DOM node rendered by the NotificationsBell Livewire view
        const userEl = document.querySelector("[data-user-id]");
        let userId = null;

        if (userEl) {
            userId = userEl.getAttribute("data-user-id");
        }

        // fallback: Laravel global object if present
        if (!userId && window.Laravel && window.Laravel.user) {
            userId = window.Laravel.user.id;
        }

        if (!userId) return;

        if (window.Echo) {
            const handler = (e) => {
                console.log("[app.js] Broadcast NewMinistryNotification:", e);
                try {
                    if (
                        window.Livewire &&
                        typeof window.Livewire.dispatch === "function"
                    ) {
                        window.Livewire.dispatch("notificationCreated", e);
                    }
                } catch (err) {
                    // Livewire dispatch failed
                }
            };

            const channel = window.Echo.private(`user.${userId}`);
            channel.listen("NewMinistryNotification", handler);
            channel.listen(`.App\\Events\\NewMinistryNotification`, handler);
        }
    } catch (e) {
        console.warn("[app.js] Echo subscription setup failed:", e);
    }
});
