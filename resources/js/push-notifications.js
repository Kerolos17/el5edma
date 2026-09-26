// Shared Firebase Cloud Messaging registration used by every bundle
// (admin/app.js, web-app via app import, servant). Single source of truth:
// permission is NEVER requested automatically — sync happens silently only
// when permission was already granted, otherwise the user must tap an
// explicit [data-push-enable] control (see updatePushPermissionButtons).
import { initializeApp } from 'firebase/app';
import { deleteToken, getMessaging, getToken, isSupported, onMessage } from 'firebase/messaging';

const ROOT_SW_URL = '/sw-v9.js';
const SENT_TOKEN_KEY = 'ministry-fcm-token-sent';
const PUSH_DISABLED_KEY = 'ministry-push-disabled';

let booted = false;
// Set when the browser push service itself is unreachable (e.g. the
// network blocks Google's push endpoints) — surfaces as a distinct
// button state instead of a silent failure.
let pushUnavailable = false;

const firebaseConfig = {
    apiKey:            import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain:        import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId:         import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket:     import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId:             import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId:     import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
};

const isFirebaseConfigReady = Object.values(firebaseConfig).every(
    (value) => typeof value === 'string' && value.length > 0 && !value.includes('YOUR_'),
);

const dispatchLivewire = (name, detail) => {
    try {
        if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
            window.Livewire.dispatch(name, detail);
        }
    } catch {
        // Livewire dispatch failed
    }
};

const deviceLabel = () => {
    try {
        const brands = navigator.userAgentData?.brands
            ?.map((entry) => entry.brand)
            .filter(Boolean);

        if (brands?.length) {
            return brands.join(', ').slice(0, 120);
        }
    } catch {
        // Fall through to platform fallback.
    }

    try {
        return (navigator.platform || 'web').slice(0, 120);
    } catch {
        return 'web';
    }
};

const updatePushPermissionButtons = () => {
    const supported = 'Notification' in window && 'serviceWorker' in navigator;
    const permission = supported ? Notification.permission : 'unsupported';

    document.querySelectorAll('[data-push-enable]').forEach((button) => {
        const label = button.querySelector('[data-push-label]');
        let stateLabel;
        let disabled = false;
        let state = permission;

        if (permission === 'unsupported') {
            stateLabel = button.dataset.pushUnsupportedLabel;
            disabled = true;
        } else if (permission === 'denied') {
            stateLabel = button.dataset.pushDeniedLabel;
            disabled = true;
        } else if (pushUnavailable) {
            // Keep clickable so the user can retry after switching networks.
            stateLabel = button.dataset.pushUnavailableLabel;
            state = 'unavailable';
        } else if (isPushDisabledByUser()) {
            // User turned notifications off — offer to re-enable.
            stateLabel = button.dataset.pushDefaultLabel;
            state = 'disabled';
        } else if (hasSentToken()) {
            stateLabel = button.dataset.pushDisableLabel;
            state = 'active';
        } else if (permission === 'granted') {
            stateLabel = button.dataset.pushEnabledLabel;
        } else {
            stateLabel = button.dataset.pushDefaultLabel;
        }

        if (label && stateLabel) {
            label.textContent = stateLabel;
        }

        button.disabled = disabled;
        button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        button.dataset.pushState = state;
    });
};

const isPushDisabledByUser = () => {
    try {
        return localStorage.getItem(PUSH_DISABLED_KEY) === '1';
    } catch {
        return false;
    }
};

const hasSentToken = () => {
    try {
        return !!localStorage.getItem(SENT_TOKEN_KEY);
    } catch {
        return false;
    }
};

const sendConfigToSW = async (registration) => {
    const serviceWorker =
        registration.active ?? registration.waiting ?? registration.installing;

    if (!serviceWorker) {
        return;
    }

    try {
        serviceWorker.postMessage({ type: 'FIREBASE_CONFIG', config: firebaseConfig });
    } catch (error) {
        console.warn('[push] Failed to postMessage FIREBASE_CONFIG to SW:', error);
    }
};

const cleanupLegacyFirebaseWorker = async () => {
    const registrations = await navigator.serviceWorker.getRegistrations();

    await Promise.all(
        registrations
            .filter((registration) =>
                registration.active?.scriptURL?.endsWith('/firebase-messaging-sw.js'),
            )
            .map((registration) => registration.unregister()),
    );
};

