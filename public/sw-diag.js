// Temporary diagnostic SW v2: absolute-URL importScripts exactly like the
// app worker, reporting through the transferred MessagePort.
self.addEventListener('message', (event) => {
    if (event.data?.type !== 'diag') return;
    const port = event.ports[0];
    let result;
    try {
        importScripts(self.location.origin + '/firebase/firebase-app-compat.js');
        result = 'OK: firebase=' + typeof self.firebase;
    } catch (e) {
        result = 'FAIL: ' + String(e.message).slice(0, 250);
    }
    port.postMessage({ result });
});
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.registration.unregister()));
