<x-web-app.modal
    :show="$showPrayerForm"
    title="{{ $editingPrayerId ? __('web_app.actions.edit') : __('web_app.actions.add_prayer_request') }}"
    description="{{ __('web_app.forms.daily_action') }}"
    close="closePrayerForm"
>
    <div class="app-form-grid">
        @unless($editingPrayerId)
        <label class="app-form-field app-form-field-full" for="prayer-beneficiary">
            <span>{{ __('visits.beneficiary') }}</span>
            <select id="prayer-beneficiary" wire:model="prayerBeneficiaryId" aria-required="true" aria-invalid="{{ $errors->has('prayerBeneficiaryId') ? 'true' : 'false' }}">
                <option value="">{{ __('web_app.forms.select.beneficiary') }}</option>
                @foreach ($beneficiaryOptions as $beneficiary)
                    <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }} @if($beneficiary->code) - {{ $beneficiary->code }} @endif</option>
                @endforeach
            </select>
            @error('prayerBeneficiaryId') <small>{{ $message }}</small> @enderror
        </label>
        @endunless
        <label class="app-form-field app-form-field-full" for="prayer-title">
            <span>{{ __('prayer.request_title') }}</span>
            <input id="prayer-title" type="text" wire:model="prayerTitle" placeholder="{{ __('web_app.forms.placeholders.prayer_title') }}" maxlength="255" aria-required="true" aria-invalid="{{ $errors->has('prayerTitle') ? 'true' : 'false' }}">
            @error('prayerTitle') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field app-form-field-full" for="prayer-body">
            <span>{{ __('prayer.body') }}</span>
            <textarea id="prayer-body" wire:model="prayerBody" rows="4" placeholder="{{ __('web_app.forms.placeholders.prayer_body') }}" maxlength="2000" aria-required="true" aria-invalid="{{ $errors->has('prayerBody') ? 'true' : 'false' }}"></textarea>
            @error('prayerBody') <small>{{ $message }}</small> @enderror
        </label>
    </div>
    <x-slot:actions>
        <button type="button" wire:click="closePrayerForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
        <button type="button" wire:click="savePrayer" wire:loading.attr="disabled" class="app-primary-button">
            <span wire:loading.remove wire:target="savePrayer">{{ $editingPrayerId ? __('web_app.actions.save') : __('web_app.forms.prayer.save') }}</span>
            <span wire:loading wire:target="savePrayer">{{ __('web_app.actions.saving') }}</span>
        </button>
    </x-slot:actions>
</x-web-app.modal>
