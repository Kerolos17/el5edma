<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    {{-- Page Title --}}
    <div class="reveal-card">
        <h1 class="text-xl font-bold text-teal-900">المخدومون</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $beneficiaries->total() }} مخدوم</p>
    </div>

    {{-- Search --}}
    <div class="relative reveal-card" style="animation-delay: 0.06s">
        <i class="ph ph-magnifying-glass absolute top-1/2 -translate-y-1/2 right-4 text-gray-400 text-lg pointer-events-none"></i>
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="ابحث بالاسم أو الكود أو الهاتف..."
            class="search-input"
            style="padding-right: 44px;"
            aria-label="بحث عن مخدوم">
    </div>

    {{-- Filter Chips --}}
    <div class="flex gap-2 overflow-x-auto pb-1 reveal-card" style="animation-delay: 0.1s; scrollbar-width: none;"
         role="group" aria-label="فلتر المخدومين">
        @foreach([['all','الكل'], ['mine','مخدوميّ'], ['recent','الأحدث']] as [$val, $label])
            <button wire:click="$set('filter', '{{ $val }}')"
                    class="radio-chip flex-shrink-0 {{ $filter === $val ? 'selected' : '' }}"
                    aria-pressed="{{ $filter === $val ? 'true' : 'false' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Beneficiaries List --}}

    {{-- Skeleton: shown during search / filter / pagination round-trips --}}
    <div wire:loading.delay class="space-y-3" aria-hidden="true">
        @for ($i = 0; $i < 6; $i++)
            <div class="skeleton-shimmer rounded-2xl" style="height:76px;"></div>
        @endfor
    </div>

    {{-- Actual list: hidden while loading --}}
    <div wire:loading.remove class="space-y-3">
        @forelse($beneficiaries as $beneficiary)
            <x-ui.beneficiary-card
                :beneficiary="$beneficiary"
                :href="route('servant.beneficiaries.show', $beneficiary)"
            />
        @empty
            <x-ui.empty-state
                icon="ph-users"
                message="لا يوجد مخدومون"
                size="lg"
            >
                @if($search)
                    <x-slot name="action">
                        <button wire:click="$set('search', '')"
                                class="text-sm text-teal-500 font-semibold">
                            مسح البحث
                        </button>
                    </x-slot>
                @endif
            </x-ui.empty-state>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div wire:loading.remove>
        <x-ui.pagination :paginator="$beneficiaries" />
    </div>

</div>