const getRootServiceWorkerRegistration = async () => {
    const existingRegistration = await navigator.serviceWorker.getRegistration('/');

    if (existingRegistration?.active?.scriptURL?.endsWith(ROOT_SW_URL)) {
        return existingRegistration;
    }

    return navigator.serviceWorker.register(ROOT_SW_URL);
};

const TOKEN_COOLDOWN_KEY = 'ministry-fcm-token-cooldown';

const tokenSendCoolingDown = () => {
    try {
        const until = Number(localStorage.getItem(TOKEN_COOLDOWN_KEY) || 0);
        return Date.now() < until;
    } catch {
        return false;
    }
};

const startTokenSendCooldown = () => {
    try {
        // After a 429, wait out the per-minute throttle window.
        localStorage.setItem(TOKEN_COOLDOWN_KEY, String(Date.now() + 65_000));
    } catch {
        // Private mode.
    }
};

const sendTokenToServer = async (token, logTag) => {
    try {
        let previous = null;

        try {
            previous = localStorage.getItem(SENT_TOKEN_KEY);
        } catch {
            // Private mode: always re-send.
        }

        if (previous === token) {
            return true;
        }

        if (tokenSendCoolingDown()) {
            console.warn(`[${logTag}] Token send skipped: still cooling down after a 429.`);

            return false;
        }

        await window.axios.post('/fcm-token', {
            fcm_token:    token,
            platform:     'web',
            device_label: deviceLabel(),
        });

        try {
            localStorage.setItem(SENT_TOKEN_KEY, token);
            localStorage.removeItem(TOKEN_COOLDOWN_KEY);
        } catch {
            // Private mode: skip persistence.
        }

        return true;
    } catch (error) {
        console.error(`[${logTag}] Error saving token to server:`, error);

        if (String(error?.response?.status || '') === '429') {
            startTokenSendCooldown();
        }

        return false;
    }
};

// Default foreground handler (servant behavior): hand the payload to
// Livewire and let the bell/polling UI react. Bundles with richer UX pass
// their own onForegroundMessage instead.
const defaultForegroundHandler = (payload) => {
    dispatchLivewire('fcmMessageReceived', payload);
};

// Default background-bridge handler (servant shape: raw payload).
const defaultBackgroundHandler = (payload) => {
    dispatchLivewire('fcmMessageReceived', payload);
};

/**
 * Boot Firebase push registration. Safe to call from every bundle — only
 * the first call boots; later calls just refresh button states.
 *
 * @param {object} options
 * @param {string} options.logTagTAG for console messages (bundle name).
 * @param {(payload: object) => void} [options.onForegroundMessage]
 * @param {(payload: object) => void} [options.onBackgroundMessage]
 */
