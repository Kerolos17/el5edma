// Shared Firebase Cloud Messaging registration used by every bundle
// (admin/app.js, web-app via app import, servant). Single source of truth:
// permission is NEVER requested automatically — sync happens silently only
// when permission was already granted, otherwise the user must tap an
// explicit [data-push-enable] control (see updatePushPermissionButtons).
import { initializeApp } from 'firebase/app';
import { deleteToken, getMessaging, getToken, isSupported, onMessage } from 'firebase/messaging';

const ROOT_SW_URL = '/sw-v10.js';
const SENT_TOKEN_KEY = 'ministry-fcm-token-sent';
const SENT_TOKEN_USER_KEY = 'ministry-fcm-token-user';
const PUSH_DISABLED_KEY = 'ministry-push-disabled';

let booted = false;
// Set when the browser push service itself is unreachable (e.g. the
// network blocks Google's push endpoints) — surfaces as a distinct
// button state instead of a silent failure.
let pushUnavailable = false;

// ── Global resume dispatcher (installed exactly once per page load) ──────
// Installed PWAs resume from the background with stale state. Components
// listen for `app-resumed` on the window (@window.app-resumed) and refresh
// themselves. One debounced dispatcher here instead of per-component
// document/window listeners — those leak and multiply across wire:navigate.
if (typeof window !== 'undefined' && !window.__ministryResumeDispatch) {
    window.__ministryResumeDispatch = true;
    let resumeTimer = null;
    const dispatchResume = () => {
        clearTimeout(resumeTimer);
        resumeTimer = setTimeout(
            () => window.dispatchEvent(new CustomEvent('app-resumed')),
            400,
        );
    };
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) dispatchResume();
    });
    window.addEventListener('focus', dispatchResume);
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) dispatchResume();
    });
}

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
            // Browser permission alone does not mean the device is registered.
            stateLabel = button.dataset.pushDefaultLabel;
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
        const userId = currentUserId();
        return !!userId && localStorage.getItem(SENT_TOKEN_USER_KEY) === userId && !!localStorage.getItem(SENT_TOKEN_KEY);
    } catch {
        return false;
    }
};

const clearSentToken = () => {
    try {
        localStorage.removeItem(SENT_TOKEN_KEY);
        localStorage.removeItem(SENT_TOKEN_USER_KEY);
    } catch {
        // Private mode.
    }
};

const currentUserId = () => String(
    document.querySelector('[data-user-id]')?.getAttribute('data-user-id')
    ?? window.Laravel?.user?.id
    ?? '',
);

const sendConfigToSW = async (registration) => {
    registration.active.postMessage({ type: 'FIREBASE_CONFIG', config: firebaseConfig });
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

    const registration = await navigator.serviceWorker.register(ROOT_SW_URL, { updateViaCache: 'none' });

    // navigator.serviceWorker.ready can still refer to the old /sw.js worker
    // while the replacement is installing. FCM must receive the active v9
    // registration or it can bind the token to the legacy kill switch.
    if (!registration.active?.scriptURL?.endsWith(ROOT_SW_URL)) {
        await new Promise((resolve, reject) => {
            const interval = setInterval(() => {
                if (registration.active?.scriptURL?.endsWith(ROOT_SW_URL)) {
                    clearInterval(interval);
                    clearTimeout(timeout);
                    resolve();
                }
            }, 100);
            const timeout = setTimeout(() => {
                clearInterval(interval);
                reject(new Error('Current push service worker did not activate'));
            }, 15_000);
        });
    }

    return registration;
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
        const userId = currentUserId();

        if (tokenSendCoolingDown()) {
            console.warn(`[${logTag}] Token send skipped: still cooling down after a 429.`);

            try {
                localStorage.removeItem(SENT_TOKEN_KEY);
                localStorage.removeItem(SENT_TOKEN_USER_KEY);
            } catch {
                // Private mode.
            }

            return false;
        }

        await window.axios.post('/fcm-token', {
            fcm_token:    token,
            platform:     'web',
            device_label: deviceLabel(),
        });

        try {
            localStorage.setItem(SENT_TOKEN_KEY, token);
            localStorage.setItem(SENT_TOKEN_USER_KEY, userId);
            localStorage.removeItem(TOKEN_COOLDOWN_KEY);
        } catch {
            // Private mode: skip persistence.
        }

        return true;
    } catch (error) {
        console.error(`[${logTag}] Error saving token to server:`, error);

        try {
            localStorage.removeItem(SENT_TOKEN_KEY);
            localStorage.removeItem(SENT_TOKEN_USER_KEY);
        } catch {
            // Private mode.
        }

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
            if (!requestPermission && isPushDisabledByUser()) {
                updatePushPermissionButtons();
                return false;
            }

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
            await sendConfigToSW(swRegistration);

            if (hasSentToken()) {
                try {
                    const status = await window.axios.get('/fcm-token/status');
                    if (status.data?.registered === false) {
                        // The server may have removed an expired FCM token.
                        // Discard Firebase's cached token so getToken creates
                        // a fresh subscription for this installation.
                        clearSentToken();
                        await deleteToken(messaging);
                    }
                } catch (error) {
                    // A status check failure must not stop an otherwise valid
                    // registration attempt. The POST below remains authoritative.
                    console.warn(`[${logTag}] Could not verify push registration:`, error);
                }
            }

            const currentToken = await getToken(messaging, {
                vapidKey:                  import.meta.env.VITE_FIREBASE_VAPID_KEY,
                serviceWorkerRegistration: swRegistration,
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

            const saved = await sendTokenToServer(currentToken, logTag);

            if (!saved) {
                updatePushPermissionButtons();
                return false;
            }

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
            clearSentToken();

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

        if (token) {
            try {
                await window.axios.delete('/fcm-token', { data: { fcm_token: token } });
            } catch (error) {
                console.warn('[push] server token removal failed:', error);
                const message = document.querySelector('[data-push-enable]')?.dataset.pushUnavailableToast;
                if (message) {
                    dispatchLivewire('toast', { message, type: 'warning' });
                }
                return false;
            }
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

        try {
            localStorage.removeItem(SENT_TOKEN_KEY);
            localStorage.removeItem(SENT_TOKEN_USER_KEY);
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

        if ('Notification' in window && Notification.permission === 'granted' && !isPushDisabledByUser()) {
            // Already-granted permission can be synchronized silently.
            syncPushToken({ requestPermission: false });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncExistingPermission, { once: true });
    } else {
        syncExistingPermission();
    }
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
