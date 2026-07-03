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

<body class="web-app-body" x-data="{ drawer: false }">
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
                class="app-mobile-nav-item {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                <i class="ph {{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    @livewire('servant.create-visit-wizard')

    <button x-data @click="$dispatch('open-wizard')"
            class="app-fab lg:hidden" aria-label="زيارة جديدة">
        <i class="ph-bold ph-plus" aria-hidden="true"></i>
    </button>

    <x-web-app.install-prompt />

    @livewireScripts

    <div x-show="drawer" @click="drawer = false" class="app-drawer-backdrop lg:hidden" style="display:none" aria-hidden="true"></div>
    <div x-show="drawer"
         x-transition:enter="transition-transform duration-300 ease-out"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200 ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="app-drawer lg:hidden" style="display:none">

        <div class="app-drawer-head">
            <div class="app-drawer-avatar">
                @if ($appUser->profile_photo_url)
                    <img src="{{ $appUser->profile_photo_url }}" alt="">
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
</body>

</html>
