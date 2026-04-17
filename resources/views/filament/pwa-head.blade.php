<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#0073A3">
{{-- viewport-fit=cover enables content under notch/Dynamic Island on iOS --}}
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

<style>
    /* Safe area insets for notch / Dynamic Island / home indicator */
    body {
        padding-bottom: env(safe-area-inset-bottom);
        padding-left: env(safe-area-inset-left);
        padding-right: env(safe-area-inset-right);
    }
    /* Prevent iOS font inflation on orientation change */
    html { -webkit-text-size-adjust: 100%; }
    /* Smooth momentum scrolling on iOS */
    .overflow-y-auto, .overflow-auto { -webkit-overflow-scrolling: touch; }
</style>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(() => {
                    // When a new SW takes over, do ONE clean reload to flush stale
                    // Livewire component state. The `refreshing` flag prevents a
                    // second reload if controllerchange fires more than once.
                    let refreshing = false;
                    navigator.serviceWorker.addEventListener('controllerchange', () => {
                        if (!refreshing) {
                            refreshing = true;
                            window.location.reload();
                        }
                    });
                })
                .catch(err => console.warn('SW registration failed:', err));
        });
    }
</script>
