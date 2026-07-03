// Servant Panel — JavaScript entry point
// Alpine.js is provided by Livewire 3 automatically
import './bootstrap';
import './notifications';
import { offlineQueue } from './offline-queue';
import '@phosphor-icons/web/regular';
import '@phosphor-icons/web/bold';
import '@phosphor-icons/web/fill';
import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage } from 'firebase/messaging';

// ─── Reveal animations ────────────────────────────────────────────────────────

document.addEventListener('livewire:navigated', () => {
    triggerRevealAnimations();
});

document.addEventListener('DOMContentLoaded', () => {
    triggerRevealAnimations();
    setupFcm();
    setupEchoListener();
    offlineQueue.init();
    // Expose للـ wizard في كلا التخطيطين
    window.__servantOfflineQueue = offlineQueue;
});

function triggerRevealAnimations() {
    document.querySelectorAll('.reveal-card').forEach((el, i) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.animation = 'none';
        void el.offsetWidth;
        el.style.animation = `revealUp 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) ${i * 0.06}s forwards`;
    });
}

// ─── Firebase / FCM ───────────────────────────────────────────────────────────

const ROOT_SW_URL = '/sw.js';

const firebaseConfig = {
    apiKey:            import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain:        import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId:         import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket:     import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId:             import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId:     import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
};

const isFirebaseReady = Object.values(firebaseConfig).every(
    (v) => typeof v === 'string' && v.length > 0 && !v.includes('YOUR_'),
);

async function setupFcm() {
    if (!isFirebaseReady) return;
    if (!('Notification' in window) || !('serviceWorker' in navigator)) return;

    try {
        const app       = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        onMessage(messaging, (payload) => {
            dispatchToLivewire('fcmMessageReceived', payload);
        });

        navigator.serviceWorker.addEventListener('message', (event) => {
            const data = event?.data;
            if (data?.type === 'FCM_BACKGROUND_MESSAGE') {
                dispatchToLivewire('fcmMessageReceived', data.payload);
            }
        });

        if (Notification.permission === 'default') {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') return;
        }

        if (Notification.permission !== 'granted') return;

        const swReg = await navigator.serviceWorker.register(ROOT_SW_URL);
        await navigator.serviceWorker.ready;

        const sw = swReg.active ?? swReg.waiting ?? swReg.installing;
        if (sw) {
            sw.postMessage({ type: 'FIREBASE_CONFIG', config: firebaseConfig });
        }

        const token = await getToken(messaging, {
            vapidKey:                  import.meta.env.VITE_FIREBASE_VAPID_KEY,
            serviceWorkerRegistration: await navigator.serviceWorker.ready,
        });

        if (token) {
            await window.axios.post('/fcm-token', { fcm_token: token });
        }
    } catch (e) {
        // FCM not configured — silently ignore
    }
}

// ─── Real-time (Echo / Pusher) ────────────────────────────────────────────────

function setupEchoListener() {
    try {
        const userEl = document.querySelector('[data-user-id]');
        const userId = userEl?.getAttribute('data-user-id') ?? window.Laravel?.user?.id;

        if (!userId || !window.Echo) return;

        const handler = () => dispatchToLivewire('notificationCreated', {});

        const ch = window.Echo.private(`user.${userId}`);
        ch.listen('NewMinistryNotification', handler);
        ch.listen('.App\\Events\\NewMinistryNotification', handler);
    } catch (e) {
        // Echo not configured — polling fallback is active
        }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function dispatchToLivewire(event, payload) {
    try {
        if (window.Livewire) {
            window.Livewire.dispatch(event, payload);
        }
    } catch (e) {
        // Livewire not ready yet
    }
}
