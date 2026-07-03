<x-web-app.modal
    :show="$showVisitForm"
    title="{{ $editingVisitId ? __('web_app.forms.visit.edit_title') : __('web_app.actions.record_visit') }}"
    description="{{ __('web_app.forms.daily_action') }}"
    close="closeVisitForm"
>
    <div class="app-form-grid">
        <label class="app-form-field" for="visit-beneficiary">
            <span>{{ __('visits.beneficiary') }}</span>
            <select id="visit-beneficiary" wire:model="visitBeneficiaryId" aria-required="true" aria-invalid="{{ $errors->has('visitBeneficiaryId') ? 'true' : 'false' }}">
                <option value="">{{ __('web_app.forms.select.beneficiary') }}</option>
                @foreach ($beneficiaryOptions as $beneficiary)
                    <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }}{{ $beneficiary->code ? ' - ' . $beneficiary->code : '' }}</option>
                @endforeach
            </select>
            @error('visitBeneficiaryId') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="visit-type">
            <span>{{ __('visits.type') }}</span>
            <select id="visit-type" wire:model="visitType" aria-required="true" aria-invalid="{{ $errors->has('visitType') ? 'true' : 'false' }}">
                <option value="">{{ __('web_app.forms.select.visit_type') }}</option>
                @foreach ($visitTypeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('visitType') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="visit-date">
            <span>{{ __('visits.visit_date') }}</span>
            <input id="visit-date" type="datetime-local" wire:model="visitDate" step="60" aria-required="true" aria-invalid="{{ $errors->has('visitDate') ? 'true' : 'false' }}">
            @error('visitDate') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="beneficiary-status">
            <span>{{ __('visits.beneficiary_status') }}</span>
            <select id="beneficiary-status" wire:model="beneficiaryStatus" aria-required="true" aria-invalid="{{ $errors->has('beneficiaryStatus') ? 'true' : 'false' }}">
                <option value="">{{ __('web_app.forms.select.status') }}</option>
                @foreach ($beneficiaryStatusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('beneficiaryStatus') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="duration-minutes">
            <span>{{ __('visits.duration_minutes') }}</span>
            <input id="duration-minutes" type="number" min="1" max="480" wire:model="durationMinutes" placeholder="{{ __('web_app.forms.placeholders.duration') }}" aria-invalid="{{ $errors->has('durationMinutes') ? 'true' : 'false' }}">
            @error('durationMinutes') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field app-form-field-full" for="visit-feedback">
            <span>{{ __('visits.feedback') }}</span>
            <textarea id="visit-feedback" wire:model="visitFeedback" rows="4" placeholder="{{ __('web_app.forms.placeholders.visit_feedback') }}" aria-invalid="{{ $errors->has('visitFeedback') ? 'true' : 'false' }}"></textarea>
            @error('visitFeedback') <small>{{ $message }}</small> @enderror
        </label>
        <fieldset class="app-check-grid app-form-field-full">
            <legend class="sr-only">{{ __('visits.follow_up_flags') }}</legend>
            <label class="app-check-row" for="visit-is-critical">
                <input id="visit-is-critical" type="checkbox" wire:model="isCritical" aria-invalid="{{ $errors->has('isCritical') ? 'true' : 'false' }}">
                <span>{{ __('visits.is_critical') }}</span>
            </label>
            <label class="app-check-row" for="visit-needs-family">
                <input id="visit-needs-family" type="checkbox" wire:model="needsFamilyLeader" aria-invalid="{{ $errors->has('needsFamilyLeader') ? 'true' : 'false' }}">
                <span>{{ __('visits.needs_family_leader') }}</span>
            </label>
            <label class="app-check-row" for="visit-needs-service">
                <input id="visit-needs-service" type="checkbox" wire:model="needsServiceLeader" aria-invalid="{{ $errors->has('needsServiceLeader') ? 'true' : 'false' }}">
                <span>{{ __('visits.needs_service_leader') }}</span>
            </label>
        </fieldset>
    </div>
    <x-slot:actions>
        <button type="button" wire:click="closeVisitForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
        <button type="button" wire:click="saveVisit" wire:loading.attr="disabled" class="app-primary-button">
            <span wire:loading.remove wire:target="saveVisit">{{ $editingVisitId ? __('web_app.forms.visit.update') : __('web_app.forms.visit.save') }}</span>
            <span wire:loading wire:target="saveVisit">{{ __('web_app.actions.saving') }}</span>
        </button>
    </x-slot:actions>
</x-web-app.modal>
