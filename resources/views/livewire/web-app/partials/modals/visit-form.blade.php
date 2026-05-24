@if ($showVisitForm)
    <div class="app-modal-backdrop" wire:click="closeVisitForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="{{ __('web_app.actions.record_visit') }}">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div><p class="app-section-label">{{ __('web_app.forms.daily_action') }}</p><h3>{{ $editingVisitId ? __('web_app.forms.visit.edit_title') : __('web_app.actions.record_visit') }}</h3></div>
                <button type="button" wire:click="closeVisitForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field">
                    <span>{{ __('visits.beneficiary') }}</span>
                    <select wire:model="visitBeneficiaryId">
                        <option value="">{{ __('web_app.forms.select.beneficiary') }}</option>
                        @foreach ($beneficiaryOptions as $beneficiary)
                            <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }}{{ $beneficiary->code ? ' - ' . $beneficiary->code : '' }}</option>
                        @endforeach
                    </select>
                    @error('visitBeneficiaryId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>{{ __('visits.type') }}</span>
                    <select wire:model="visitType">
                        <option value="">{{ __('web_app.forms.select.visit_type') }}</option>
                        @foreach ($visitTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visitType') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field"><span>{{ __('visits.visit_date') }}</span><input type="datetime-local" wire:model="visitDate">@error('visitDate') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field">
                    <span>{{ __('visits.beneficiary_status') }}</span>
                    <select wire:model="beneficiaryStatus">
                        <option value="">{{ __('web_app.forms.select.status') }}</option>
                        @foreach ($beneficiaryStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('beneficiaryStatus') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field"><span>{{ __('visits.duration_minutes') }}</span><input type="number" min="1" max="480" wire:model="durationMinutes" placeholder="{{ __('web_app.forms.placeholders.duration') }}">@error('durationMinutes') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field app-form-field-full"><span>{{ __('visits.feedback') }}</span><textarea wire:model="visitFeedback" rows="4" placeholder="{{ __('web_app.forms.placeholders.visit_feedback') }}"></textarea>@error('visitFeedback') <small>{{ $message }}</small> @enderror</label>
                <div class="app-check-grid app-form-field-full">
                    <label class="app-check-row"><input type="checkbox" wire:model="isCritical"><span>{{ __('visits.is_critical') }}</span></label>
                    <label class="app-check-row"><input type="checkbox" wire:model="needsFamilyLeader"><span>{{ __('visits.needs_family_leader') }}</span></label>
                    <label class="app-check-row"><input type="checkbox" wire:model="needsServiceLeader"><span>{{ __('visits.needs_service_leader') }}</span></label>
                </div>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeVisitForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
                <button type="button" wire:click="saveVisit" wire:loading.attr="disabled" class="app-primary-button">{{ $editingVisitId ? __('web_app.forms.visit.update') : __('web_app.forms.visit.save') }}</button>
            </div>
        </div>
    </section>
@endif
