@if ($showScheduledVisitForm)
    <div class="app-modal-backdrop" wire:click="closeScheduledVisitForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="{{ __('web_app.actions.schedule_visit') }}">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div><p class="app-section-label">{{ __('web_app.forms.scheduled_visit.section') }}</p><h3>{{ __('web_app.actions.schedule_visit') }}</h3></div>
                <button type="button" wire:click="closeScheduledVisitForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field">
                    <span>{{ __('visits.beneficiary') }}</span>
                    <select wire:model="scheduledVisitBeneficiaryId">
                        <option value="">{{ __('web_app.forms.select.beneficiary') }}</option>
                        @foreach ($beneficiaryOptions as $beneficiary)
                            <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }}{{ $beneficiary->code ? ' - ' . $beneficiary->code : '' }}</option>
                        @endforeach
                    </select>
                    @error('scheduledVisitBeneficiaryId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>{{ __('visits.servants') }}</span>
                    <div class="app-check-grid">
                        @forelse ($servantOptions as $servant)
                            <label class="app-check-row"><input type="checkbox" value="{{ $servant->id }}" wire:model="scheduledVisitAssignedServantIds"><span>{{ $servant->name }}</span></label>
                        @empty
                            <div class="app-check-empty">{{ __('web_app.forms.scheduled_visit.select_beneficiary_first') }}</div>
                        @endforelse
                    </div>
                    @if (count($scheduledVisitAssignedServantIds) > 0)
                        <div class="app-chip-row">
                            @foreach ($servantOptions->whereIn('id', array_map('intval', $scheduledVisitAssignedServantIds)) as $servant)
                                <span class="app-status-pill tone-blue">{{ $servant->name }}</span>
                            @endforeach
                        </div>
                    @endif
                    @error('scheduledVisitAssignedServantIds') <small>{{ $message }}</small> @enderror
                    @error('scheduledVisitAssignedServantIds.*') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field"><span>{{ __('visits.scheduled_date') }}</span><input type="date" wire:model="scheduledVisitDate">@error('scheduledVisitDate') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>{{ __('visits.scheduled_time') }}</span><input type="time" wire:model="scheduledVisitTime">@error('scheduledVisitTime') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field app-form-field-full"><span>{{ __('visits.notes') }}</span><textarea wire:model="scheduledVisitNotes" rows="4" placeholder="{{ __('web_app.forms.placeholders.scheduled_notes') }}"></textarea>@error('scheduledVisitNotes') <small>{{ $message }}</small> @enderror</label>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeScheduledVisitForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
                <button type="button" wire:click="saveScheduledVisit" wire:loading.attr="disabled" class="app-primary-button">{{ __('web_app.forms.scheduled_visit.save') }}</button>
            </div>
        </div>
    </section>
@endif
