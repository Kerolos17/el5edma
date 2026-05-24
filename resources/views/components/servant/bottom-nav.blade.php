@php
    $isDashboard       = request()->routeIs('servant.dashboard');
    $isBeneficiary     = request()->routeIs('servant.beneficiaries');
    $isVisits          = request()->routeIs('servant.visits');
    $isScheduledVisits = request()->routeIs('servant.scheduled-visits');
    $isProfile         = request()->routeIs('servant.profile');
@endphp

<div class="fixed bottom-0 left-0 right-0 lg:hidden z-50">

    {{-- Nav bar background --}}
    <div class="relative" style="background: rgba(255,251,247,0.96); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-top: 1px solid rgba(0,109,119,0.10); padding-bottom: env(safe-area-inset-bottom);">

        <div class="flex items-end justify-around px-2 pt-2 pb-3">

            {{-- Dashboard --}}
            <a href="{{ route('servant.dashboard') }}" wire:navigate
               class="flex flex-col items-center gap-1 px-3 py-1 transition-all duration-200 {{ $isDashboard ? 'text-teal-600' : 'text-gray-400' }}">
                <i class="{{ $isDashboard ? 'ph-fill' : 'ph' }} ph-house-simple text-2xl"></i>
                <span class="text-xs font-semibold">الرئيسية</span>
                @if($isDashboard)
                    <span class="w-1 h-1 rounded-full bg-teal-500 mt-0.5"></span>
                @endif
            </a>

            {{-- Beneficiaries --}}
            <a href="{{ route('servant.beneficiaries') }}" wire:navigate
               class="flex flex-col items-center gap-1 px-3 py-1 transition-all duration-200 {{ $isBeneficiary ? 'text-teal-600' : 'text-gray-400' }}">
                <i class="{{ $isBeneficiary ? 'ph-fill' : 'ph' }} ph-users text-2xl"></i>
                <span class="text-xs font-semibold">المخدومون</span>
                @if($isBeneficiary)
                    <span class="w-1 h-1 rounded-full bg-teal-500 mt-0.5"></span>
                @endif
            </a>

            {{-- FAB — New Visit --}}
            <div class="relative flex flex-col items-center" style="margin-top: -28px;">
                <button
                    onclick="Livewire.dispatch('open-wizard')"
                    class="w-14 h-14 rounded-full flex items-center justify-center btn-ripple shadow-lg transition-transform duration-200 active:scale-95"
                    style="background: linear-gradient(135deg, #006D77 0%, #003942 100%); box-shadow: 0 8px 24px rgba(0,109,119,0.35);"
                    aria-label="تسجيل زيارة جديدة">
                    <i class="ph-bold ph-plus text-white text-2xl"></i>
                </button>
                <span class="text-xs font-semibold text-teal-700 mt-1">زيارة</span>
            </div>

            {{-- Visits --}}
            <a href="{{ route('servant.visits') }}" wire:navigate
               class="flex flex-col items-center gap-1 px-3 py-1 transition-all duration-200 {{ $isVisits ? 'text-teal-600' : 'text-gray-400' }}">
                <i class="{{ $isVisits ? 'ph-fill' : 'ph' }} ph-calendar-check text-2xl"></i>
                <span class="text-xs font-semibold">الزيارات</span>
                @if($isVisits)
                    <span class="w-1 h-1 rounded-full bg-teal-500 mt-0.5"></span>
                @endif
            </a>

            {{-- Scheduled Visits --}}
            <a href="{{ route('servant.scheduled-visits') }}" wire:navigate
               class="flex flex-col items-center gap-1 px-3 py-1 transition-all duration-200 {{ $isScheduledVisits ? 'text-teal-600' : 'text-gray-400' }}"
               aria-label="الزيارات المجدولة">
                <i class="{{ $isScheduledVisits ? 'ph-fill' : 'ph' }} ph-calendar-dots text-2xl"></i>
                <span class="text-xs font-semibold">مجدولة</span>
                @if($isScheduledVisits)
                    <span class="w-1 h-1 rounded-full bg-teal-500 mt-0.5"></span>
                @endif
            </a>

            {{-- Profile --}}
            <a href="{{ route('servant.profile') }}" wire:navigate
               class="flex flex-col items-center gap-1 px-3 py-1 transition-all duration-200 {{ $isProfile ? 'text-teal-600' : 'text-gray-400' }}">
                <i class="{{ $isProfile ? 'ph-fill' : 'ph' }} ph-user-circle text-2xl"></i>
                <span class="text-xs font-semibold">حسابي</span>
                @if($isProfile)
                    <span class="w-1 h-1 rounded-full bg-teal-500 mt-0.5"></span>
                @endif
            </a>
        </div>
    </div>

</div>
