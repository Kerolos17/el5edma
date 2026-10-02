const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function loadWorker() {
    const listeners = new Map();
    const importedScripts = [];
    const shown = [];
    const opened = [];
    const windows = [];
    const context = {
        URL,
        console,
        // importScripts is legal only during the worker's initial evaluation —
        // the worker relies on that: it imports the Firebase compat SDK at the
        // top level. Record the imports so we can assert exactly that.
        importScripts: (...paths) => {
            for (const p of paths.flat()) importedScripts.push(p);
        },
        caches: { open: async () => ({ match: async () => undefined, keys: async () => [], put: async () => {} }) },
        fetch: async () => ({ ok: true }),
        self: {
            location: { origin: 'https://ministry.example' },
            registration: {
                showNotification: async (title, options) => shown.push({ title, options }),
            },
            addEventListener: (name, handler) => listeners.set(name, handler),
        },
        clients: {
            matchAll: async () => windows,
            openWindow: async (url) => opened.push(url),
        },
    };
    vm.createContext(context);
    vm.runInContext(
        fs.readFileSync(path.resolve(__dirname, '../../public/sw-v10.js'), 'utf8'),
        context,
    );
    return { listeners, shown, opened, windows, context, importedScripts };
}

test('the Firebase compat SDK is imported during initial evaluation', () => {
    const { importedScripts } = loadWorker();
    assert.deepEqual(importedScripts, [
        '/firebase/firebase-app-compat.js',
        '/firebase/firebase-messaging-compat.js',
    ]);
});

test('the worker never tries lazy loading or blob fallbacks (illegal in SW)', () => {
    const source = fs.readFileSync(path.resolve(__dirname, '../../public/sw-v10.js'), 'utf8');
    assert.doesNotMatch(source, /importScriptResilient/);
    assert.doesNotMatch(source, /createObjectURL/);
    // importScripts must only appear in the two top-level statements
    // (line-initial); the comments mention it but never call it.
    assert.equal((source.match(/^importScripts\(/gm) || []).length, 2);
});

test('a cold worker displays a push and opens its internal target', async () => {
    const { listeners, shown, opened } = loadWorker();
    let work;
    listeners.get('push')({
        data: { json: () => ({
            notification: { title: 'زيارة', body: 'موعد جديد' },
            data: { url: '/app/scheduled-visits', tag: 'visit-1' },
        }) },
        waitUntil: (promise) => { work = promise; },
    });
    await work;
    assert.equal(shown.length, 1);
    assert.equal(shown[0].title, 'زيارة');
    assert.equal(shown[0].options.data.url, '/app/scheduled-visits');

    listeners.get('notificationclick')({
        notification: { data: shown[0].options.data, close() {} },
        waitUntil: (promise) => { work = promise; },
    });
    await work;
    assert.deepEqual(opened, ['https://ministry.example/app/scheduled-visits']);
});

test('external push links stay inside the app', async () => {
    const { listeners, shown } = loadWorker();
    let work;
    listeners.get('push')({
        data: { json: () => ({
            notification: { title: 'تنبيه' },
            data: { url: 'https://other.example/phishing' },
        }) },
        waitUntil: (promise) => { work = promise; },
    });
    await work;
    assert.equal(shown[0].options.data.url, '/app/dashboard');
});

test('an automatic Firebase notification opens its FCM link once', async () => {
    const { listeners, opened } = loadWorker();
    let work;
    let stopped = false;
    listeners.get('notificationclick')({
        stopImmediatePropagation: () => { stopped = true; },
        notification: {
            data: { FCM_MSG: { fcmOptions: { link: 'https://ministry.example/app/visits' } } },
            close() {},
        },
        waitUntil: (promise) => { work = promise; },
    });
    await work;
    assert.equal(stopped, true);
    assert.deepEqual(opened, ['https://ministry.example/app/visits']);
});

test('a failed navigation falls back to opening the installed app', async () => {
    const { listeners, opened, windows } = loadWorker();
    windows.push({
        navigate: async () => { throw new Error('client was discarded'); },
        focus: async () => {},
    });
    let work;
    listeners.get('notificationclick')({
        notification: { data: { url: '/app/notifications' }, close() {} },
        waitUntil: (promise) => { work = promise; },
    });
    await work;
    assert.deepEqual(opened, ['https://ministry.example/app/notifications']);
});

test('an active Firebase SDK handles its own automatic notification clicks', () => {
    const { listeners, opened, context } = loadWorker();
    vm.runInContext('firebaseMessaging = {}', context);
    let stopped = false;
    listeners.get('notificationclick')({
        stopImmediatePropagation: () => { stopped = true; },
        notification: { data: { FCM_MSG: { fcmOptions: { link: '/app/visits' } } }, close() {} },
        waitUntil() { throw new Error('Firebase should handle this click'); },
    });
    assert.equal(stopped, false);
    assert.equal(opened.length, 0);
});

test('the web app registers the same worker used for push tokens', () => {
    const app = fs.readFileSync(path.resolve(__dirname, '../../resources/js/web-app.js'), 'utf8');
    const push = fs.readFileSync(path.resolve(__dirname, '../../resources/js/push-notifications.js'), 'utf8');
    const layout = fs.readFileSync(path.resolve(__dirname, '../../resources/views/web-app/layouts/app.blade.php'), 'utf8');
    const head = fs.readFileSync(path.resolve(__dirname, '../../resources/views/filament/pwa-head.blade.php'), 'utf8');
    assert.match(app, /\.register\('\/sw-v10\.js\?v=11'/);
    assert.match(push, /ROOT_SW_URL = '\/sw-v10\.js\?v=11'/);
    assert.match(layout, /serviceWorker\.register\('\/sw-v10\.js\?v=11'\)/);
    assert.match(head, /serviceWorker\.register\('\/sw-v10\.js\?v=11'\)/);
    assert.doesNotMatch(app, /\.register\('\/sw\.js'/);
});
