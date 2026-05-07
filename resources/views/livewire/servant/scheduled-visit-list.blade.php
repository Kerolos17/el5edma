<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    {{-- Page Title --}}
    <div class="reveal-card">
        <h1 class="text-xl font-bold text-teal-900">الزيارات المجدولة</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $scheduledVisits->count() }} زيارة</p>
    </div>

    {{-- Filter Chips --}}
    <div class="flex gap-2 overflow-x-auto pb-1 reveal-card"
         style="animation-delay: 0.06s; scrollbar-width: none;"
         role="group" aria-label="فلتر الزيارات المجدولة">
        @foreach([['upcoming','القادمة'], ['past','السابقة'], ['all','الكل']] as [$val, $label])
            <button wire:click="$set('filter', '{{ $val }}')"
                    class="radio-chip flex-shrink-0 {{ $filter === $val ? 'selected' : '' }}"
                    aria-pressed="{{ $filter === $val ? 'true' : 'false' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Skeleton --}}
    <div wire:loading.delay class="space-y-3" aria-hidden="true">
        @for ($i = 0; $i < 4; $i++)
            <div class="skeleton-shimmer rounded-2xl" style="height:88px;"></div>
        @endfor
    </div>

    {{-- List --}}
    <div wire:loading.remove class="space-y-3">
        @forelse($scheduledVisits as $sv)
            <div class="s-card card-lift rounded-2xl px-4 py-3 flex items-start gap-3"
                 role="article"
                 aria-label="زيارة مجدولة {{ $sv->beneficiary?->full_name ?? '' }}">

                {{-- Date column --}}
                <div class="text-center flex-shrink-0 w-12" aria-hidden="true">
                    <p class="text-2xl font-bold text-teal-700 leading-none" style="font-family: var(--font-accent);">
                        {{ $sv->scheduled_date->format('d') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $sv->scheduled_date->locale('ar')->isoFormat('MMM') }}
                    </p>
                </div>

                <div class="w-px self-stretch bg-gray-100 flex-shrink-0" aria-hidden="true"></div>

                <div class="flex-1 min-w-0">
                    <p class="font-bold text-teal-900 text-sm truncate">
                        {{ $sv->beneficiary?->full_name ?? 'محذوف' }}
                    </p>

                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        @if($sv->scheduled_time)
                            <span class="text-xs text-gray-500">
                                <i class="ph ph-clock text-xs" aria-hidden="true"></i>
                                {{ \Carbon\Carbon::parse($sv->scheduled_time)->format('g:i A') }}
                            </span>
                        @endif
                        <span class="text-xs text-gray-500">
                            <i class="ph ph-users-three text-xs" aria-hidden="true"></i>
                            {{ $sv->assigned_servant_names }}
                        </span>
                        <span @class([
                            'badge-pill text-xs px-2 py-0.5',
                            'badge-info'     => $sv->status === 'pending',
                            'badge-success'  => $sv->status === 'completed',
                            'badge-critical' => $sv->status === 'cancelled',
                        ])>
                            @match($sv->status)
                                'pending'   => 'قيد الانتظار',
                                'completed' => 'مكتملة',
                                'cancelled' => 'ملغاة',
                                default     => $sv->status
                            @endmatch
                        </span>
                    </div>

                    @if($sv->notes)
                        <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $sv->notes }}</p>
                    @endif
                </div>

                {{-- Cancel action --}}
                @if($sv->status === 'pending')
                    <button wire:click="cancel({{ $sv->id }})"
                            wire:confirm="هل تريد إلغاء هذه الزيارة المجدولة؟"
                            class="flex-shrink-0 w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center
                                   hover:bg-red-100 transition-colors"
                            aria-label="إلغاء الزيارة">
                        <i class="ph ph-x text-red-500 text-base" aria-hidden="true"></i>
                    </button>
                @endif
            </div>
        @empty
            <x-ui.empty-state
                icon="ph-calendar-blank"
                message="{{ $filter === 'upcoming' ? 'لا توجد زيارات مجدولة قادمة' : 'لا توجد زيارات' }}"
            />
        @endforelse
    </div>

</div>
