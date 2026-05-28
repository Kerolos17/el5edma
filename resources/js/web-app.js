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

function syncOnlineState() {
    const offlineBanner = document.querySelector('[data-offline-banner]');
    if (!offlineBanner) return;

    const showOffline = () => offlineBanner.classList.add('is-visible');
    const hideOffline = () => offlineBanner.classList.remove('is-visible');

    if (navigator.onLine) {
        hideOffline();
        return;
    }

    // navigator says offline — verify with a real HEAD request.
    // HEAD bypasses the service worker (SW only intercepts GET).
    // Any response (even 404) means we're online — only network errors show the banner.
    const probe = fetch('/manifest.json', { method: 'HEAD', cache: 'no-store' });
    const timeout = new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 3000));
    Promise.race([probe, timeout]).then(hideOffline).catch(showOffline);
}

window.addEventListener('online', syncOnlineState);
window.addEventListener('offline', syncOnlineState);
document.addEventListener('livewire:navigated', () => {
    restoreTheme();
    // Delay slightly to let the SW settle after navigation
    setTimeout(syncOnlineState, 500);
});
restoreTheme();
// Delay initial check to prevent false offline flash
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
