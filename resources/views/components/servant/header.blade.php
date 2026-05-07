<header class="sticky top-0 z-40 lg:hidden">
    <div class="px-4 py-3 flex items-center justify-between"
         style="background: rgba(255,251,247,0.92); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-bottom: 1px solid rgba(0,109,119,0.08); padding-top: max(0.75rem, env(safe-area-inset-top));">

        {{-- Right: Logo + Greeting --}}
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background: linear-gradient(135deg, #006D77, #003942);"
                 aria-hidden="true">
                <i class="ph-bold ph-cross" style="color:white; font-size:18px;"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 leading-none" aria-hidden="true">مرحباً</p>
                <p class="font-bold text-teal-900 text-sm leading-tight">{{ auth()->user()->name }}</p>
            </div>
        </div>

        {{-- Left: Notification Bell --}}
        <div class="flex items-center gap-2" role="complementary" aria-label="الإشعارات">
            @livewire('servant.notifications-bell')
        </div>
    </div>
</header>
