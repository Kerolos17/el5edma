import './bootstrap';

import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

const ROOT_SERVICE_WORKER_URL = '/sw.js';

const firebaseConfig = {
    apiKey:            import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain:        import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId:         import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket:     import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId:             import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId:     import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
};

const isFirebaseConfigReady = Object.values(firebaseConfig).every((value) =>
    typeof value === 'string' && value.length > 0 && !value.includes('YOUR_'),
);

const sendConfigToSW = async (registration) => {
    const serviceWorker = registration.active ?? registration.waiting ?? registration.installing;

    if (!serviceWorker) {
        return;
    }

    serviceWorker.postMessage({
        type: 'FIREBASE_CONFIG',
        config: firebaseConfig,
    });
};

const cleanupLegacyFirebaseWorker = async () => {
    const registrations = await navigator.serviceWorker.getRegistrations();

    await Promise.all(
        registrations
            .filter((registration) => registration.active?.scriptURL?.endsWith('/firebase-messaging-sw.js'))
            .map((registration) => registration.unregister()),
    );
};

const getRootServiceWorkerRegistration = async () => {
    const existingRegistration = await navigator.serviceWorker.getRegistration('/');

    if (existingRegistration?.active?.scriptURL?.endsWith(ROOT_SERVICE_WORKER_URL)) {
        return existingRegistration;
    }

    return navigator.serviceWorker.register(ROOT_SERVICE_WORKER_URL);
};

// التحقق من وجود الـ config قبل التهيئة
if (!isFirebaseConfigReady) {
    console.warn('[app.js] Firebase config is missing or contains placeholder values. Push Notifications will not work.');
} else {
    try {
        const app       = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        // Request Permission and Generate Token
        const requestPermissionAndGetToken = async () => {
            try {
                if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                    return;
                }

                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    await cleanupLegacyFirebaseWorker();

                    const swRegistration = await getRootServiceWorkerRegistration();
                    const readyRegistration = await navigator.serviceWorker.ready;

                    await sendConfigToSW(swRegistration.active ? swRegistration : readyRegistration);

                    const currentToken = await getToken(messaging, {
                        vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
                        serviceWorkerRegistration: readyRegistration,
                    });

                    if (currentToken) {
                        await sendTokenToServer(currentToken);
                    } else {
                        console.warn('[app.js] No FCM token received. Check VAPID key and permissions.');
                    }
                } else {
                    console.log('[app.js] Notification permission denied.');
                }
            } catch (error) {
                console.error('[app.js] Error retrieving FCM token:', error);
            }
        };

        const sendTokenToServer = async (token) => {
            try {
                await axios.post('/fcm-token', { fcm_token: token });
                console.log('[app.js] FCM Token saved on server successfully.');
            } catch (error) {
                console.error('[app.js] Error saving token to server:', error);
            }
        };

        // عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (!("Notification" in window) || !("serviceWorker" in navigator)) {
                    return;
                }

                if (Notification.permission === 'default') {
                    requestPermissionAndGetToken();
                } else if (Notification.permission === 'granted') {
                    requestPermissionAndGetToken();
                }
            }, 3000);
        });

        // استقبال الإشعارات عند فتح التطبيق (Foreground)
        onMessage(messaging, (payload) => {
            console.log('[app.js] Foreground message received:', payload);
            document.dispatchEvent(new CustomEvent('fcm-message-received', { detail: payload }));
        });

    } catch (error) {
        console.error('[app.js] Firebase initialization error:', error);
    }
}
