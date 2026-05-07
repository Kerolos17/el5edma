<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name') }}</title>

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
    @endphp

    <div class="app-shell">
        <aside class="app-sidebar" aria-label="التنقل الرئيسي">
            <div class="app-brand">
                <div class="app-brand-mark">MS</div>
                <div>
                    <p class="app-brand-title">Ministry System</p>
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

            <div class="app-sidebar-footer">
                <p class="app-muted-label">لوحة Filament الاحتياطية</p>
                <a href="/admin" class="app-admin-link">
                    <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                    <span>فتح /admin</span>
                </a>
            </div>
        </aside>

        <div class="app-main">
            <header class="app-topbar" data-user-id="{{ $appUser->id }}">
                <div>
                    <p class="app-topbar-kicker">واجهة موحدة لكل الأدوار</p>
                    <h1 class="app-topbar-title">{{ $title ?? 'لوحة التحكم' }}</h1>
                </div>

                <form action="{{ route('app.beneficiaries') }}" method="GET" class="app-global-search">
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q', '') }}"
                        placeholder="ابحث عن مخدوم، زيارة، طلب صلاة..."
                        aria-label="بحث سريع">
                </form>

                <div class="app-topbar-actions">
                    <a href="{{ route('app.dashboard') }}" wire:navigate class="app-icon-button" title="العودة للوحة التحكم">
                        <i class="ph ph-house-line" aria-hidden="true"></i>
                    </a>

                    <a href="{{ route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                        onclick="event.preventDefault(); document.getElementById('web-app-language-form').submit();"
                        class="app-icon-button" title="تغيير اللغة">
                        <i class="ph ph-translate" aria-hidden="true"></i>
                    </a>
                    <form id="web-app-language-form" method="POST"
                        action="{{ route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                        class="hidden">
                        @csrf
                    </form>

                    @livewire('servant.notifications-bell')

                    <div class="app-profile-chip">
                        <span>{{ mb_substr($appUser->name, 0, 1) }}</span>
                        <div>
                            <strong>{{ $appUser->name }}</strong>
                            <small>{{ $roleLabel }}</small>
                        </div>
                    </div>
                </div>
            </header>

            <main class="app-content">
                {{ $slot }}
            </main>
        </div>
    </div>

    <nav class="app-mobile-nav" aria-label="تنقل الموبايل">
        @foreach (array_slice($navigationItems, 0, 5) as $item)
            <a href="{{ route($item['route']) }}" wire:navigate
                class="app-mobile-nav-item {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                <i class="ph {{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <x-web-app.offline-banner />
    <x-web-app.install-prompt />

    @livewireScripts
</body>

</html>
