<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    {{-- Page Title --}}
    <div class="reveal-card">
        <h1 class="text-xl font-bold text-teal-900">الزيارات</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $visits->total() }} زيارة</p>
    </div>

    {{-- Filter Chips --}}
    <div class="flex gap-2 overflow-x-auto pb-1 reveal-card"
         style="animation-delay: 0.06s; scrollbar-width: none;"
         role="group" aria-label="فلتر الزيارات">
        @foreach([['all','الكل'], ['month','هذا الشهر'], ['critical','حرجة']] as [$val, $label])
            <button wire:click="$set('filter', '{{ $val }}')"
                    class="radio-chip flex-shrink-0 {{ $filter === $val ? 'selected' : '' }}"
                    aria-pressed="{{ $filter === $val ? 'true' : 'false' }}">
                @if($val === 'critical')
                    <i class="ph-fill ph-warning text-sm {{ $filter === 'critical' ? 'text-red-500' : 'text-gray-400' }}"
                       aria-hidden="true"></i>
                @endif
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Visit Timeline --}}

    {{-- Skeleton: shown during filter / pagination round-trips --}}
    <div wire:loading.delay class="space-y-3" aria-hidden="true">
        @for ($i = 0; $i < 5; $i++)
            <div class="skeleton-shimmer rounded-2xl" style="height:84px;"></div>
        @endfor
    </div>

    <div wire:loading.remove class="space-y-3">
        @forelse($visits as $visit)
            <div class="s-card card-lift rounded-2xl px-4 py-3 flex items-start gap-3
                        {{ $visit->is_critical ? 'border-r-4 border-red-400' : '' }}"
                 role="article"
                 aria-label="زيارة {{ $visit->beneficiary?->full_name ?? 'محذوف' }}">

                {{-- Date column --}}
                <div class="text-center flex-shrink-0 w-12" aria-hidden="true">
                    <p class="text-2xl font-bold text-teal-700 leading-none" style="font-family: var(--font-accent);">
                        {{ $visit->visit_date->format('d') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $visit->visit_date->locale('ar')->isoFormat('MMM') }}
                    </p>
                </div>

                <div class="w-px self-stretch bg-gray-100 flex-shrink-0" aria-hidden="true"></div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-bold text-teal-900 text-sm truncate">
                            {{ $visit->beneficiary?->full_name ?? 'محذوف' }}
                        </p>
                        @if($visit->is_critical)
                            <span class="badge-pill badge-critical text-xs px-2 py-0.5 critical-indicator">
                                <i class="ph-fill ph-warning text-xs" aria-hidden="true"></i>
                                <span>حرجة</span>
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        @if($visit->type)
                            <span class="badge-pill badge-info text-xs px-2 py-0.5">{{ $visit->type }}</span>
                        @endif
                        @if($visit->duration_minutes)
                            <span class="text-xs text-gray-400">
                                <i class="ph ph-clock text-xs" aria-hidden="true"></i>
                                {{ $visit->duration_minutes }} دقيقة
                            </span>
                        @endif
                    </div>

                    @if($visit->feedback)
                        <p class="text-xs text-gray-500 mt-1.5 line-clamp-2">{{ $visit->feedback }}</p>
                    @endif
                </div>
            </div>
        @empty
            <x-ui.empty-state icon="ph-calendar-blank" message="لا توجد زيارات مسجلة" size="lg" />
        @endforelse
    </div>

    {{-- Pagination --}}
    <div wire:loading.remove>
        <x-ui.pagination :paginator="$visits" />
    </div>

</div>
