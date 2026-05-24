<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ __('web_app.brand.name') }}</title>

    <script>
        (() => {
            const storedTheme = localStorage.getItem('web-app-theme');
            const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
            const theme = storedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
        })();
    </script>

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

<body class="web-app-body">
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
                @foreach ($navigationItems as $item)
                    <a href="{{ route($item['route']) }}" wire:navigate
                        class="app-nav-item {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                        <i class="ph {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

        </aside>

        <div class="app-main">
            <header class="app-topbar" data-user-id="{{ $appUser->id }}">
                <div>
                    <h1 class="app-topbar-title">{{ $title ?? __('web_app.shell.dashboard') }}</h1>
                </div>

                <form action="{{ route('app.beneficiaries') }}" method="GET" class="app-global-search">
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q', '') }}"
                        placeholder="{{ __('web_app.shell.search_placeholder') }}"
                        aria-label="{{ __('web_app.shell.search_label') }}">
                </form>

                <div class="app-topbar-actions">
                    <a href="{{ route('app.dashboard') }}" wire:navigate class="app-icon-button" title="{{ __('web_app.shell.go_dashboard') }}">
                        <i class="ph ph-house-line" aria-hidden="true"></i>
                    </a>

                    <button type="button" class="app-icon-button" data-theme-toggle title="{{ __('web_app.shell.toggle_dark_mode') }}">
                        <i class="ph ph-moon" aria-hidden="true" data-theme-dark-icon></i>
                        <i class="ph ph-sun" aria-hidden="true" data-theme-light-icon></i>
                    </button>

                    <button type="submit" form="web-app-language-form" class="app-icon-button app-language-button" title="{{ __('web_app.shell.switch_language') }}">
                        <i class="ph ph-translate" aria-hidden="true"></i>
                        <span>{{ $nextLocaleLabel }}</span>
                    </button>
                    <form id="web-app-language-form" method="POST"
                        action="{{ route('language.switch', $nextLocale) }}"
                        class="hidden">
                        @csrf
                    </form>

                    @livewire('servant.notifications-bell')

                    <div style="position:relative">
                        <button type="button"
                            onclick="event.stopPropagation();var m=document.getElementById('prof-dd');m.style.display=m.style.display==='block'?'none':'block'"
                            style="display:flex;align-items:center;justify-content:center;border:0;padding:0;background:transparent;cursor:pointer;border-radius:9999px">
                            <div style="width:36px;height:36px;border-radius:9999px;overflow:hidden;border:2px solid #dbe3ee;background:#e5e7eb;display:flex;align-items:center;justify-content:center">
                                @if ($appUser->profile_photo_url)
                                    <img src="{{ $appUser->profile_photo_url }}" alt="" style="width:100%;height:100%;object-fit:cover">
                                @else
                                    <span style="font-size:12px;font-weight:800;color:#9ca3af">{{ mb_substr($appUser->name, 0, 1) }}</span>
                                @endif
                            </div>
                        </button>
                        <div id="prof-dd" class="prof-dropdown" style="display:none;position:absolute;top:calc(100% + 10px);inset-inline-end:0;min-width:220px;z-index:70;direction:{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                            <div class="prof-dropdown-head">
                                <p>{{ $appUser->name }}</p>
                                <span>{{ $roleLabel }}</span>
                            </div>
                            <a href="{{ route('app.profile') }}" wire:navigate class="prof-dropdown-item">
                                <i class="ph ph-user-circle" aria-hidden="true"></i>
                                {{ __('web_app.navigation.profile') }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="prof-dropdown-item prof-dropdown-item-danger">
                                    <i class="ph ph-sign-out" aria-hidden="true"></i>
                                    {{ __('web_app.actions.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="app-content">
                {{ $slot }}
            </main>
        </div>
    </div>

    <nav class="app-mobile-nav" aria-label="{{ __('web_app.shell.mobile_navigation') }}">
        @php $mobileItems = array_slice($navigationItems, 0, 4); @endphp
        @foreach ($mobileItems as $item)
            <a href="{{ route($item['route']) }}" wire:navigate
                class="app-mobile-nav-item {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                <i class="ph {{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
        <a href="{{ route('app.profile') }}" wire:navigate
            class="app-mobile-nav-item {{ request()->routeIs('app.profile') ? 'is-active' : '' }}">
            <i class="ph ph-user-circle" aria-hidden="true"></i>
            <span>{{ __('web_app.navigation.profile') }}</span>
        </a>
    </nav>

    <x-web-app.offline-banner />
    <x-web-app.install-prompt />

    <script>
        document.addEventListener('click', function () {
            var m = document.getElementById('prof-dd');
            if (m) m.style.display = 'none';
        });
    </script>
    @livewireScripts
</body>

</html>
