<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#006D77">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cairo:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/servant.css', 'resources/js/servant.js'])
    @livewireStyles
</head>
<body>
    {{-- Ambient background shapes --}}
    <div class="organic-shape organic-shape-1"></div>
    <div class="organic-shape organic-shape-2"></div>
    <div class="organic-shape organic-shape-3"></div>

    {{-- Desktop sidebar (lg and up) --}}
    <x-servant.desktop-sidebar />

    {{-- Main content wrapper --}}
    <main class="relative z-10 lg:mr-64 min-h-screen">
        {{-- Mobile / tablet header --}}
        <x-servant.header />

        {{-- Page content --}}
        <div>
            {{ $slot }}
        </div>

        {{-- Mobile bottom navigation (hidden on desktop) --}}
        <x-servant.bottom-nav />
    </main>

    {{-- Offline Banner --}}
    <x-servant.offline-banner />

    {{-- PWA Install Prompt --}}
    <x-servant.install-prompt />

    {{-- Visit Wizard (persistent in layout) --}}
    @livewire('servant.create-visit-wizard')

    {{-- Toast notifications --}}
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
                this.timer = setTimeout(() => this.visible = false, 3500);
            }
         }"
         @toast.window="show($event.detail.message, $event.detail.type)">

        <div :class="[
                's-toast',
                visible ? 'show' : '',
                type === 'success' ? 's-toast-success' : (type === 'error' ? 's-toast-error' : 's-toast-warning')
             ]"
             x-show="visible"
             x-cloak
             :role="type === 'error' ? 'alert' : 'status'"
             :aria-live="type === 'error' ? 'assertive' : 'polite'"
             aria-atomic="true">
            <i :class="{
                'ph-fill ph-check-circle text-lg': type === 'success',
                'ph-fill ph-x-circle text-lg':    type === 'error',
                'ph-fill ph-warning text-lg':      type === 'warning'
               }"
               aria-hidden="true"></i>
            <span x-text="message"></span>
        </div>
    </div>

    @livewireScripts
</body>
</html>
