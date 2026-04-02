import './bootstrap';

import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

const firebaseConfig = {
    apiKey:            import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain:        import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId:         import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket:     import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId:             import.meta.env.VITE_FIREBASE_APP_ID,
};

// التحقق من وجود الـ config قبل التهيئة
const configValues = Object.values(firebaseConfig);
if (configValues.some(v => !v || v.includes('YOUR_'))) {
    console.warn('[app.js] Firebase config is missing or contains placeholder values. Push Notifications will not work.');
} else {
    try {
        const app       = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        // إرسال الـ config للـ Service Worker عند تسجيله
        const sendConfigToSW = async (sw) => {
            sw.postMessage({
                type:   'FIREBASE_CONFIG',
                config: firebaseConfig,
            });
        };

        // Request Permission and Generate Token
        const requestPermissionAndGetToken = async () => {
            try {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    // تأكد من تسجيل الـ Service Worker أولاً
                    const swRegistration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
                    await navigator.serviceWorker.ready;

                    // إرسال الـ config للـ SW
                    if (swRegistration.active) {
                        sendConfigToSW(swRegistration.active);
                    }

                    const currentToken = await getToken(messaging, {
                        vapidKey:            import.meta.env.VITE_FIREBASE_VAPID_KEY,
                        serviceWorkerRegistration: swRegistration,
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
                if (Notification.permission === 'default') {
                    requestPermissionAndGetToken();
                } else if (Notification.permission === 'granted') {
                    requestPermissionAndGetToken(); // تجديد الـ token على السيرفر
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
