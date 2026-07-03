// Shared notification-bell UI handlers (used by web-app and servant layouts)

const NOTIF_MUTE_KEY = 'ministry-notif-muted';

function toggleNotificationPanel(root) {
    const panel = root.querySelector('[data-notif-panel]');
    const backdrop = root.querySelector('[data-notif-backdrop]');
    const isOpen = panel?.classList.toggle('is-open');
    backdrop?.classList.toggle('is-open', isOpen);
}

function closeNotificationPanel(root) {
    const panel = root.querySelector('[data-notif-panel]');
    const backdrop = root.querySelector('[data-notif-backdrop]');
    panel?.classList.remove('is-open');
    backdrop?.classList.remove('is-open');
}

function syncNotificationMuteIcons(root) {
    const muted = localStorage.getItem(NOTIF_MUTE_KEY) === 'true';
    const onIcon = root?.querySelector('[data-notif-sound-on]');
    const offIcon = root?.querySelector('[data-notif-sound-off]');
    if (onIcon) onIcon.style.display = muted ? 'none' : '';
    if (offIcon) offIcon.style.display = muted ? '' : 'none';
}

function initNotifications() {
    document.querySelectorAll('[data-user-id]').forEach(syncNotificationMuteIcons);

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
        document.querySelectorAll('[data-user-id]').forEach(syncNotificationMuteIcons);
        document.querySelectorAll('[data-notif-panel], [data-notif-backdrop]').forEach(el => el.classList.remove('is-open'));
    });
}

initNotifications();
