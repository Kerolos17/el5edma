// Firebase version must match the version used in app.js (firebase@12)
importScripts('https://www.gstatic.com/firebasejs/12.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.0.0/firebase-messaging-compat.js');

let messaging = null;

// Receive Firebase config from app.js on first connection
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'FIREBASE_CONFIG') {
        const config = event.data.config;
        try {
            if (!firebase.apps.length) {
                firebase.initializeApp(config);
            }
            messaging = firebase.messaging();

            messaging.onBackgroundMessage((payload) => {
                console.log('[firebase-messaging-sw.js] Background message:', payload);

                const notificationTitle = payload.notification?.title || 'إشعار جديد';
                const notificationOptions = {
                    body:  payload.notification?.body || '',
                    icon:  '/icons/icon-192x192.png',
                    badge: '/icons/icon-72x72.png',
                    dir:   'rtl',
                    // Pass the deep-link URL so notificationclick can navigate to the right record
                    data: {
                        url: payload.data?.url || '/admin',
                        ...payload.data,
                    },
                };

                self.registration.showNotification(notificationTitle, notificationOptions);
            });

            console.log('[firebase-messaging-sw.js] Firebase initialized successfully.');
        } catch (e) {
            console.error('[firebase-messaging-sw.js] Initialization error:', e);
        }
    }
});

// Handle notification clicks from Firebase background messages
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/admin';

    event.waitUntil(
        clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                for (const client of clientList) {
                    if ('focus' in client) {
                        client.focus();
                        if ('navigate' in client) client.navigate(targetUrl);
                        return;
                    }
                }
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
    );
});
