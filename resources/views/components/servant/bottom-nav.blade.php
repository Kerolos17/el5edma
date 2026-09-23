@php
    $items = [
        ['route' => 'servant.dashboard',        'icon' => 'ph-house',       'label' => __('servant.nav_home'),          'active' => request()->routeIs('servant.dashboard')],
        ['route' => 'servant.beneficiaries',    'icon' => 'ph-users',       'label' => __('servant.nav_beneficiaries'), 'active' => request()->routeIs('servant.beneficiaries*')],
        ['route' => 'servant.scheduled-visits', 'icon' => 'ph-calendar',    'label' => __('servant.nav_schedule'),      'active' => request()->routeIs('servant.scheduled-visits')],
    ];
@endphp

<div class="fixed bottom-0 inset-x-0 lg:hidden z-50" style="padding-bottom: env(safe-area-inset-bottom);">
    <nav class="flex items-center justify-around h-16 px-1 servant-bottombar" aria-label="{{ __('servant.nav_quick') }}">

        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}" wire:navigate
               @if($item['active']) aria-current="page" @endif
               class="relative flex flex-col items-center justify-center gap-0.5 min-w-0 min-h-[44px] flex-1 h-full transition-colors duration-200 {{ $item['active'] ? 'text-teal-600' : 'text-gray-500 hover:text-gray-700' }}">
                <i aria-hidden="true" class="{{ $item['active'] ? 'ph-fill' : 'ph' }} {{ $item['icon'] }} text-[22px] transition-all duration-200 {{ $item['active'] ? 'scale-110' : '' }}"></i>
                <span class="text-[11px] font-bold leading-relaxed">{{ $item['label'] }}</span>
                @if ($item['active'])
                    <span class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-1 rounded-full bg-teal-500"></span>
                @endif
            </a>
        @endforeach

        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-drawer'))"
               class="relative flex flex-col items-center justify-center gap-0.5 min-w-0 min-h-[44px] flex-1 h-full transition-colors duration-200 text-gray-500 hover:text-gray-700"
               aria-label="{{ __('web_app.actions.more') }}" aria-haspopup="dialog">
            <i aria-hidden="true" class="ph ph-dots-three-circle text-[22px]"></i>
            <span class="text-[11px] font-bold leading-relaxed">{{ __('web_app.actions.more') }}</span>
        </button>

        <div class="relative flex flex-col items-center justify-center min-w-0 flex-1 h-full">
            <button
                onclick="window.dispatchEvent(new CustomEvent('open-wizard'))"
                class="w-12 h-12 -mt-5 rounded-2xl flex items-center justify-center shadow-lg shadow-teal-500/25 transition-all duration-200 active:scale-95"
                style="background: linear-gradient(135deg, #0d9488, #0f766e);"
                aria-label="{{ __('servant.record_visit') }}">
                <i aria-hidden="true" class="ph-bold ph-plus text-white text-xl"></i>
            </button>
            <span class="text-[11px] font-bold text-teal-600 mt-0.5 leading-relaxed">{{ __('servant.nav_visit') }}</span>
        </div>
    </nav>
</div>
