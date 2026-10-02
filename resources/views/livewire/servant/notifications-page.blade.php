<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5"
     wire:poll.60000ms.visible="refresh">

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-teal-900">الإشعارات</h1>
        @if ($unreadCount > 0)
            <button type="button" wire:click="markAllRead"
                class="text-xs font-bold text-teal-600 min-h-[44px] px-3">
                تحديد الكل كمقروء ({{ $unreadCount }})
            </button>
        @endif
    </div>

    {{-- Search + filters --}}
    <div class="s-card p-3 space-y-3">
        <label class="flex items-center gap-2 rounded-xl bg-gray-50 px-3 min-h-[44px]">
            <i class="ph ph-magnifying-glass text-gray-400" aria-hidden="true"></i>
            <input wire:model.live.debounce.300ms="search" type="search" enterkeyhint="search"
                placeholder="ابحث في الإشعارات..." class="bg-transparent flex-1 outline-none text-sm">
        </label>
        <div class="flex gap-2 overflow-x-auto">
            @foreach ([
                'all' => 'الكل',
                'unread' => 'غير مقروء',
                'critical_case' => 'حرجة',
                'visit_reminder' => 'تذكيرات',
                'birthday' => 'أعياد ميلاد',
            ] as $value => $label)
                <button type="button" wire:click="$set('filter', '{{ $value }}')"
                    class="px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap min-h-[36px] {{ $filter === $value ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Notifications list --}}
    <div class="space-y-2.5" wire:loading.attr="aria-busy" wire:target="search,filter">
        @forelse ($notifications as $notification)
            <article class="s-card p-4 {{ $notification->read_at ? '' : 'ring-1 ring-teal-200' }}">
                <button type="button" wire:click="markReadAndRedirect({{ $notification->id }})"
                    class="w-full text-start" aria-label="{{ $notification->display_title }}">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-bold text-sm text-teal-900">{{ $notification->display_title }}</p>
                        @if (! $notification->read_at)
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-500 flex-shrink-0 mt-1" aria-label="غير مقروء"></span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-600 mt-1 leading-relaxed">{{ $notification->body }}</p>
                    <p class="text-[11px] text-gray-400 mt-2">{{ $notification->created_at->isoFormat('LLL') }}</p>
                </button>
                @if (! $notification->read_at)
                    <button type="button" wire:click="markRead({{ $notification->id }})"
                        class="text-[11px] font-bold text-gray-400 hover:text-teal-600 mt-2 min-h-[32px]">
                        تحديد كمقروء
                    </button>
                @endif
            </article>
        @empty
            <div class="s-card p-8 text-center">
                <i class="ph ph-bell-slash text-3xl text-gray-300" aria-hidden="true"></i>
                <p class="text-sm text-gray-500 mt-2">لا توجد إشعارات هنا.</p>
            </div>
        @endforelse
    </div>

    @if ($notifications instanceof \Illuminate\Contracts\Pagination\Paginator && $notifications->hasPages())
        <div class="app-pagination-wrap">
            {{ $notifications->onEachSide(1)->links() }}
        </div>
    @endif
</div>
