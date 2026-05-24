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
    offlineBanner.classList.toggle('is-visible', !navigator.onLine);
}

window.addEventListener('online', syncOnlineState);
window.addEventListener('offline', syncOnlineState);
document.addEventListener('livewire:navigated', () => {
    restoreTheme();
    syncOnlineState();
});
restoreTheme();
syncOnlineState();

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
