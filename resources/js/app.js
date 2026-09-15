import "./bootstrap";

import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage, isSupported } from "firebase/messaging";

const ROOT_SERVICE_WORKER_URL = "/sw.js";
const MUTE_STORAGE_KEY = "ministry-notif-muted";

const isNotificationSoundMuted = () =>
    localStorage.getItem(MUTE_STORAGE_KEY) === "true";

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
            url: data.url || "/app/dashboard",
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

const updatePushPermissionButtons = () => {
    const supported =
        "Notification" in window && "serviceWorker" in navigator;
    const permission = supported ? Notification.permission : "unsupported";

    document.querySelectorAll("[data-push-enable]").forEach((button) => {
        const label = button.querySelector("[data-push-label]");
        const stateLabel =
            permission === "granted"
                ? button.dataset.pushEnabledLabel
                : permission === "denied"
                  ? button.dataset.pushDeniedLabel
                  : permission === "unsupported"
                    ? button.dataset.pushUnsupportedLabel
                    : button.dataset.pushDefaultLabel;

        if (label && stateLabel) {
            label.textContent = stateLabel;
        }

        const disabled = permission !== "default";
        button.disabled = disabled;
        button.setAttribute("aria-disabled", disabled ? "true" : "false");
        button.dataset.pushState = permission;
    });
};

const sendConfigToSW = async (registration) => {
    const serviceWorker =
        registration.active ?? registration.waiting ?? registration.installing;

    if (!serviceWorker) {
        return;
    }

    try {
        serviceWorker.postMessage({
            type: "FIREBASE_CONFIG",
            config: firebaseConfig,
        });
    } catch (error) {
        console.warn(
            "[app.js] Failed to postMessage FIREBASE_CONFIG to SW:",
            error,
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

const sendTokenToServer = async (token) => {
    try {
        await axios.post("/fcm-token", {
            fcm_token: token,
            platform: "web",
        });
    } catch (error) {
        console.error("[app.js] Error saving token to server:", error);
    }
};

(async () => {
    if (!isFirebaseConfigReady) {
        console.warn(
            "[app.js] Firebase config is missing or contains placeholder values. Push Notifications will not work.",
        );
        updatePushPermissionButtons();
        return;
    }

    try {
        const app = initializeApp(firebaseConfig);
        const supported = await isSupported();

        if (!supported) {
            console.warn(
                "[app.js] Firebase Messaging is not supported in this browser. Push notifications disabled.",
            );
            updatePushPermissionButtons();
            return;
        }

        const messaging = getMessaging(app);

        const syncPushToken = async ({ requestPermission = false } = {}) => {
            try {
                if (
                    !("Notification" in window) ||
                    !("serviceWorker" in navigator)
                ) {
                    updatePushPermissionButtons();
                    return false;
                }

                let permission = Notification.permission;

                if (permission === "default" && requestPermission) {
                    permission = await Notification.requestPermission();
                }

                updatePushPermissionButtons();

                if (permission !== "granted") {
                    return false;
                }

                await cleanupLegacyFirebaseWorker();

                const swRegistration =
                    await getRootServiceWorkerRegistration();
                const readyRegistration = await navigator.serviceWorker.ready;

                await sendConfigToSW(
                    swRegistration.active ? swRegistration : readyRegistration,
                );

                const currentToken = await getToken(messaging, {
                    vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
                    serviceWorkerRegistration: readyRegistration,
                });

                if (!currentToken) {
                    console.warn(
                        "[app.js] No FCM token received. Check VAPID key and permissions.",
                    );
                    return false;
                }

                await sendTokenToServer(currentToken);
                updatePushPermissionButtons();

                return true;
            } catch (error) {
                console.error("[app.js] Error retrieving FCM token:", error);
                updatePushPermissionButtons();
                return false;
            }
        };

        // Permission prompts must originate from an explicit user action.
        document.addEventListener("click", (event) => {
            const trigger = event.target.closest("[data-push-enable]");

            if (!trigger || trigger.disabled) {
                return;
            }

            syncPushToken({ requestPermission: true });
        });

        const syncExistingPermission = () => {
            updatePushPermissionButtons();

            if (
                "Notification" in window &&
                Notification.permission === "granted"
            ) {
                // Already-granted permission can be synchronized silently.
                syncPushToken({ requestPermission: false });
            }
        };

        document.addEventListener("DOMContentLoaded", syncExistingPermission, {
            once: true,
        });
        document.addEventListener("livewire:navigated", updatePushPermissionButtons);

        window.MinistryPushNotifications = {
            enable: () => syncPushToken({ requestPermission: true }),
            sync: () => syncPushToken({ requestPermission: false }),
            permission: () =>
                "Notification" in window
                    ? Notification.permission
                    : "unsupported",
        };

        // Background FCM is already rendered by the service worker/OS. Here we
        // only refresh application state; do not play a second notification tone.
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.addEventListener("message", (event) => {
                try {
                    const data = event.data;
                    if (!data || data.type !== "FCM_BACKGROUND_MESSAGE") {
                        return;
                    }

                    if (
                        window.Livewire &&
                        typeof window.Livewire.dispatch === "function"
                    ) {
                        window.Livewire.dispatch("fcmMessageReceived", {
                            payload: data.payload,
                        });
                    }
                } catch (error) {
                    console.warn(
                        "[app.js] Error handling serviceWorker message:",
                        error,
                    );
                }
            });
        }

        // Foreground notifications are owned by the page.
        onMessage(messaging, async (payload) => {
            console.log("[app.js] Foreground message received:", payload);

            const soundMode = payload.data?.sound_mode || "soft";

            playForegroundNotificationTone(soundMode);

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
            } catch {
                // Livewire dispatch failed
            }
        });
    } catch (error) {
        console.error("[app.js] Firebase initialization error:", error);
        updatePushPermissionButtons();
    }
})();

// Subscribe to server broadcasts via Echo and forward to Livewire
document.addEventListener("DOMContentLoaded", () => {
    try {
        const userEl = document.querySelector("[data-user-id]");
        let userId = null;

        if (userEl) {
            userId = userEl.getAttribute("data-user-id");
        }

        if (!userId && window.Laravel && window.Laravel.user) {
            userId = window.Laravel.user.id;
        }

        if (!userId || !window.Echo) {
            return;
        }

        const handler = (event) => {
            try {
                if (
                    window.Livewire &&
                    typeof window.Livewire.dispatch === "function"
                ) {
                    window.Livewire.dispatch("notificationCreated", event);
                }
            } catch {
                // Livewire dispatch failed
            }
        };

        const channel = window.Echo.private(`user.${userId}`);
        channel.listen("NewMinistryNotification", handler);
        channel.listen(`.App\\Events\\NewMinistryNotification`, handler);
    } catch (error) {
        console.warn("[app.js] Echo subscription setup failed:", error);
    }
});
