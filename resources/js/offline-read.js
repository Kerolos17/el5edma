// ── Offline read for the servant field list ───────────────────────────────
// The service worker keeps a network-first cached copy of the
// offline-cache payload (names/codes/status only). When the device goes
// offline, this module renders the cached list in a full-screen overlay so
// the servant can still look up who is who in the field. On reconnect the
// overlay closes and the cache warms again.

const ENDPOINT = '/servant/beneficiaries/offline-cache';
const STATUS_LABELS = { active: 'نشط', inactive: 'غير نشط', moved: 'منتقل', deceased: 'انتقل إلى رحمة الله' };

let overlayEl = null;

function renderOverlay(payload) {
    if (overlayEl) return;

    overlayEl = document.createElement('div');
    overlayEl.className = 'offline-read-overlay';
    overlayEl.setAttribute('role', 'dialog');
    overlayEl.setAttribute('aria-label', 'قائمة المخدومين المحفوظة');

    const updated = payload.updated_at
        ? new Date(payload.updated_at).toLocaleString('ar-EG', { dateStyle: 'medium', timeStyle: 'short' })
        : null;

    const rows = (payload.beneficiaries ?? []).map((b) => `
        <div class="offline-read-row">
            <strong>${escapeHtml(b.name)}</strong>
            <span>${escapeHtml(b.code ?? '')}</span>
            <span>${escapeHtml(b.group ?? '')}</span>
            <span class="offline-read-status">${escapeHtml(STATUS_LABELS[b.status] ?? b.status ?? '')}</span>
            <span class="offline-read-last">${b.last_visit ? 'آخر زيارة: ' + escapeHtml(b.last_visit) : 'بلا زيارات'}</span>
        </div>
    `).join('');

    overlayEl.innerHTML = `
        <div class="offline-read-panel">
            <div class="offline-read-head">
                <div>
                    <strong>قائمة المخدومين — بيانات محفوظة</strong>
                    <p>${updated ? 'حُفظت على الجهاز بتاريخ ' + escapeHtml(updated) : 'لا توجد نسخة محفوظة بعد — اتصل بالإنترنت مرة واحدة لتخزين القائمة.'}</p>
                </div>
                <button type="button" class="offline-read-close" aria-label="إغلاق">✕</button>
            </div>
            <div class="offline-read-body">${rows || '<p class="offline-read-empty">القائمة فارغة.</p>'}</div>
        </div>
    `;

    overlayEl.querySelector('.offline-read-close').addEventListener('click', closeOverlay);
    document.body.appendChild(overlayEl);
}

function closeOverlay() {
    overlayEl?.remove();
    overlayEl = null;
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
}

async function showIfOffline() {
    if (navigator.onLine) return;

    try {
        // The service worker answers from its cached copy when offline.
        const response = await fetch(ENDPOINT, { headers: { Accept: 'application/json' } });
        if (!response.ok) return;
        renderOverlay(await response.json());
    } catch {
        // No cached copy either — nothing to show.
    }
}

async function warmCache() {
    if (!navigator.onLine) return;
    try {
        await fetch(ENDPOINT, { headers: { Accept: 'application/json' } });
    } catch {
        // The next successful page load warms it instead.
    }
}

window.addEventListener('offline', showIfOffline);
window.addEventListener('online', () => {
    closeOverlay();
    warmCache();
});

// Warm the cache once per page session so the list is fresh before going offline.
warmCache();
