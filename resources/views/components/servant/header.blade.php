<header class="sticky top-0 z-40 lg:hidden">
    <div class="px-4 py-3 flex items-center justify-between"
         style="background: rgba(255,255,255,0.92); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-bottom: 1px solid rgba(0,0,0,0.06); padding-top: max(0.75rem, env(safe-area-inset-top));">

        <div class="flex items-center gap-2">
            <span class="text-lg font-extrabold text-teal-800">{{ $title ?? 'الرئيسية' }}</span>
        </div>

        <div class="flex items-center gap-2">
            @livewire('servant.notifications-bell')
        </div>
    </div>
</header>
