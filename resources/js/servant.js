// Servant Panel — JavaScript entry point
// Alpine.js is provided by Livewire 3 automatically
import './bootstrap';
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
    // Expose للـ Alpine.js في الـ wizard
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

// ─── Offline Visit Queue (IndexedDB) ──────────────────────────────────────────

export const offlineQueue = {
    DB_NAME:    'servant-offline',
    DB_VERSION: 1,
    STORE:      'pending-visits',
    db:         null,

    async init() {
        if (!('indexedDB' in window)) return;

        this.db = await this._openDb();

        // عند عودة الاتصال، حاول مزامنة الزيارات المنتظرة
        window.addEventListener('online', () => this._syncPending());

        // إذا كان الاتصال متاحاً عند البداية وعندنا زيارات معلقة
        if (navigator.onLine) {
            await this._syncPending();
        }

        // أبلغ الـ Livewire بعدد الزيارات المعلقة عند التحميل
        this._notifyCount();
    },

    async enqueue(visitData) {
        if (!this.db) return false;
        const tx    = this.db.transaction(this.STORE, 'readwrite');
        const store = tx.objectStore(this.STORE);
        store.add({ ...visitData, queuedAt: Date.now() });
        await this._txDone(tx);
        this._notifyCount();
        return true;
    },

    async count() {
        if (!this.db) return 0;
        const tx    = this.db.transaction(this.STORE, 'readonly');
        const store = tx.objectStore(this.STORE);
        return new Promise((resolve, reject) => {
            const req = store.count();
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    },

    async _syncPending() {
        if (!this.db) return;

        const tx      = this.db.transaction(this.STORE, 'readwrite');
        const store   = tx.objectStore(this.STORE);
        const records = await this._getAll(store);

        if (records.length === 0) return;

        for (const record of records) {
            try {
                await window.axios.post('/servant/visits/sync', record);
                store.delete(record.id);
            } catch (err) {
                // 409 = conflict (مخدوم أُعيد تعيينه) — احذف السجل وأبلغ المستخدم
                if (err?.response?.status === 409) {
                    store.delete(record.id);
                    dispatchToLivewire('offlineSyncConflict', {
                        beneficiaryId: record.beneficiary_id,
                    });
                }
                // أي خطأ آخر: أبق السجل للمحاولة مرة أخرى
            }
        }

        await this._txDone(tx);
        this._notifyCount();
    },

    _notifyCount() {
        this.count().then((n) => {
            dispatchToLivewire('offlineQueueCount', { count: n });
        });
    },

    _openDb() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(this.DB_NAME, this.DB_VERSION);

            req.onupgradeneeded = (event) => {
                const db    = event.target.result;
                if (!db.objectStoreNames.contains(this.STORE)) {
                    db.createObjectStore(this.STORE, {
                        keyPath:       'id',
                        autoIncrement: true,
                    });
                }
            };

            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    },

    _getAll(store) {
        return new Promise((resolve, reject) => {
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    },

    _txDone(tx) {
        return new Promise((resolve, reject) => {
            tx.oncomplete = () => resolve();
            tx.onerror    = () => reject(tx.error);
        });
    },
};

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
