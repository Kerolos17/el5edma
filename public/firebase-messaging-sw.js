importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

// ============================================================
// يستقبل Service Worker الـ config من app.js عند أول اتصال
// ============================================================
let messaging = null;

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'FIREBASE_CONFIG') {
        const config = event.data.config;
        try {
            if (!firebase.apps.length) {
                firebase.initializeApp(config);
            }
            messaging = firebase.messaging();

            messaging.onBackgroundMessage((payload) => {
                console.log('[firebase-messaging-sw.js] Background message received:', payload);
                const notificationTitle = payload.notification?.title || 'إشعار جديد';
                const notificationOptions = {
                    body: payload.notification?.body || '',
                    icon: '/icons/icon-192x192.png',
                    badge: '/icons/icon-72x72.png',
                    dir: 'rtl',
                    data: payload.data || {},
                };
                self.registration.showNotification(notificationTitle, notificationOptions);
            });

            console.log('[firebase-messaging-sw.js] Firebase initialized successfully.');
        } catch (e) {
            console.error('[firebase-messaging-sw.js] Error initializing Firebase:', e);
        }
    }
});
