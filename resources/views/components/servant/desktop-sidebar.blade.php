@php
    $isDashboard   = request()->routeIs('servant.dashboard');
    $isBeneficiary = request()->routeIs('servant.beneficiaries');
    $isVisits      = request()->routeIs('servant.visits');
    $isProfile     = request()->routeIs('servant.profile');

    $isScheduledVisits = request()->routeIs('servant.scheduled-visits');

    $nav = [
        ['route' => 'servant.dashboard',        'icon' => 'ph-house-simple',   'label' => 'الرئيسية',      'active' => $isDashboard],
        ['route' => 'servant.beneficiaries',    'icon' => 'ph-users',           'label' => 'المخدومون',     'active' => $isBeneficiary],
        ['route' => 'servant.visits',           'icon' => 'ph-calendar-check',  'label' => 'الزيارات',      'active' => $isVisits],
        ['route' => 'servant.scheduled-visits', 'icon' => 'ph-calendar-dots',   'label' => 'مجدولة',        'active' => $isScheduledVisits],
        ['route' => 'servant.profile',          'icon' => 'ph-user-circle',     'label' => 'حسابي',         'active' => $isProfile],
    ];
@endphp

<aside class="hidden lg:flex flex-col fixed top-0 right-0 h-full w-64 z-50 gradient-deep">
    {{-- Logo --}}
    <div class="px-6 py-7 border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0">
                <i class="ph-bold ph-cross text-white text-xl"></i>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-4 py-6 space-y-1">
        @foreach($nav as $item)
            <a href="{{ route($item['route']) }}" wire:navigate
               class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-200 group
               {{ $item['active']
                    ? 'bg-white/15 text-white'
                    : 'text-white/60 hover:bg-white/[8%] hover:text-white' }}">
                <i class="{{ $item['active'] ? 'ph-fill' : 'ph' }} {{ $item['icon'] }} text-xl flex-shrink-0"></i>
                <span class="font-semibold text-sm">{{ $item['label'] }}</span>
                @if($item['active'])
                    <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-300"></span>
                @endif
            </a>
        @endforeach
    </nav>

    {{-- New Visit Button --}}
    <div class="px-4 pb-4">
        <button onclick="Livewire.dispatch('open-wizard')"
                class="w-full py-3.5 rounded-2xl flex items-center justify-center gap-2 font-bold text-sm btn-ripple transition-all duration-200"
                style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.2);">
            <i class="ph-bold ph-calendar-plus text-lg"></i>
            تسجيل زيارة جديدة
        </button>
    </div>

    {{-- User Info --}}
    <div class="px-5 py-5 border-t border-white/10">
        <div class="flex items-center gap-3">
            @php $user = auth()->user(); @endphp
            <div class="w-10 h-10 rounded-full overflow-hidden avatar-ring flex-shrink-0">
                @if($user->profile_photo_url)
                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-teal-800 font-bold text-base"
                         style="background: linear-gradient(135deg, #F7BB86, #F4A261);">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white font-bold text-sm truncate">{{ $user->name }}</p>
                <p class="text-white/50 text-xs truncate">{{ $user->role->label() }}</p>
            </div>
        </div>
    </div>
</aside>
