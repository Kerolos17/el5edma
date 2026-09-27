const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function loadWorker() {
    const listeners = new Map();
    const shown = [];
    const opened = [];
    const context = {
        URL,
        console,
        self: {
            location: { origin: 'https://ministry.example' },
            registration: {
                showNotification: async (title, options) => shown.push({ title, options }),
            },
            addEventListener: (name, handler) => listeners.set(name, handler),
        },
        clients: {
            matchAll: async () => [],
            openWindow: async (url) => opened.push(url),
        },
    };
    vm.runInNewContext(
        fs.readFileSync(path.resolve(__dirname, '../../public/sw-v9.js'), 'utf8'),
        context,
    );
    return { listeners, shown, opened };
}

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
    assert.deepEqual(opened, ['/app/scheduled-visits']);
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
    assert.deepEqual(opened, ['/app/visits']);
});

test('the web app registers the same worker used for push tokens', () => {
    const app = fs.readFileSync(path.resolve(__dirname, '../../resources/js/web-app.js'), 'utf8');
    const push = fs.readFileSync(path.resolve(__dirname, '../../resources/js/push-notifications.js'), 'utf8');
    assert.match(app, /\.register\('\/sw-v9\.js'/);
    assert.match(push, /ROOT_SW_URL = '\/sw-v9\.js'/);
    assert.doesNotMatch(app, /\.register\('\/sw\.js'/);
});
