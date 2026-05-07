import './app';
import '@phosphor-icons/web/regular';
import '@phosphor-icons/web/bold';
import '@phosphor-icons/web/fill';

const installPrompt = document.querySelector('[data-install-prompt]');
const offlineBanner = document.querySelector('[data-offline-banner]');
let deferredInstallPrompt = null;

function syncOnlineState() {
    if (!offlineBanner) return;
    offlineBanner.classList.toggle('is-visible', !navigator.onLine);
}

window.addEventListener('online', syncOnlineState);
window.addEventListener('offline', syncOnlineState);
syncOnlineState();

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    installPrompt?.removeAttribute('hidden');
});

installPrompt?.addEventListener('click', async () => {
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
