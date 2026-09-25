<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    {{-- Page Title --}}
    <div>
        <h1 class="text-xl font-bold text-teal-900">الزيارات</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $visits->total() }} زيارة</p>
    </div>

    {{-- Search --}}
    <div class="relative">
        <i class="ph ph-magnifying-glass absolute top-1/2 -translate-y-1/2 text-gray-500 text-lg pointer-events-none search-input__icon" aria-hidden="true"></i>
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="ابحث باسم المخدوم أو الكود..."
            class="search-input search-input--padded"
            aria-label="بحث في الزيارات">
    </div>

    {{-- Filter Chips --}}
    <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar"
         role="group" aria-label="فلتر الزيارات">
        @foreach([['all','الكل'], ['month','هذا الشهر'], ['critical','حرجة']] as [$val, $label])
            <button wire:click="$set('filter', '{{ $val }}')"
                    class="radio-chip flex-shrink-0 {{ $filter === $val ? 'selected' : '' }}"
                    aria-pressed="{{ $filter === $val ? 'true' : 'false' }}">
                @if($val === 'critical')
                    <i class="ph-fill ph-warning text-sm {{ $filter === 'critical' ? 'text-red-500' : 'text-gray-500' }}"
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
            <div class="skeleton-shimmer skeleton-row rounded-2xl"></div>
        @endfor
    </div>

    <div wire:loading.remove class="space-y-3">
        @forelse($visits as $visit)
            <div class="s-card rounded-2xl px-4 py-3 flex items-start gap-3
                        {{ $visit->is_critical ? 'border-s-4 border-red-400' : '' }}"
                 role="article"
                 aria-label="زيارة {{ $visit->beneficiary?->full_name ?? 'محذوف' }}">

                {{-- Date column --}}
                <div class="text-center flex-shrink-0 w-12" aria-hidden="true">
                    <p class="accent-font text-2xl font-bold text-teal-700 leading-none">
                        {{ $visit->visit_date->format('d') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
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
                            <span class="badge-pill badge-info text-xs px-2 py-0.5">{{ in_array($visit->type, ['home_visit', 'phone_call', 'church_meeting'], true) ? __("visits.$visit->type") : $visit->type }}</span>
                        @endif
                        @if($visit->duration_minutes)
                            <span class="text-xs text-gray-500">
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