export function initPushNotifications({ logTag = 'push', onForegroundMessage = null, onBackgroundMessage = null } = {}) {
    const foregroundHandler = onForegroundMessage ?? defaultForegroundHandler;
    const backgroundHandler = onBackgroundMessage ?? defaultBackgroundHandler;

    const syncPushToken = async ({ requestPermission = false } = {}) => {
        try {
            if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                updatePushPermissionButtons();

                return false;
            }

            let permission = Notification.permission;

            // Permission prompts must originate from an explicit user action.
            if (permission === 'default' && requestPermission) {
                permission = await Notification.requestPermission();
            }

            updatePushPermissionButtons();

            if (permission !== 'granted') {
                return false;
            }

            await cleanupLegacyFirebaseWorker();

            const swRegistration = await getRootServiceWorkerRegistration();
            const readyRegistration = await navigator.serviceWorker.ready;

            await sendConfigToSW(swRegistration.active ? swRegistration : readyRegistration);

            const currentToken = await getToken(messaging, {
                vapidKey:                  import.meta.env.VITE_FIREBASE_VAPID_KEY,
                serviceWorkerRegistration: readyRegistration,
            });

            if (!currentToken) {
                console.warn(`[${logTag}] No FCM token received. Check VAPID key and permissions.`);

                // Firebase swallows the underlying failure and returns null.
                // With permission granted but no token, the browser push
                // service is unreachable (e.g. the network blocks Google's
                // push endpoints) — surface that honestly. Toast only on an
                // explicit user action; background syncs just update the
                // button state (a toast on every page load is noise).
                pushUnavailable = true;
                if (requestPermission) {
                    const trigger = document.querySelector('[data-push-enable]');
                    const msg = trigger?.dataset.pushUnavailableToast;
                    if (msg) {
                        dispatchLivewire('toast', { message: msg, type: 'warning' });
                    }
                }

                updatePushPermissionButtons();

                return false;
            }

            await sendTokenToServer(currentToken, logTag);

            try {
                localStorage.removeItem(PUSH_DISABLED_KEY);
            } catch {
                // Private mode.
            }
            pushUnavailable = false;

            updatePushPermissionButtons();

            return true;
        } catch (error) {
            console.error(`[${logTag}] Error retrieving FCM token:`, error);

            if (String(error?.message || error).includes('push service not available')) {
                pushUnavailable = true;
                const trigger = document.querySelector('[data-push-enable]');
                const msg = trigger?.dataset.pushUnavailableToast;
                if (msg) {
                    dispatchLivewire('toast', { message: msg, type: 'warning' });
                }
            }

            updatePushPermissionButtons();

            return false;
        }
    };

    // Permission prompts must originate from an explicit user action.
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-push-enable]');

        if (!trigger || trigger.disabled) {
            return;
        }

        if (trigger.dataset.pushState === 'active') {
            disablePush();
            return;
        }

        syncPushToken({ requestPermission: true });
    });

    const disablePush = async () => {
        let token = null;

        try {
            token = localStorage.getItem(SENT_TOKEN_KEY);
        } catch {
            // Private mode.
        }

        try {
            if (messaging) {
                await deleteToken(messaging);
            }
        } catch (error) {
            console.warn('[push] deleteToken failed:', error);
        }

        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) {
                await sub.unsubscribe();
            }
        } catch (error) {
            console.warn('[push] unsubscribe failed:', error);
        }

        if (token) {
            try {
                await window.axios.delete('/fcm-token', { data: { fcm_token: token } });
            } catch (error) {
                console.warn('[push] server token removal failed:', error);
            }
        }

        try {
            localStorage.removeItem(SENT_TOKEN_KEY);
            localStorage.setItem(PUSH_DISABLED_KEY, '1');
        } catch {
            // Private mode.
        }

        updatePushPermissionButtons();

        const trigger = document.querySelector('[data-push-enable]');
        const msg = trigger?.dataset.pushDisabledToast;
        if (msg) {
            dispatchLivewire('toast', { message: msg, type: 'info' });
        }

        return true;
    };

    const syncExistingPermission = () => {
        updatePushPermissionButtons();

        if ('Notification' in window && Notification.permission === 'granted') {
            // Already-granted permission can be synchronized silently.
            syncPushToken({ requestPermission: false });
        }
    };

    document.addEventListener('DOMContentLoaded', syncExistingPermission, { once: true });
    document.addEventListener('livewire:navigated', updatePushPermissionButtons);

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            try {
                const data = event.data;

                if (!data || data.type !== 'FCM_BACKGROUND_MESSAGE') {
                    return;
                }

                backgroundHandler(data.payload);
            } catch (error) {
                console.warn(`[${logTag}] Error handling serviceWorker message:`, error);
            }
        });
    }

    const boot = async () => {
        if (!isFirebaseConfigReady) {
            console.warn(`[${logTag}] Firebase config is missing or contains placeholder values. Push Notifications will not work.`);
            updatePushPermissionButtons();

            return;
        }

        try {
            const app = initializeApp(firebaseConfig);
            const supported = await isSupported();

            if (!supported) {
                console.warn(`[${logTag}] Firebase Messaging is not supported in this browser. Push notifications disabled.`);
                updatePushPermissionButtons();

                return;
            }

            messaging = getMessaging(app);

            onMessage(messaging, async (payload) => {
                console.log(`[${logTag}] Foreground message received:`, payload);

                try {
                    await foregroundHandler(payload, { messaging });
                } catch (error) {
                    console.error(`[${logTag}] Foreground handler error:`, error);
                }
            });

            syncExistingPermission();
        } catch (error) {
            console.error(`[${logTag}] Firebase initialization error:`, error);
            updatePushPermissionButtons();
        }
    };

    let messaging = null;

    if (!booted) {
        booted = true;
        boot();
    } else {
        updatePushPermissionButtons();
    }

    window.MinistryPushNotifications = window.MinistryPushNotifications ?? {
        enable:     () => syncPushToken({ requestPermission: true }),
        disable:    () => disablePush(),
        sync:       () => syncPushToken({ requestPermission: false }),
        permission: () => ('Notification' in window ? Notification.permission : 'unsupported'),
    };

    return window.MinistryPushNotifications;
}
