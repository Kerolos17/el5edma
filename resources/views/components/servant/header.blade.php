@php
    $nav = [
        ['route' => 'servant.dashboard',        'icon' => 'ph-house-simple',   'label' => 'الرئيسية'],
        ['route' => 'servant.beneficiaries',    'icon' => 'ph-users',           'label' => 'المخدومون'],
        ['route' => 'servant.visits',           'icon' => 'ph-calendar-check',  'label' => 'الزيارات'],
        ['route' => 'servant.scheduled-visits', 'icon' => 'ph-calendar-dots',   'label' => 'مجدولة'],
        ['route' => 'servant.prayer-requests',  'icon' => 'ph-hands-praying',   'label' => 'طلبات الصلاة'],
        ['route' => 'servant.medical-files',    'icon' => 'ph-file-lock',       'label' => 'الملفات الطبية'],
        ['route' => 'servant.profile',          'icon' => 'ph-user-circle',     'label' => 'حسابي'],
    ];
    $user = auth()->user();
@endphp

<header class="sticky top-0 z-40 lg:hidden" x-data="{ drawer: false }">
    <div class="px-4 py-3 flex items-center justify-between gap-3"
         style="background: rgba(255,255,255,0.92); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-bottom: 1px solid rgba(0,0,0,0.06); padding-top: max(0.75rem, env(safe-area-inset-top));">

        {{-- Hamburger --}}
        <button @click="drawer = true" class="w-9 h-9 flex items-center justify-center rounded-xl text-gray-600 hover:bg-gray-100 transition-colors" aria-label="القائمة">
            <i class="ph ph-list text-xl"></i>
        </button>

        {{-- Search + Notifications --}}
        <div class="flex items-center gap-1">
            <a href="{{ route('servant.beneficiaries') }}" wire:navigate class="w-9 h-9 flex items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 transition-colors" aria-label="بحث">
                <i class="ph ph-magnifying-glass text-lg"></i>
            </a>
            @livewire('servant.notifications-bell')
        </div>
    </div>

    {{-- Backdrop --}}
    <div x-cloak x-show="drawer" x-transition.opacity @click="drawer = false"
         class="fixed inset-0 bg-black/30 z-50" aria-hidden="true"></div>

    {{-- Drawer --}}
    <div x-cloak x-show="drawer"
         x-transition:enter="transition-transform duration-300 ease-out"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200 ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 h-full w-72 max-w-[85vw] z-50 shadow-2xl overflow-y-auto"
         style="background: linear-gradient(180deg, #0d555c 0%, #003942 100%);">

        {{-- User Info --}}
        <div class="px-5 py-6 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 ring-2 ring-white/20">
                    @if($user->profile_photo_url)
                        <img src="{{ $user->profile_photo_url }}" alt="" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-teal-800 font-bold" style="background: linear-gradient(135deg, #F7BB86, #F4A261);">
                            {{ mb_substr($user->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-white font-bold text-sm truncate">{{ $user->name }}</p>
                    <p class="text-white/50 text-xs">{{ $user->role->label() }}</p>
                </div>
            </div>
            <button @click="drawer = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-white/60 hover:text-white hover:bg-white/10" aria-label="إغلاق">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        {{-- Nav Items --}}
        <nav class="px-3 py-4 space-y-1">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" wire:navigate @click="drawer = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors {{ request()->routeIs($item['route'].'*') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/[8%] hover:text-white' }}">
                    <i class="{{ request()->routeIs($item['route'].'*') ? 'ph-fill' : 'ph' }} {{ $item['icon'] }} text-xl flex-shrink-0"></i>
                    <span class="font-semibold text-sm">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- New Visit Button --}}
        <div class="px-4 pb-4">
            <button @click="drawer = false; Livewire.dispatch('open-wizard')"
                    class="w-full py-3.5 rounded-2xl flex items-center justify-center gap-2 font-bold text-sm transition-all duration-200 active:scale-[0.98]"
                    style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.2);">
                <i class="ph-bold ph-calendar-plus text-lg"></i>
                تسجيل زيارة جديدة
            </button>
        </div>

        {{-- Logout --}}
        <div class="px-4 pb-6 mt-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full py-3 rounded-2xl flex items-center justify-center gap-2 font-bold text-sm text-white/50 hover:text-white hover:bg-white/[8%] transition-colors">
                    <i class="ph ph-sign-out text-lg"></i>
                    تسجيل خروج
                </button>
            </form>
        </div>
    </div>
</header>
