// Shared notification-bell UI handlers (used by web-app and servant layouts)

const NOTIF_MUTE_KEY = 'ministry-notif-muted';

function getFocusableElements(root) {
    return Array.from(
        root.querySelectorAll('button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])'),
    ).filter((el) => el.getClientRects().length > 0);
}

function toggleNotificationPanel(root) {
    const panel = root.querySelector('[data-notif-panel]');
    const backdrop = root.querySelector('[data-notif-backdrop]');
    const toggle = root.querySelector('[data-notif-toggle]');
    const isOpen = panel?.classList.toggle('is-open');
    backdrop?.classList.toggle('is-open', isOpen);

    if (panel) {
        panel.inert = !isOpen;
        if (isOpen) {
            window.__notificationReturnFocus = document.activeElement;
            getFocusableElements(panel)[0]?.focus();
        } else if (window.__notificationReturnFocus instanceof HTMLElement) {
            window.__notificationReturnFocus.focus();
            window.__notificationReturnFocus = null;
        }
    }
    if (toggle) toggle.setAttribute('aria-expanded', String(!!isOpen));
}

function closeNotificationPanel(root) {
    const panel = root.querySelector('[data-notif-panel]');
    const backdrop = root.querySelector('[data-notif-backdrop]');
    const toggle = root.querySelector('[data-notif-toggle]');
    panel?.classList.remove('is-open');
    backdrop?.classList.remove('is-open');
    if (panel) panel.inert = true;
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
    if (window.__notificationReturnFocus instanceof HTMLElement) {
        window.__notificationReturnFocus.focus();
        window.__notificationReturnFocus = null;
    }
}

function syncNotificationMuteIcons(root) {
    const muted = localStorage.getItem(NOTIF_MUTE_KEY) === 'true';
    const onIcon = root?.querySelector('[data-notif-sound-on]');
    const offIcon = root?.querySelector('[data-notif-sound-off]');
    const muteButton = root?.querySelector('[data-notif-mute]');
    if (onIcon) onIcon.style.display = muted ? 'none' : '';
    if (offIcon) offIcon.style.display = muted ? '' : 'none';

    if (muteButton) {
        const label = muted
            ? (muteButton.dataset.labelUnmute ?? '')
            : (muteButton.dataset.labelMute ?? '');
        if (label) {
            muteButton.title = label;
            muteButton.setAttribute('aria-label', label);
        }
        muteButton.setAttribute('aria-pressed', String(muted));
    }
}

function initNotifications() {
    document.querySelectorAll('[data-user-id]').forEach((root) => {
        syncNotificationMuteIcons(root);
        const initialPanel = root.querySelector('[data-notif-panel]');
        if (initialPanel) initialPanel.inert = true;
    });

    document.addEventListener('click', (event) => {
        const notifToggle = event.target.closest('[data-notif-toggle]');
        if (notifToggle) {
            const root = notifToggle.closest('[data-user-id]');
            if (root) toggleNotificationPanel(root);
            return;
        }

        const notifBackdrop = event.target.closest('[data-notif-backdrop]');
        if (notifBackdrop) {
            const root = notifBackdrop.closest('[data-user-id]');
            if (root) closeNotificationPanel(root);
            return;
        }

        const muteBtn = event.target.closest('[data-notif-mute]');
        if (muteBtn) {
            const muted = localStorage.getItem(NOTIF_MUTE_KEY) === 'true';
            localStorage.setItem(NOTIF_MUTE_KEY, muted ? 'false' : 'true');
            const root = muteBtn.closest('[data-user-id]');
            if (root) syncNotificationMuteIcons(root);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const openNotif = document.querySelector('[data-notif-panel].is-open');
        if (!openNotif) return;
        const root = openNotif.closest('[data-user-id]');
        if (root) {
            closeNotificationPanel(root);
            event.preventDefault();
        }
    });

    document.addEventListener('livewire:navigated', () => {
        document.querySelectorAll('[data-user-id]').forEach((root) => {
            syncNotificationMuteIcons(root);
            const panel = root.querySelector('[data-notif-panel]');
            if (panel) panel.inert = true;
        });
        document.querySelectorAll('[data-notif-panel], [data-notif-backdrop]').forEach(el => el.classList.remove('is-open'));
    });
}

initNotifications();
