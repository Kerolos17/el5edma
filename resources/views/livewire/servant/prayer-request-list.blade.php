<div class="px-4 pt-6 pb-32 lg:pb-10 space-y-5">

    {{-- Page Title + FAB --}}
    <div class="reveal-card flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-teal-900">{{ __('servant.prayer_title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('servant.prayer_count', ['count' => $prayerRequests->total()]) }}</p>
        </div>
        <button wire:click="openForm"
                class="w-11 h-11 min-w-[44px] min-h-[44px] rounded-2xl gradient-deep flex items-center justify-center shadow-lg text-white"
                aria-label="{{ __('servant.add_prayer') }}">
            <i class="ph-bold ph-plus text-lg" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Filter Chips --}}
    <div class="flex gap-2 overflow-x-auto pb-1 reveal-card"
         style="animation-delay:0.06s; scrollbar-width:none;"
         role="group" aria-label="{{ __('servant.filter_prayer') }}">
        @foreach([['open', __('servant.filter_open')], ['answered', __('servant.filter_answered')], ['closed', __('servant.filter_closed')], ['all', __('servant.filter_all')]] as [$val, $label])
            <button wire:click="$set('filter', '{{ $val }}')"
                    class="radio-chip flex-shrink-0 {{ $filter === $val ? 'selected' : '' }}"
                    aria-pressed="{{ $filter === $val ? 'true' : 'false' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Create Form --}}
    @if($showForm)
        <div class="s-card rounded-2xl p-5 space-y-4 reveal-card" role="region" aria-label="{{ __('servant.new_prayer_form') }}">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-teal-900">{{ __('servant.new_prayer') }}</h2>
                <button wire:click="closeForm"
                        class="w-11 h-11 min-w-[44px] min-h-[44px] rounded-xl bg-gray-100 flex items-center justify-center"
                        aria-label="{{ __('servant.close_form') }}">
                    <i class="ph ph-x text-gray-500" aria-hidden="true"></i>
                </button>
            </div>

            <div>
                <label class="block text-sm font-semibold text-teal-900 mb-1.5">
                    {{ __('servant.prayer_beneficiary') }} <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select wire:model="beneficiaryId"
                        class="w-full rounded-xl border border-gray-200 px-3 min-h-[44px] py-2.5 text-sm bg-white
                               focus:ring-2 focus:ring-teal-400 focus:outline-none"
                        aria-required="true">
                    <option value="">{{ __('servant.select_beneficiary') }}</option>
                    @foreach($myBeneficiaries as $b)
                        <option value="{{ $b->id }}">{{ $b->full_name }}</option>
                    @endforeach
                </select>
                @error('beneficiaryId') <p class="text-xs text-red-500 mt-1" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-teal-900 mb-1.5">
                    {{ __('servant.topic_title') }} <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input wire:model.live.debounce.300ms="title"
                       type="text"
                       placeholder="{{ __('servant.topic_placeholder') }}"
                       maxlength="255"
                       class="w-full rounded-xl border border-gray-200 px-3 min-h-[44px] py-2.5 text-sm
                              focus:ring-2 focus:ring-teal-400 focus:outline-none"
                       aria-required="true" />
                @error('title') <p class="text-xs text-red-500 mt-1" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-teal-900 mb-1.5">{{ __('servant.prayer_topic') }}</label>
                <textarea wire:model="body"
                          rows="3"
                          placeholder="{{ __('servant.details_placeholder') }}"
                          maxlength="2000"
                          class="w-full rounded-xl border border-gray-200 px-3 min-h-[44px] py-2.5 text-sm
                                 focus:ring-2 focus:ring-teal-400 focus:outline-none resize-none"></textarea>
            </div>

            <button wire:click="save"
                    wire:loading.attr="disabled"
                    class="w-full py-3 min-h-[44px] rounded-2xl gradient-deep text-white font-bold text-sm
                           shadow-lg disabled:opacity-60 transition-opacity">
                <span wire:loading.remove wire:target="save">{{ __('servant.save_prayer') }}</span>
                <span wire:loading wire:target="save">{{ __('servant.saving') }}</span>
            </button>
        </div>
    @endif

    {{-- Skeleton --}}
    <div wire:loading.delay class="space-y-3" aria-hidden="true">
        @for ($i = 0; $i < 4; $i++)
            <div class="skeleton-shimmer rounded-2xl" style="height:80px;"></div>
        @endfor
    </div>

    {{-- Prayer Request Cards --}}
    <div wire:loading.remove class="space-y-3">
        @forelse($prayerRequests as $pr)
            <div class="s-card card-lift rounded-2xl px-4 py-3" role="article">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-teal-900 text-sm truncate">{{ $pr->title }}</p>
                        <p class="text-xs text-teal-700 mt-0.5">{{ $pr->beneficiary?->full_name ?? '' }}</p>
                        @if($pr->body)
                            <p class="text-xs text-gray-500 mt-1.5 line-clamp-2">{{ $pr->body }}</p>
                        @endif
                    </div>
                    <span @class([
                        'badge-pill text-xs px-2 py-0.5 flex-shrink-0',
                        'badge-info'    => $pr->status === 'open',
                        'badge-success' => $pr->status === 'answered',
                        'bg-gray-100 text-gray-500' => $pr->status === 'closed',
                    ])>
                        @match($pr->status)
                            'open'     => __('servant.prayer_open'),
                            'answered' => __('servant.prayer_answered'),
                            'closed'   => __('servant.prayer_closed'),
                            default    => $pr->status
                        @endmatch
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="ph ph-clock text-xs" aria-hidden="true"></i>
                    {{ $pr->created_at->locale('ar')->diffForHumans() }}
                </p>
            </div>
        @empty
            <x-ui.empty-state
                icon="ph-hands-praying"
                message="{{ $filter === 'open' ? __('servant.no_open_prayers') : __('servant.no_prayers') }}"
            />
        @endforelse
    </div>

    {{-- Pagination --}}
    <div wire:loading.remove>
        <x-ui.pagination :paginator="$prayerRequests" />
    </div>

</div>
