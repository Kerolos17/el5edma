<div class="app-offline-banner" data-offline-banner>
    <i class="ph ph-wifi-slash" aria-hidden="true"></i>
    <span>{{ __('web_app.pwa.offline_banner') }}</span>
    <button type="button" data-offline-close class="app-offline-close" aria-label="{{ __('web_app.actions.close') }}">
        <i class="ph ph-x" aria-hidden="true"></i>
    </button>
    <button type="button" data-offline-dismiss class="app-offline-dismiss">
        {{ __('web_app.pwa.dont_show_again') }}
    </button>
</div>

@php
    $cspNonce = request()->attributes->get('_csp_nonce', '');
@endphp
@if ($cspNonce)
<script nonce="{{ $cspNonce }}">
(function () {
    const key = 'web-app-offline-banner-disabled';
    const banner = document.querySelector('[data-offline-banner]');
    if (!banner || banner.dataset.offlineInitialized) return;
    banner.dataset.offlineInitialized = '1';

    function hide() {
        if (document.activeElement && banner.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        banner.classList.remove('is-visible');
        banner.removeAttribute('aria-hidden');
    }

    function disableForever() {
        try { localStorage.setItem(key, '1'); } catch (e) {}
    }

    try {
        if (localStorage.getItem(key) === '1') {
            banner.remove();
            return;
        }
    } catch (e) {}

    banner.addEventListener('click', function (event) {
        if (event.target.closest('[data-offline-close]')) {
            event.preventDefault();
            disableForever();
            hide();
            return;
        }
        if (event.target.closest('[data-offline-dismiss]')) {
            event.preventDefault();
            disableForever();
            hide();
        }
    });

    function onOffline() {
        try {
            if (localStorage.getItem(key) === '1') return;
        } catch (e) {}
        banner.classList.add('is-visible');
        setTimeout(hide, 5000);
    }

    window.addEventListener('online', hide);
    window.addEventListener('offline', onOffline);
})();
</script>
@endif
