import './app';
import '@phosphor-icons/web/regular';
import '@phosphor-icons/web/bold';
import '@phosphor-icons/web/fill';

let deferredInstallPrompt = null;

function setTheme(theme) {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = normalizedTheme;
    localStorage.setItem('web-app-theme', normalizedTheme);
}

function restoreTheme() {
    const storedTheme = localStorage.getItem('web-app-theme');
    const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
    setTheme(storedTheme || (prefersDark ? 'dark' : 'light'));
}

document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-theme-toggle]')) return;

    setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
});

const PING_URL = '/_pwa/ping';
let ongoingCheck = null;

function syncOnlineState() {
    const offlineBanner = document.querySelector('[data-offline-banner]');
    if (!offlineBanner) return;

    // Abort any in-flight connectivity check
    if (ongoingCheck) {
        ongoingCheck.abort();
        ongoingCheck = null;
    }

    const hide = () => offlineBanner.classList.remove('is-visible');
    const show = () => offlineBanner.classList.add('is-visible');

    // Browser says online — trust it immediately
    if (navigator.onLine) return hide();

    // navigator says offline — verify with a real server request.
    // GET /_pwa/ping goes through the SW, which uses network-first:
    //   online  → SW fetches from server (204) → promise resolves → hide
    //   offline → SW fetch fails, no cache → promise rejects → show
    // Require 2 consecutive failures to prevent brief glitches.
    const check = (attempt = 0) => {
        if (navigator.onLine) return hide();

        const ctrl = new AbortController();
        ongoingCheck = ctrl;
        const ttl = attempt === 0 ? 3000 : 6000;

        const timer = setTimeout(() => ctrl.abort(), ttl);

        fetch(PING_URL, { cache: 'no-store', signal: ctrl.signal })
            .then(() => { clearTimeout(timer); ongoingCheck = null; hide(); })
            .catch(() => {
                clearTimeout(timer); ongoingCheck = null;
                if (navigator.onLine) return hide(); // came back during check
                if (attempt === 0) {
                    setTimeout(() => check(1), 2000);
                } else {
                    show(); // two consecutive failures — truly offline
                }
            });
    };

    check();
}

// Debounce offline event — give network time to stabilize before checking
let offlineTimer = null;
window.addEventListener('online', () => {
    clearTimeout(offlineTimer);
    syncOnlineState();
});
window.addEventListener('offline', () => {
    clearTimeout(offlineTimer);
    offlineTimer = setTimeout(syncOnlineState, 3000);
});

document.addEventListener('livewire:navigated', () => {
    restoreTheme();
    setTimeout(syncOnlineState, 500);
});
restoreTheme();
setTimeout(syncOnlineState, 1000);

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    const installPrompt = document.querySelector('[data-install-prompt]');
    installPrompt?.removeAttribute('hidden');
});

document.addEventListener('click', async (event) => {
    const installPrompt = event.target.closest('[data-install-prompt]');
    if (!installPrompt) return;
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    installPrompt.setAttribute('hidden', 'hidden');
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
