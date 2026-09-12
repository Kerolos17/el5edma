// Firebase version must match the version used in app.js (firebase@12)
importScripts('https://www.gstatic.com/firebasejs/12.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.0.0/firebase-messaging-compat.js');

let messaging = null;

function safeInternalPath(value) {
    try {
        const url = new URL(value || '/app/dashboard', self.location.origin);

        if (url.origin !== self.location.origin || !url.pathname.startsWith('/')) {
            return '/app/dashboard';
        }

        return `${url.pathname}${url.search}${url.hash}`;
    } catch (error) {
        return '/app/dashboard';
    }
}

// Receive Firebase config from app.js on first connection.
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'FIREBASE_CONFIG') {
        const config = event.data.config;

        try {
            if (!firebase.apps.length) {
                firebase.initializeApp(config);
            }

            messaging = firebase.messaging();

            // Server messages already include a notification payload and
            // webpush.fcm_options.link. Firebase displays those automatically
            // in the background. Calling showNotification() again here would
            // create duplicate notifications for the same push.
            messaging.onBackgroundMessage((payload) => {
                console.log('[firebase-messaging-sw.js] Background message received:', payload?.messageId || 'unknown');
            });

            console.log('[firebase-messaging-sw.js] Firebase initialized successfully.');
        } catch (error) {
            console.error('[firebase-messaging-sw.js] Initialization error:', error);
        }
    }
});

// Handle clicks for notifications that expose data.url. Automatic Firebase
// notifications use webpush.fcm_options.link directly; this remains as a safe
// fallback for any custom notifications created by the app.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = safeInternalPath(event.notification.data?.url);

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    client.focus();

                    if ('navigate' in client) {
                        return client.navigate(targetUrl);
                    }

                    return;
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        }),
    );
});
