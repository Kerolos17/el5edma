import "./bootstrap";

import { initPushNotifications } from "./push-notifications";

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

// Admin/web-app foreground behavior: audible tone + conditional OS-level
// notification + DOM event + Livewire refresh. Runs inside the shared
// push module via callback (see push-notifications.js).
const handleAppForegroundMessage = async (payload) => {
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
};

// Background FCM is already rendered by the service worker/OS. Here we
// only refresh application state; do not play a second notification tone.
const handleAppBackgroundMessage = (payload) => {
    try {
        if (
            window.Livewire &&
            typeof window.Livewire.dispatch === "function"
        ) {
            window.Livewire.dispatch("fcmMessageReceived", {
                payload,
            });
        }
    } catch (error) {
        console.warn("[app.js] Error handling serviceWorker message:", error);
    }
};

initPushNotifications({
    logTag: "app.js",
    onForegroundMessage: handleAppForegroundMessage,
    onBackgroundMessage: handleAppBackgroundMessage,
});

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
