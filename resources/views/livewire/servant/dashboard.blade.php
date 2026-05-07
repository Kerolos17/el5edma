<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-6">

    {{-- Greeting --}}
    <div class="reveal-card">
        <h1 class="text-2xl font-bold text-teal-900" style="font-family: var(--font-display);">
            مرحباً، {{ auth()->user()->name }}
        </h1>
        <p class="text-sm text-gray-400 mt-1">
            {{ now()->locale('ar')->isoFormat('dddd، D MMMM YYYY') }}
        </p>
    </div>

    {{-- Stats Grid --}}
    <div wire:loading.delay wire:target="refresh" class="grid grid-cols-2 gap-3" aria-hidden="true">
        @for ($i = 0; $i < 4; $i++)
            <div class="skeleton-shimmer rounded-2xl" style="height:100px;"></div>
        @endfor
    </div>
    <div wire:loading.remove wire:target="refresh" class="grid grid-cols-2 gap-3 reveal-card" style="animation-delay: 0.08s">
        <x-ui.stat-card
            label="مخدوميّ"
            :value="$myBeneficiariesCount"
            icon="ph-fill ph-users"
            gradient="teal"
        />
        <x-ui.stat-card
            label="زيارات الشهر"
            :value="$visitsThisMonth"
            icon="ph-fill ph-calendar-check"
            gradient="gold"
        />
        <x-ui.stat-card
            label="مجدولة قادمة"
            :value="$scheduledCount"
            icon="ph-fill ph-clock"
            gradient="teal-light"
        />
        <x-ui.stat-card
            label="حالات حرجة"
            :value="$criticalCount"
            icon="ph-fill ph-warning"
            :gradient="$criticalCount > 0 ? 'critical' : 'success'"
            :pulse="$criticalCount > 0"
        />
    </div>

    {{-- Recent Visits --}}
    <div class="reveal-card" style="animation-delay: 0.16s">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-teal-900">آخر الزيارات</h2>
            <a href="{{ route('servant.visits') }}" wire:navigate
               class="text-sm font-semibold text-teal-500 hover:text-teal-700 transition-colors">
                عرض الكل
            </a>
        </div>

        {{-- Skeleton: visit rows --}}
        <div wire:loading.delay wire:target="refresh" class="space-y-3" aria-hidden="true">
            @for ($i = 0; $i < 3; $i++)
                <div class="skeleton-shimmer rounded-2xl" style="height:68px;"></div>
            @endfor
        </div>

        @if($recentVisits->isEmpty())
            <div wire:loading.remove wire:target="refresh">
                <x-ui.empty-state icon="ph-calendar-blank" message="لم تُسجَّل زيارات بعد" />
            </div>
        @else
            <div wire:loading.remove wire:target="refresh" class="space-y-3">
                @foreach($recentVisits as $visit)
                    <div class="s-card card-lift rounded-2xl px-4 py-3 flex items-center gap-3">
                        <x-ui.avatar
                            :name="$visit->beneficiary?->full_name ?? '؟'"
                            size="md"
                            shape="square"
                        />

                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-teal-900 text-sm truncate">
                                {{ $visit->beneficiary?->full_name ?? 'محذوف' }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $visit->visit_date->locale('ar')->isoFormat('D MMM') }}
                                @if($visit->type)
                                    · {{ $visit->type }}
                                @endif
                            </p>
                        </div>

                        @if($visit->is_critical)
                            <span class="badge-pill badge-critical text-xs px-2 py-1 critical-indicator">
                                <i class="ph-fill ph-warning text-xs"></i>
                                حرجة
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
