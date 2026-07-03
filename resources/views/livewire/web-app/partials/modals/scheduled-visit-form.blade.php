<x-web-app.modal
    :show="$showScheduledVisitForm"
    title="{{ __('web_app.actions.schedule_visit') }}"
    description="{{ __('web_app.forms.scheduled_visit.section') }}"
    close="closeScheduledVisitForm"
>
    <div class="app-form-grid">
        <label class="app-form-field" for="scheduled-beneficiary">
            <span>{{ __('visits.beneficiary') }}</span>
            <select id="scheduled-beneficiary" wire:model.live="scheduledVisitBeneficiaryId" aria-required="true" aria-invalid="{{ $errors->has('scheduledVisitBeneficiaryId') ? 'true' : 'false' }}">
                <option value="">{{ __('web_app.forms.select.beneficiary') }}</option>
                @foreach ($beneficiaryOptions as $beneficiary)
                    <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }}{{ $beneficiary->code ? ' - ' . $beneficiary->code : '' }}</option>
                @endforeach
            </select>
            @error('scheduledVisitBeneficiaryId') <small>{{ $message }}</small> @enderror
        </label>
        <fieldset class="app-form-field">
            <legend class="block mb-1">{{ __('visits.servants') }}</legend>
            <div class="app-check-grid">
                @forelse ($servantOptions as $servant)
                    <label class="app-check-row" for="scheduled-servant-{{ $servant->id }}">
                        <input id="scheduled-servant-{{ $servant->id }}" type="checkbox" value="{{ $servant->id }}" wire:model.live="scheduledVisitAssignedServantIds">
                        <span>{{ $servant->name }}</span>
                    </label>
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
        </fieldset>
        <label class="app-form-field" for="scheduled-date">
            <span>{{ __('visits.scheduled_date') }}</span>
            <input id="scheduled-date" type="date" wire:model="scheduledVisitDate" aria-required="true" aria-invalid="{{ $errors->has('scheduledVisitDate') ? 'true' : 'false' }}">
            @error('scheduledVisitDate') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="scheduled-time">
            <span>{{ __('visits.scheduled_time') }}</span>
            <input id="scheduled-time" type="time" wire:model="scheduledVisitTime" step="60" aria-required="true" aria-invalid="{{ $errors->has('scheduledVisitTime') ? 'true' : 'false' }}">
            @error('scheduledVisitTime') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field app-form-field-full" for="scheduled-notes">
            <span>{{ __('visits.notes') }}</span>
            <textarea id="scheduled-notes" wire:model="scheduledVisitNotes" rows="4" placeholder="{{ __('web_app.forms.placeholders.scheduled_notes') }}" aria-invalid="{{ $errors->has('scheduledVisitNotes') ? 'true' : 'false' }}"></textarea>
            @error('scheduledVisitNotes') <small>{{ $message }}</small> @enderror
        </label>
    </div>
    <x-slot:actions>
        <button type="button" wire:click="closeScheduledVisitForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
        <button type="button" wire:click="saveScheduledVisit" wire:loading.attr="disabled" class="app-primary-button" :disabled="count($scheduledVisitAssignedServantIds) === 0">
            <span wire:loading.remove wire:target="saveScheduledVisit">{{ __('web_app.forms.scheduled_visit.save') }}</span>
            <span wire:loading wire:target="saveScheduledVisit">{{ __('web_app.actions.saving') }}</span>
        </button>
    </x-slot:actions>
</x-web-app.modal>
