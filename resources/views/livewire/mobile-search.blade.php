<div class="sm:hidden">

    {{-- Trigger button — visible only below sm (640px) --}}
    <button
        type="button"
        wire:click="openModal"
        class="flex items-center justify-center w-10 h-10 rounded-full
               hover:bg-gray-100 dark:hover:bg-gray-800 transition
               focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
        aria-label="{{ __('search.title') }}"
        title="{{ __('search.title') }}"
    >
        <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-500 dark:text-gray-400" />
    </button>

    {{-- Full-screen overlay --}}
    <div
        x-data="{
            init() {
                this.$watch('$wire.open', v => {
                    document.body.style.overflow = v ? 'hidden' : '';
                });
            }
        }"
        x-show="$wire.open"
        x-cloak
        @keydown.escape.window="$wire.closeModal()"
        class="fixed inset-0 z-[200] flex flex-col"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('search.title') }}"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/50 backdrop-blur-sm"
            x-show="$wire.open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            wire:click="closeModal"
            aria-hidden="true"
        ></div>

        {{-- Bottom sheet --}}
        <div
            class="relative mt-auto w-full rounded-t-2xl
                   bg-white dark:bg-gray-900
                   shadow-2xl border-t border-gray-200 dark:border-gray-700
                   flex flex-col"
            style="max-height: 90dvh;"
            x-show="$wire.open"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-0"
            x-init="$watch('$wire.open', v => { if (v) $nextTick(() => document.getElementById('mob-search-input')?.focus()); })"
        >
            {{-- Search header --}}
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
                <div class="shrink-0 text-gray-400 dark:text-gray-500">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                </div>

                <input
                    id="mob-search-input"
                    type="search"
                    autocomplete="off"
                    maxlength="200"
                    placeholder="{{ __('search.placeholder') }}"
                    wire:model.live.debounce.200ms="query"
                    @keydown.escape="$wire.closeModal()"
                    class="flex-1 bg-transparent text-base
                           text-gray-900 dark:text-gray-100
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none min-h-[44px]"
                    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
                    aria-label="{{ __('search.placeholder') }}"
                />

                {{-- Spinner --}}
                <div wire:loading wire:target="query" class="shrink-0 text-primary-500">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                {{-- Close button --}}
                <button
                    type="button"
                    wire:click="closeModal"
                    class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full
                           text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700
                           transition focus:outline-none"
                    aria-label="{{ __('search.close') }}"
                >
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            {{-- Results area --}}
            <div class="flex-1 overflow-y-auto overscroll-contain">

                @if (blank(trim($query)))
                    {{-- Prompt to start typing --}}
                    <div class="flex flex-col items-center justify-center py-12 gap-3
                                text-gray-400 dark:text-gray-500">
                        <x-heroicon-o-magnifying-glass class="w-10 h-10 opacity-40" />
                        <p class="text-sm">{{ __('search.type_to_search') }}</p>
                    </div>

                @elseif ($results === null)
                    {{-- Query too short OR no results returned --}}
                    <div
                        class="flex flex-col items-center justify-center py-12 gap-3
                               text-gray-400 dark:text-gray-500"
                        wire:loading.remove wire:target="query"
                    >
                        <x-heroicon-o-face-frown class="w-10 h-10 opacity-40" />
                        <p class="text-sm">{{ __('search.no_results') }}</p>
                    </div>

                @elseif ($results->getCategories()->isEmpty())
                    {{-- Results object returned but no categories --}}
                    <div class="flex flex-col items-center justify-center py-12 gap-3
                                text-gray-400 dark:text-gray-500">
                        <x-heroicon-o-face-frown class="w-10 h-10 opacity-40" />
                        <p class="text-sm">{{ __('search.no_results') }}</p>
                    </div>

                @else
                    {{-- Grouped results --}}
                    <ul>
                        @foreach ($results->getCategories() as $categoryName => $categoryResults)
                            <li>
                                {{-- Category header --}}
                                <div class="sticky top-0 z-10 px-4 py-2
                                            bg-gray-50 dark:bg-gray-800
                                            border-b border-gray-100 dark:border-gray-700">
                                    <h3 class="text-xs font-semibold uppercase tracking-wide
                                               text-gray-500 dark:text-gray-400">
                                        {{ $categoryName }}
                                    </h3>
                                </div>

                                {{-- Results in this category --}}
                                <ul class="divide-y divide-gray-50 dark:divide-gray-800/50">
                                    @foreach ($categoryResults as $result)
                                        <li>
                                            <a
                                                href="{{ $result->url }}"
                                                wire:click="closeModal"
                                                wire:navigate
                                                class="block w-full text-start px-4 py-3 min-h-[56px]
                                                       hover:bg-gray-50 dark:hover:bg-white/5
                                                       focus:outline-none focus:bg-gray-50 dark:focus:bg-white/5
                                                       transition"
                                            >
                                                <p class="text-sm font-semibold
                                                          text-gray-900 dark:text-gray-100 truncate">
                                                    {{ $result->title }}
                                                </p>

                                                @if ($result->details)
                                                    <dl class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5">
                                                        @foreach ($result->details as $label => $value)
                                                            <div class="flex items-center gap-1
                                                                        text-xs text-gray-500 dark:text-gray-400">
                                                                <dt class="font-medium">{{ $label }}:</dt>
                                                                <dd>{{ $value }}</dd>
                                                            </div>
                                                        @endforeach
                                                    </dl>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                @endif

            </div>

            {{-- iOS safe area spacer --}}
            <div class="shrink-0" style="padding-bottom: env(safe-area-inset-bottom, 0px);"></div>
        </div>
    </div>
</div>
