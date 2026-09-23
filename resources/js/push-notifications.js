// Shared Firebase Cloud Messaging registration used by every bundle
// (admin/app.js, web-app via app import, servant). Single source of truth:
// permission is NEVER requested automatically — sync happens silently only
// when permission was already granted, otherwise the user must tap an
// explicit [data-push-enable] control (see updatePushPermissionButtons).
import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, isSupported, onMessage } from 'firebase/messaging';

const ROOT_SW_URL = '/sw.js';
const SENT_TOKEN_KEY = 'ministry-fcm-token-sent';

let booted = false;

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
        const stateLabel =
            permission === 'granted'
                ? button.dataset.pushEnabledLabel
                : permission === 'denied'
                  ? button.dataset.pushDeniedLabel
                  : permission === 'unsupported'
                    ? button.dataset.pushUnsupportedLabel
                    : button.dataset.pushDefaultLabel;

        if (label && stateLabel) {
            label.textContent = stateLabel;
        }

        const disabled = permission !== 'default';
        button.disabled = disabled;
        button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
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

        await window.axios.post('/fcm-token', {
            fcm_token:    token,
            platform:     'web',
            device_label: deviceLabel(),
        });

        try {
            localStorage.setItem(SENT_TOKEN_KEY, token);
        } catch {
            // Private mode: skip persistence.
        }

        return true;
    } catch (error) {
        console.error(`[${logTag}] Error saving token to server:`, error);

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

                return false;
            }

            await sendTokenToServer(currentToken, logTag);
            updatePushPermissionButtons();

            return true;
        } catch (error) {
            console.error(`[${logTag}] Error retrieving FCM token:`, error);
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

        syncPushToken({ requestPermission: true });
    });

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
        sync:       () => syncPushToken({ requestPermission: false }),
        permission: () => ('Notification' in window ? Notification.permission : 'unsupported'),
    };

    return window.MinistryPushNotifications;
}
