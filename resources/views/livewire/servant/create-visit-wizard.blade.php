@php
    $visitTypes = [
        'home_visit'     => ['label' => __('visits.home_visit'),     'icon' => 'ph-house-simple',   'var' => 'var(--wizard-type-home)'],
        'phone_call'     => ['label' => __('visits.phone_call'),    'icon' => 'ph-phone',          'var' => 'var(--wizard-type-phone)'],
        'church_meeting' => ['label' => __('visits.church_meeting'),   'icon' => 'ph-church',         'var' => 'var(--wizard-type-church)'],
    ];
    $statuses = [
        'great'        => ['label' => __('visits.great'),        'class' => 'is-success'],
        'good'         => ['label' => __('visits.good'),         'class' => 'is-info'],
        'needs_follow' => ['label' => __('visits.needs_follow'), 'class' => 'is-warning'],
        'critical'     => ['label' => __('visits.critical'),     'class' => 'is-error'],
    ];
@endphp

{{-- Single Livewire root --}}
<div x-data="{ open: $wire.entangle('open') }"
     @open-wizard.window="open = true"
     @open-wizard-for.window="open = true"
     role="dialog" aria-modal="true" aria-label="{{ __('web_app.actions.record_visit') }}">

    {{-- Overlay --}}
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         wire:click="close"
         class="fixed inset-0 z-[150] wizard-overlay">
    </div>

    {{-- Bottom Sheet --}}
    <div class="fixed bottom-0 inset-x-0 z-[160] wizard-sheet-outer"
         :style="{ transform: open ? 'translateY(0)' : 'translateY(100%)' }"
         style="transform: translateY(100%);">

        <div class="rounded-t-[28px] overflow-hidden wizard-sheet-inner">

            {{-- Drag Handle --}}
            <div class="flex justify-center pt-3 pb-1">
                <div class="w-10 h-1 rounded-full wizard-drag-handle"></div>
            </div>

            {{-- Step Indicator --}}
            <div class="px-5 pt-2 pb-4" aria-live="polite">
                <div class="flex items-center gap-1 mb-3" role="group" aria-label="{{ __('web_app.wizard.steps') }}">
                    @for($i = 1; $i <= 4; $i++)
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 transition-all duration-300 wizard-step-dot {{ $step >= $i ? 'is-active' : '' }}"
                             aria-current="{{ $step === $i ? 'step' : 'false' }}">
                            {{ $i }}
                        </div>
                        @if($i < 4)
                            <div class="step-connector {{ $step > $i ? 'completed' : '' }}"></div>
                        @endif
                    @endfor
                </div>

                {{-- Step Title --}}
                <h3 class="font-bold text-lg wizard-step-title">
                    {{ match($step) {
                        1 => __('web_app.wizard.step_beneficiary'),
                        2 => __('web_app.wizard.step_type'),
                        3 => __('web_app.wizard.step_details'),
                        default => __('web_app.wizard.step_summary'),
                    } }}
                </h3>
            </div>

            {{-- ══════════════════ STEP 1: Select Beneficiary ══════════════════ --}}
            @if($step === 1)
                <div class="px-5 pb-6 space-y-4">

                    @if($selectedBeneficiary)
                        {{-- Selected beneficiary card --}}
                        <div class="wizard-card rounded-2xl px-4 py-3 flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl overflow-hidden flex-shrink-0">
                                @if($selectedBeneficiary->photo_url)
                                    <img src="{{ $selectedBeneficiary->photo_url }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <div class="w-full h-full flex items-center justify-center font-bold wizard-avatar-fallback">
                                        {{ mb_substr($selectedBeneficiary->full_name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-sm truncate wizard-text-strong">{{ $selectedBeneficiary->full_name }}</p>
                                <p class="text-xs wizard-text-muted">{{ $selectedBeneficiary->code }}</p>
                            </div>
                            <button wire:click="clearBeneficiary"
                                    class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 transition-colors wizard-icon-button"
                                    aria-label="{{ __('web_app.actions.clear_selection') }}">
                                <i class="ph ph-x text-sm wizard-text-muted" aria-hidden="true"></i>
                            </button>
                        </div>
                    @else
                        {{-- Search --}}
                        <div class="relative">
                            <i class="ph ph-magnifying-glass absolute top-1/2 -translate-y-1/2 text-lg pointer-events-none wizard-search-icon" aria-hidden="true"></i>
                            <input wire:model.live.debounce.250ms="beneficiarySearch"
                                   type="search"
                                   placeholder="{{ __('web_app.forms.placeholders.search_beneficiary') }}"
                                   class="search-input wizard-search-input"
                                   aria-label="{{ __('web_app.forms.placeholders.search_beneficiary') }}">
                        </div>

                        {{-- Beneficiary List --}}
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @forelse($beneficiaries as $b)
                                <button wire:click="selectBeneficiary({{ $b->id }})"
                                        class="w-full text-start wizard-card rounded-2xl px-4 py-3 flex items-center gap-3 hover:shadow-md transition-all duration-200 active:scale-[0.98]">
                                    <div class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0">
                                        @if($b->photo_url)
                                            <img src="{{ $b->photo_url }}" class="w-full h-full object-cover" alt="">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center font-bold text-sm wizard-avatar-fallback">
                                                {{ mb_substr($b->full_name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-sm truncate wizard-text-strong">{{ $b->full_name }}</p>
                                        <p class="text-xs wizard-text-muted">{{ $b->code }}</p>
                                    </div>
                                </button>
                            @empty
                                <div class="text-center py-8">
                                    <i class="ph ph-users text-4xl block mb-2 wizard-text-muted" aria-hidden="true"></i>
                                    <p class="text-sm wizard-text-muted">
                                        {{ $beneficiarySearch ? __('visits.no_records') : __('web_app.forms.placeholders.search_beneficiary') }}
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    @endif

                    @error('selectedBeneficiaryId')
                        <p class="text-sm flex items-center gap-1 wizard-error-text">
                            <i class="ph ph-warning-circle" aria-hidden="true"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            @endif

            {{-- ══════════════════ STEP 2: Visit Type ══════════════════ --}}
            @if($step === 2)
                <div class="px-5 pb-6 space-y-3">
                    @foreach($visitTypes as $key => $type)
                        <button wire:click="$set('visitType', '{{ $key }}')"
                                class="w-full text-start rounded-2xl px-5 py-4 flex items-center gap-4 transition-all duration-200 border-2 wizard-type-card {{ $visitType === $key ? 'is-selected' : '' }}">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 wizard-type-icon {{ $visitType === $key ? 'is-selected' : '' }}"
                                 style="--type-color: {{ $type['var'] }}">
                                <i class="ph-bold {{ $type['icon'] }} text-xl"></i>
                            </div>
                            <span class="font-bold text-base wizard-type-label {{ $visitType === $key ? 'is-selected' : '' }}">
                                {{ $type['label'] }}
                            </span>
                            @if($visitType === $key)
                                <span class="ms-auto w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 wizard-check-mark">
                                    <i class="ph-bold ph-check text-white text-xs" aria-hidden="true"></i>
                                </span>
                            @endif
                        </button>
                    @endforeach

                    @error('visitType')
                        <p class="text-sm flex items-center gap-1 wizard-error-text">
                            <i class="ph ph-warning-circle" aria-hidden="true"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            @endif

            {{-- ══════════════════ STEP 3: Details ══════════════════ --}}
            @if($step === 3)
                <div class="px-5 pb-6 space-y-5">

                    {{-- Beneficiary Status --}}
                    <fieldset>
                        <legend class="text-sm font-bold mb-3 wizard-text-strong">{{ __('visits.beneficiary_status') }}</legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($statuses as $key => $status)
                                <button wire:click="$set('beneficiaryStatus', '{{ $key }}')"
                                        type="button"
                                        class="py-3 rounded-2xl text-center font-bold text-sm transition-all duration-200 border-2 wizard-status-chip {{ $beneficiaryStatus === $key ? 'is-selected ' . $status['class'] : '' }}">
                                    {{ $status['label'] }}
                                </button>
                            @endforeach
                        </div>
                        @error('beneficiaryStatus')
                            <p class="text-sm mt-1 flex items-center gap-1 wizard-error-text">
                                <i class="ph ph-warning-circle" aria-hidden="true"></i> {{ $message }}
                            </p>
                        @enderror
                    </fieldset>

                    {{-- Duration --}}
                    <div>
                        <label class="text-sm font-bold mb-2 block wizard-text-strong">{{ __('visits.duration_minutes') }}</label>
                        <div class="flex gap-2 flex-wrap">
                            @foreach([15, 30, 45, 60, 90] as $min)
                                <button wire:click="$set('durationMinutes', {{ $min }})"
                                        type="button"
                                        class="px-4 min-h-[44px] rounded-xl text-sm font-bold transition-all duration-200 border-2 wizard-duration-chip {{ $durationMinutes == $min ? 'is-selected' : '' }}">
                                    {{ $min }}{{ __('visits.minute_suffix') }}
                                </button>
                            @endforeach
                            <input wire:model.live.debounce.150ms="durationMinutes"
                                   type="number" min="1" max="480" placeholder="{{ __('visits.other') }}"
                                   class="w-20 min-h-[44px] px-3 rounded-xl border-2 text-sm text-center font-bold outline-none transition-colors wizard-duration-input"
                                   aria-label="{{ __('visits.duration_minutes') }}">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="text-sm font-bold mb-2 block wizard-text-strong">{{ __('visits.feedback') }}</label>
                        <textarea wire:model.lazy="feedback"
                                  class="form-textarea"
                                  rows="4"
                                  placeholder="{{ __('visits.feedback') }}"></textarea>
                    </div>

                    {{-- Critical + Escalation Flags --}}
                    <fieldset class="space-y-2">
                        <legend class="sr-only">{{ __('visits.follow_up_flags') }}</legend>
                        <label class="flex items-center gap-3 wizard-card rounded-2xl px-4 py-3 cursor-pointer wizard-flag-card {{ $isCritical ? 'is-critical' : '' }}">
                            <input wire:model="isCritical" type="checkbox"
                                   class="w-5 h-5 rounded cursor-pointer flex-shrink-0 wizard-checkbox-critical">
                            <div>
                                <p class="font-bold text-sm {{ $isCritical ? 'wizard-error-text' : 'wizard-text-strong' }}">{{ __('visits.is_critical') }}</p>
                                <p class="text-xs wizard-text-muted">{{ __('visits.critical_case_help') }}</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 wizard-card rounded-2xl px-4 py-3 cursor-pointer wizard-flag-card {{ $needsFamilyLeader ? 'is-family-leader' : '' }}">
                            <input wire:model="needsFamilyLeader" type="checkbox"
                                   class="w-5 h-5 rounded cursor-pointer flex-shrink-0 wizard-checkbox-warning">
                            <div>
                                <p class="font-bold text-sm {{ $needsFamilyLeader ? 'wizard-text-warning' : 'wizard-text-strong' }}">{{ __('visits.needs_family_leader') }}</p>
                                <p class="text-xs wizard-text-muted">{{ __('visits.needs_family_leader_help') }}</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 wizard-card rounded-2xl px-4 py-3 cursor-pointer wizard-flag-card {{ $needsServiceLeader ? 'is-service-leader' : '' }}">
                            <input wire:model="needsServiceLeader" type="checkbox"
                                   class="w-5 h-5 rounded cursor-pointer flex-shrink-0 wizard-checkbox-info">
                            <div>
                                <p class="font-bold text-sm {{ $needsServiceLeader ? 'wizard-text-primary-emph' : 'wizard-text-strong' }}">{{ __('visits.needs_service_leader') }}</p>
                                <p class="text-xs wizard-text-muted">{{ __('visits.needs_service_leader_help') }}</p>
                            </div>
                        </label>
                    </fieldset>
                </div>
            @endif

            {{-- ══════════════════ STEP 4: Summary & Confirm ══════════════════ --}}
            @if($step === 4)
                <div class="px-5 pb-6 space-y-4">

                    {{-- Beneficiary --}}
                    @if($selectedBeneficiary)
                        <div class="wizard-card rounded-2xl px-4 py-3 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0">
                                @if($selectedBeneficiary->photo_url)
                                    <img src="{{ $selectedBeneficiary->photo_url }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <div class="w-full h-full flex items-center justify-center font-bold wizard-avatar-fallback">
                                        {{ mb_substr($selectedBeneficiary->full_name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="text-xs wizard-text-muted">{{ __('visits.beneficiary') }}</p>
                                <p class="font-bold text-sm wizard-text-strong">{{ $selectedBeneficiary->full_name }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Summary Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="wizard-card rounded-2xl p-3 text-center">
                             <i class="ph-bold {{ $visitTypes[$visitType]['icon'] ?? 'ph-calendar' }} text-xl mb-1 block wizard-primary-text" aria-hidden="true"></i>
                            <p class="text-xs wizard-text-muted">{{ __('visits.visit_type') }}</p>
                            <p class="font-bold text-sm wizard-text-strong">{{ $visitTypes[$visitType]['label'] ?? '' }}</p>
                        </div>

                        <div class="wizard-card rounded-2xl p-3 text-center">
                            <div class="w-5 h-5 rounded-full mx-auto mb-1 wizard-status-dot {{ $statuses[$beneficiaryStatus]['class'] ?? '' }}"></div>
                            <p class="text-xs wizard-text-muted">{{ __('visits.status') }}</p>
                            <p class="font-bold text-sm wizard-text-strong">{{ $statuses[$beneficiaryStatus]['label'] ?? '' }}</p>
                        </div>

                        @if($durationMinutes)
                            <div class="wizard-card rounded-2xl p-3 text-center">
                                <i class="ph ph-clock text-xl mb-1 block wizard-primary-text" aria-hidden="true"></i>
                                <p class="text-xs wizard-text-muted">{{ __('visits.duration') }}</p>
                                <p class="font-bold text-sm wizard-text-strong">{{ $durationMinutes }} {{ __('visits.minutes') }}</p>
                            </div>
                        @endif

                        <div class="wizard-card rounded-2xl p-3 text-center">
                            <i class="ph ph-calendar-check text-xl mb-1 block wizard-primary-text" aria-hidden="true"></i>
                            <p class="text-xs wizard-text-muted">{{ __('visits.date') }}</p>
                            <p class="font-bold text-sm wizard-text-strong">{{ now()->locale(app()->getLocale())->isoFormat('D MMM') }}</p>
                        </div>
                    </div>

                    @if($feedback)
                        <div class="wizard-card rounded-2xl px-4 py-3">
                            <p class="text-xs wizard-text-muted mb-1">{{ __('visits.notes') }}</p>
                            <p class="text-sm wizard-text-body leading-relaxed">{{ $feedback }}</p>
                        </div>
                    @endif

                    @if($isCritical || $needsFamilyLeader || $needsServiceLeader)
                        <div class="flex flex-wrap gap-2">
                            @if($isCritical)
                                <span class="badge-pill badge-critical text-xs">
                                    <i class="ph-fill ph-warning" aria-hidden="true"></i> {{ __('visits.is_critical') }}
                                </span>
                            @endif
                            @if($needsFamilyLeader)
                                <span class="badge-pill badge-warning text-xs">
                                    <i class="ph ph-user-gear" aria-hidden="true"></i> {{ __('visits.needs_family_leader') }}
                                </span>
                            @endif
                            @if($needsServiceLeader)
                                <span class="badge-pill badge-info text-xs">
                                    <i class="ph ph-user-crown" aria-hidden="true"></i> {{ __('visits.needs_service_leader') }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            {{-- Navigation Buttons --}}
            <div class="px-5 pb-8 flex gap-3 wizard-nav-actions" role="group" aria-label="{{ __('web_app.wizard.navigation') }}">

                @if($step > 1)
                    <button wire:click="prevStep"
                            type="button"
                            class="flex-1 py-3.5 rounded-2xl font-bold text-sm border-2 transition-all duration-200 wizard-btn-secondary">
                        {{ __('web_app.actions.back') }}
                    </button>
                @else
                    <button wire:click="close"
                            type="button"
                            class="flex-1 py-3.5 rounded-2xl font-bold text-sm border-2 transition-all duration-200 wizard-btn-secondary">
                        {{ __('web_app.actions.cancel') }}
                    </button>
                @endif

                @if($step < 4)
                    <button wire:click="nextStep"
                            type="button"
                            class="flex-[2] py-3.5 rounded-2xl font-bold text-sm text-white btn-ripple transition-all duration-200 wizard-btn-primary">
                        {{ __('web_app.actions.next') }}
                    </button>
                @else
                    <button
                        x-data
                        @click.prevent="
                            if (!navigator.onLine) {
                                const queue = window.__servantOfflineQueue;
                                if (queue) {
                                    await queue.enqueue({
                                        beneficiary_id:     $wire.selectedBeneficiaryId,
                                        visitType:          $wire.visitType,
                                        beneficiaryStatus:  $wire.beneficiaryStatus,
                                        durationMinutes:    $wire.durationMinutes,
                                        feedback:           $wire.feedback,
                                        isCritical:         $wire.isCritical,
                                        needsFamilyLeader:  $wire.needsFamilyLeader,
                                        needsServiceLeader: $wire.needsServiceLeader,
                                        queuedAt:           Date.now(),
                                    });
                                    $wire.close();
                                    $dispatch('toast', { message: '{{ __('web_app.wizard.offline_queued') }}', type: 'warning' });
                                }
                            } else {
                                $wire.submit();
                            }
                        "
                        wire:loading.attr="disabled"
                        type="button"
                        class="flex-[2] py-3.5 rounded-2xl font-bold text-sm text-white btn-ripple transition-all duration-200 disabled:opacity-60 wizard-btn-save">
                        <span wire:loading.remove wire:target="submit">{{ __('web_app.wizard.confirm_save') }}</span>
                        <span wire:loading wire:target="submit">{{ __('web_app.actions.saving') }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
