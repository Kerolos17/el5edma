@props([
    'appUser' => auth()->user(),
    'roleLabel' => \App\Support\WebAppScope::roleLabel(auth()->user()->role ?? 'servant'),
])

@php
$nextLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
$nextLocaleLabel = strtoupper($nextLocale);
@endphp

<header class="app-topbar" data-user-id="{{ $appUser->id }}" x-data="{ profileOpen: false }" @click.outside="profileOpen = false">
    <div class="app-topbar-start">
        <button type="button" @click="drawer = true" class="app-hamburger lg:hidden" aria-label="{{ __('web_app.shell.main_navigation') }}">
            <i class="ph ph-list" aria-hidden="true"></i>
        </button>
    </div>

    <form action="{{ route('app.beneficiaries') }}" method="GET" class="app-global-search" role="search">
        <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
        <input
            type="search"
            name="q"
            value="{{ request('q', '') }}"
            placeholder="{{ __('web_app.shell.search_placeholder') }}"
            aria-label="{{ __('web_app.shell.search_label') }}"
            dir="auto"
            autocomplete="off">
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

        @livewire('servant.notifications-bell')

        <div class="app-profile-menu">
            <button
                type="button"
                class="app-icon-button app-profile-trigger"
                @click="profileOpen = !profileOpen"
                :aria-expanded="profileOpen"
                aria-haspopup="menu"
                title="{{ __('web_app.navigation.profile') }}">
                <span class="app-profile-avatar">
                    @if ($appUser->profile_photo_url)
                        <img src="{{ $appUser->profile_photo_url }}" alt="">
                    @else
                        <span aria-hidden="true">{{ mb_substr($appUser->name, 0, 1) }}</span>
                    @endif
                </span>
            </button>

            <div
                x-show="profileOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-1"
                class="app-dropdown-menu app-profile-dropdown"
                style="display: none;"
                role="menu">
                <div class="prof-dropdown-head">
                    <p>{{ $appUser->name }}</p>
                    <span>{{ $roleLabel }}</span>
                </div>
                <a href="{{ route('app.profile') }}" wire:navigate class="app-dropdown-item" role="menuitem">
                    <i class="ph ph-user-circle" aria-hidden="true"></i>
                    {{ __('web_app.navigation.profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="app-dropdown-item app-dropdown-item-danger" role="menuitem">
                        <i class="ph ph-sign-out" aria-hidden="true"></i>
                        {{ __('web_app.actions.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <form id="web-app-language-form" method="POST" action="{{ route('language.switch', $nextLocale) }}" class="hidden" aria-hidden="true">
        @csrf
    </form>
</header>
