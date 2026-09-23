// Servant Panel — JavaScript entry point
// Alpine.js is provided by Livewire 3 automatically
import './bootstrap';
import './notifications';
import { offlineQueue } from './offline-queue';
import { initPushNotifications } from './push-notifications';
import '@phosphor-icons/web/regular';
import '@phosphor-icons/web/bold';
import '@phosphor-icons/web/fill';

// ─── Reveal animations ────────────────────────────────────────────────────────

document.addEventListener('livewire:navigated', () => {
    triggerRevealAnimations();
    applyServantTheme(servantTheme(), false);
});

document.addEventListener('DOMContentLoaded', () => {
    triggerRevealAnimations();
    initPushNotifications({ logTag: 'servant.js' });
    setupEchoListener();
    offlineQueue.init();
    // Expose للـ wizard في كلا التخطيطين
    window.offlineQueue = offlineQueue;
    window.__servantOfflineQueue = offlineQueue;
});

// ─── Servant theme (light default, dark optional + persisted) ───────────────

const SERVANT_THEME_KEY = 'servant-theme';

function servantTheme() {
    try {
        return localStorage.getItem(SERVANT_THEME_KEY) === 'dark' ? 'dark' : 'light';
    } catch (e) {
        return 'light';
    }
}

function applyServantTheme(mode, persist = true) {
    document.documentElement.setAttribute('data-theme', mode);

    if (persist) {
        try {
            localStorage.setItem(SERVANT_THEME_KEY, mode);
        } catch (e) {
            // Private mode: theme applies for this session only.
        }
    }

    document
        .querySelector('meta[name="theme-color"]')
        ?.setAttribute('content', mode === 'dark' ? '#0f172a' : '#006D77');

    document.querySelectorAll('[data-servant-theme-toggle]').forEach((btn) => {
        btn.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
        btn.querySelector('.icon-moon')?.classList.toggle('hidden', mode === 'dark');
        btn.querySelector('.icon-sun')?.classList.toggle('hidden', mode !== 'dark');
    });
}

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-servant-theme-toggle]')) {
        applyServantTheme(servantTheme() === 'dark' ? 'light' : 'dark');
    }
});

function triggerRevealAnimations() {
    document.querySelectorAll('.reveal-card').forEach((el, i) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.animation = 'none';
        void el.offsetWidth;
        el.style.animation = `revealUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) ${i * 0.06}s forwards`;
    });
}

// ─── Real-time (Echo / Pusher) ────────────────────────────────────────────────

function setupEchoListener() {
    try {
        const userEl = document.querySelector('[data-user-id]');
        const userId = userEl?.getAttribute('data-user-id') ?? window.Laravel?.user?.id;

        if (!userId || !window.Echo) return;

        const handler = () => dispatchToLivewire('notificationCreated', {});

        const ch = window.Echo.private(`user.${userId}`);
        ch.listen('NewMinistryNotification', handler);
        ch.listen('.App\\Events\\NewMinistryNotification', handler);
    } catch (e) {
        // Echo not configured — polling fallback is active
        }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function dispatchToLivewire(event, payload) {
    try {
        if (window.Livewire) {
            window.Livewire.dispatch(event, payload);
        }
    } catch (e) {
        // Livewire not ready yet
    }
}
