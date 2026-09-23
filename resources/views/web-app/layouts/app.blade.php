<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ __('web_app.brand.name') }}</title>

    @php $cspNonce = request()->attributes->get('_csp_nonce', ''); @endphp
    <script nonce="{{ $cspNonce }}">
        (() => {
            const storedTheme = localStorage.getItem('web-app-theme');
            const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
            const theme = storedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
        })();
    </script>

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @once
        @php
            $manifestPath = public_path('build/manifest.json');
            $viteEntries = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) ?? [] : [];
            $hasWebAppCss = array_key_exists('resources/css/web-app.css', $viteEntries);
            $hasWebAppJs = array_key_exists('resources/js/web-app.js', $viteEntries);
        @endphp

        @if ($hasWebAppCss && $hasWebAppJs)
            @vite(['resources/css/web-app.css', 'resources/js/web-app.js'])
        @else
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    @endonce
    @livewireStyles
</head>

    <body class="web-app-body" x-data="{
        drawer: false,
        trapDrawer(e) {
            const root = this.$refs.drawerPanel;
            if (!root) return;
            const items = Array.from(root.querySelectorAll('a[href], button:not([disabled])')).filter((el) => el.getClientRects().length > 0);
            if (items.length === 0) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    }">
    @php
        $appUser = auth()->user();
        $navigationItems = \App\Support\WebAppNavigation::items($appUser);
        $roleLabel = \App\Support\WebAppScope::roleLabel($appUser->role);
        $nextLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
        $nextLocaleLabel = strtoupper($nextLocale);
    @endphp

    <div class="app-shell">
        <aside class="app-sidebar" aria-label="{{ __('web_app.shell.main_navigation') }}">
            <div class="app-brand">
                <div class="app-brand-mark">AS</div>
                <div>
                    <p class="app-brand-title">{{ __('web_app.brand.name') }}</p>
                    <p class="app-brand-subtitle">{{ $roleLabel }}</p>
                </div>
            </div>

            <nav class="app-nav">
                @php $currentGroup = null; @endphp
                @foreach ($navigationItems as $item)
                    @if (($item['group'] ?? null) && $item['group'] !== $currentGroup)
                        @php $currentGroup = $item['group']; @endphp
                        <span class="app-nav-section">{{ __("web_app.nav_groups.$currentGroup") }}</span>
                    @endif
                    <a href="{{ route($item['route']) }}" wire:navigate
                        class="app-nav-item {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                        <i class="ph {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

        </aside>

        <div class="app-main">
            <x-web-app.header :app-user="$appUser" :role-label="$roleLabel" />

            <main class="app-content">
                {{ $slot }}
            </main>
        </div>
    </div>

    <nav class="app-mobile-nav" aria-label="{{ __('web_app.shell.mobile_navigation') }}">
        @php
            $bottomNavRoutes = ['app.dashboard', 'app.visits', 'app.beneficiaries', 'app.notifications'];
            $bottomItems = collect($navigationItems)->filter(fn ($i) => in_array($i['route'], $bottomNavRoutes))->all();
        @endphp
        @foreach ($bottomItems as $item)
            <a href="{{ route($item['route']) }}" wire:navigate
                @if(request()->routeIs($item['route'])) aria-current="page" @endif
                class="app-mobile-nav-item {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                <i class="ph {{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    @livewire('servant.create-visit-wizard')

    <button onclick="window.dispatchEvent(new CustomEvent('open-wizard'))"
            class="app-fab lg:hidden" aria-label="{{ __('web_app.actions.record_visit') }}">
        <i class="ph-bold ph-plus" aria-hidden="true"></i>
    </button>

    <x-web-app.install-prompt />

    @livewireScripts

    <div x-show="drawer" @click="drawer = false" class="app-drawer-backdrop lg:hidden" style="display:none" aria-hidden="true"></div>
    <div x-show="drawer"
         x-ref="drawerPanel"
         role="dialog" aria-modal="true" aria-label="{{ __('web_app.shell.main_navigation') }}"
         :inert="!drawer"
         @keydown.escape.window="drawer = false"
         @keydown.tab="trapDrawer($event)"
         x-transition:enter="transition-transform duration-300 ease-out"
         x-transition:enter-start="rtl:-translate-x-full ltr:translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200 ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="rtl:-translate-x-full ltr:translate-x-full"
         class="app-drawer lg:hidden" style="display:none">

        <div class="app-drawer-head">
            <div class="app-drawer-avatar">
                @if ($appUser->profile_photo_url)
                    <img src="{{ $appUser->profile_photo_url }}" alt="" loading="lazy" decoding="async">
                @else
                    <span>{{ mb_substr($appUser->name, 0, 1) }}</span>
                @endif
            </div>
            <div class="app-drawer-user-info">
                <p class="app-drawer-user-name">{{ $appUser->name }}</p>
                <span class="app-drawer-user-role">{{ $roleLabel }}</span>
            </div>
            <button @click="drawer = false" class="app-drawer-close" aria-label="{{ __('web_app.actions.close') }}">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <nav class="app-drawer-nav">
            @php $currentGroup = null; @endphp
            @foreach ($navigationItems as $item)
                @if (($item['group'] ?? null) && $item['group'] !== $currentGroup)
                    @php $currentGroup = $item['group']; @endphp
                    <span class="app-nav-section">{{ __("web_app.nav_groups.$currentGroup") }}</span>
                @endif
                <a href="{{ route($item['route']) }}" wire:navigate @click="drawer = false"
                   class="app-drawer-nav-item {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                    <i class="ph {{ $item['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div style="padding:0.75rem 1rem 1.25rem">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="app-drawer-logout">
                    <i class="ph ph-sign-out" aria-hidden="true"></i>
                    {{ __('web_app.actions.logout') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Toast notifications (mirrors servant layout) --}}
    <div x-data="{
            visible: false,
            message: '',
            type: 'success',
            timer: null,
            show(msg, t = 'success') {
                this.message = msg;
                this.type = t;
                this.visible = true;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.visible = false, t === 'warning' ? 5000 : 3500);
            },
            dismiss() {
                clearTimeout(this.timer);
                this.visible = false;
            },
            pause() { clearTimeout(this.timer); },
            resume() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.visible = false, this.type === 'warning' ? 5000 : 3500);
            }
         }"
         @toast.window="show($event.detail.message, $event.detail.type)">

        <div :class="[
                'app-toast',
                visible ? 'show' : '',
                type === 'success' ? 'app-toast-success' : (type === 'error' ? 'app-toast-error' : 'app-toast-warning')
             ]"
             x-show="visible"
             x-cloak
             role="status"
             aria-live="polite"
             @mouseenter="pause()"
             @mouseleave="resume()"
             @focusin="pause()"
             @focusout="resume()"
             :role="type === 'error' ? 'alert' : 'status'"
             :aria-live="type === 'error' ? 'assertive' : 'polite'"
             aria-atomic="true">
            <button type="button" @click="dismiss()" class="app-toast-close" aria-label="{{ __('web_app.actions.close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
            <i :class="{
                'ph-fill ph-check-circle': type === 'success',
                'ph-fill ph-x-circle':    type === 'error',
                'ph-fill ph-warning':      type === 'warning'
               }"
               aria-hidden="true"></i>
            <span x-text="message"></span>
        </div>
    </div>
</body>

</html>
