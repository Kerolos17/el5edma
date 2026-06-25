import './app';
import '@phosphor-icons/web/regular';
import '@phosphor-icons/web/bold';
import '@phosphor-icons/web/fill';

let deferredInstallPrompt = null;

// ── Theme ─────────────────────────────────────────────────────

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

// ── Modal accessibility: ESC + focus management ───────────────

/**
 * Selectable focusable elements inside a container.
 */
function focusableEls(container) {
    return /** @type {HTMLElement[]} */ ([
        ...container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), ' +
            'select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        ),
    ]).filter((el) => !el.closest('[hidden]'));
}

/**
 * Close the topmost visible modal by simulating a backdrop click.
 * Works for any `.app-modal-backdrop[wire\\:click]` present in the DOM.
 */
function closeTopmostModal() {
    const backdrop = document.querySelector('.app-modal-backdrop');
    backdrop?.click();
}

// ESC key closes the open modal
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const panel = document.querySelector('.app-modal-panel');
    if (!panel) return;
    event.preventDefault();
    closeTopmostModal();
});

// Focus first focusable element when a modal opens (via Livewire DOM mutation)
const modalObserver = new MutationObserver(() => {
    const panel = document.querySelector('.app-modal-panel:not([data-focused])');
    if (!panel) return;
    panel.dataset.focused = '1';
    const first = focusableEls(panel)[0];
    if (first) {
        // Small delay so Livewire finishes rendering
        requestAnimationFrame(() => first.focus());
    }
});

function observeModals() {
    const shell = document.querySelector('.app-shell');
    if (!shell) return;
    modalObserver.observe(shell, { childList: true, subtree: true });
}

// Tab-trap: keep focus inside the modal panel while it is open
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab') return;
    const panel = document.querySelector('.app-modal-panel');
    if (!panel) return;

    const els = focusableEls(panel);
    if (!els.length) return;

    const first = els[0];
    const last = els[els.length - 1];

    if (event.shiftKey) {
        if (document.activeElement === first) {
            event.preventDefault();
            last.focus();
        }
    } else {
        if (document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
});

// ── PWA install prompt ─────────────────────────────────────────

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    document.querySelector('[data-install-prompt]')?.removeAttribute('hidden');
});

document.addEventListener('click', async (event) => {
    const installPrompt = event.target.closest('[data-install-prompt]');
    if (!installPrompt || !deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    installPrompt.setAttribute('hidden', 'hidden');
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { updateViaCache: 'none' })
            .then((reg) => reg.update())
            .catch(() => {});
    });
}

// ── Livewire navigation lifecycle ─────────────────────────────

document.addEventListener('livewire:navigated', () => {
    restoreTheme();
    observeModals();
});

// Init
restoreTheme();
observeModals();
