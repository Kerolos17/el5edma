<div x-data="{ open: false }" class="relative flex items-center" wire:poll.60s="loadNotifications"
     @keydown.escape.window="open = false">

    {{-- زر الجرس --}}
    <button
        @click="open = !open"
        type="button"
        aria-haspopup="true"
        :aria-expanded="open.toString()"
        aria-controls="filament-notifications-menu"
        aria-label="{{ __('notifications.title') }}"
        class="relative flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition focus:outline-none focus-visible:ring-2"
    >
        <x-heroicon-o-bell class="w-6 h-6 text-gray-500 dark:text-gray-400" />

        @if($unreadCount > 0)
            <span class="absolute top-1 end-1 flex items-center justify-center min-w-5 h-5 px-1 text-[11px] font-bold text-white bg-red-500 rounded-full" aria-hidden="true">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
            <span class="sr-only">{{ __('notifications.unread_count', ['count' => $unreadCount]) }}</span>
        @endif
    </button>

    {{-- الـ Dropdown --}}
    <div
        id="filament-notifications-menu"
        role="menu"
        aria-label="{{ __('notifications.title') }}"
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute top-12 end-0 z-50 w-[min(20rem,calc(100vw-2rem))] bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800">
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ __('notifications.title') }}
                @if($unreadCount > 0)
                    <span class="ms-1 px-2 py-0.5 text-xs bg-red-100 text-red-600 rounded-full">
                        {{ $unreadCount }}
                    </span>
                @endif
            </span>

            @if($unreadCount > 0)
                <button
                    wire:click="markAllRead"
                    class="text-xs font-medium hover:opacity-80 transition min-h-[44px] px-2 text-[#0073A3] dark:text-sky-400"
                >
                    {{ __('notifications.mark_all_read') }}
                </button>
            @endif
        </div>

        {{-- قائمة الإشعارات --}}
        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($notifications as $notification)
                <div
                    wire:key="notification-{{ $notification['id'] }}"
                    wire:click="markRead({{ $notification['id'] }})"
                    role="button" tabindex="0"
                    @keydown.enter="$wire.markRead({{ $notification['id'] }})"
                    @keydown.space.prevent="$wire.markRead({{ $notification['id'] }})"
                    aria-label="{{ $notification['title'] }} — {{ __('notifications.mark_read') }}"
                    title="{{ __('notifications.view_all') }}"
                    class="flex gap-3 px-4 py-3 cursor-pointer transition
                        {{ $notification['read']
                            ? 'hover:bg-gray-50 dark:hover:bg-gray-800'
                            : 'bg-blue-50/50 dark:bg-blue-900/10 border-s-2 border-[#0073A3] dark:border-sky-400'
                        }}"
                >
                    {{-- أيقونة النوع --}}
                    <div class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-full text-base
                        {{ match($notification['type']) {
                            'birthday'        => 'bg-amber-100 dark:bg-amber-900/40',
                            'critical_case'   => 'bg-red-100 dark:bg-red-900/40',
                            'visit_reminder'  => 'bg-blue-100 dark:bg-blue-900/40',
                            'unvisited_alert' => 'bg-amber-100 dark:bg-amber-900/40',
                            'new_beneficiary' => 'bg-green-100 dark:bg-green-900/40',
                            default           => 'bg-gray-100 dark:bg-gray-800',
                        } }}
                    ">
                        @switch($notification['type'])
                            @case('birthday')        🎂 @break
                            @case('critical_case')   🔴 @break
                            @case('visit_reminder')  📅 @break
                            @case('unvisited_alert') ⏰ @break
                            @case('new_beneficiary') ✨ @break
                            @default                 🔔 @break
                        @endswitch
                    </div>

                    {{-- المحتوى --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                            {{ $notification['title'] }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                            {{ $notification['body'] }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $notification['time'] }}
                        </p>
                    </div>

                    @if(! $notification['read'])
                        <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full self-start bg-[#0073A3] dark:bg-sky-400"></div>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-8 text-gray-400">
                    <x-heroicon-o-bell-slash class="w-8 h-8 mb-2" />
                    <p class="text-sm">{{ __('notifications.no_notifications') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-800 text-center">
             <a href="{{ route('filament.admin.resources.ministry-notifications.index') }}"
                class="text-xs font-medium hover:opacity-80 transition inline-flex items-center gap-1 min-h-[44px] px-2 text-[#0073A3] dark:text-sky-400"
            >
                {{ __('notifications.title') }}
                <x-heroicon-o-arrow-left class="w-3.5 h-3.5 rtl:rotate-0 ltr:rotate-180" />
            </a>
        </div>
    </div>
</div>
