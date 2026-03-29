<div x-data="{ open: false }" class="relative flex items-center" wire:poll.60s="loadNotifications">

    {{-- زر الجرس --}}
    <button
        @click="open = !open"
        type="button"
        class="relative flex items-center justify-center w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition focus:outline-none"
    >
        <x-heroicon-o-bell class="w-6 h-6 text-gray-500 dark:text-gray-400" />

        @if($unreadCount > 0)
            <span class="absolute top-1 end-1 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- الـ Dropdown --}}
    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute top-12 end-0 z-50 w-80 bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
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
                    class="text-xs font-medium hover:opacity-80 transition"
                    style="color: #0073A3;"
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
                    class="flex gap-3 px-4 py-3 cursor-pointer transition
                        {{ $notification['read']
                            ? 'hover:bg-gray-50 dark:hover:bg-gray-800'
                            : 'bg-blue-50/50 dark:bg-blue-900/10 border-s-2'
                        }}"
                    style="{{ !$notification['read'] ? 'border-color: #0073A3;' : '' }}"
                >
                    {{-- أيقونة النوع --}}
                    <div class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-full text-base
                        {{ match($notification['type']) {
                            'birthday'        => 'bg-amber-100',
                            'critical_case'   => 'bg-red-100',
                            'visit_reminder'  => 'bg-blue-100',
                            'unvisited_alert' => 'bg-amber-100',
                            'new_beneficiary' => 'bg-green-100',
                            default           => 'bg-gray-100',
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
                        <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full self-start" style="background-color: #0073A3;"></div>
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
                class="text-xs font-medium hover:opacity-80 transition"
                style="color: #0073A3;"
            >
                {{ __('notifications.title') }} ←
            </a>
        </div>
    </div>
</div>
