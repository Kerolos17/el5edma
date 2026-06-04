@if ($showPrayerForm)
    <div class="app-modal-backdrop" wire:click="closePrayerForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="{{ __('web_app.actions.add_prayer_request') }}">
        <div class="app-modal-panel" tabindex="-1">
            <div class="app-modal-header">
                <div><p class="app-section-label">{{ __('web_app.forms.daily_action') }}</p><h3>{{ __('web_app.actions.add_prayer_request') }}</h3></div>
                <button type="button" wire:click="closePrayerForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field app-form-field-full">
                    <span>{{ __('visits.beneficiary') }}</span>
                    <select wire:model="prayerBeneficiaryId">
                        <option value="">{{ __('web_app.forms.select.beneficiary') }}</option>
                        @foreach ($beneficiaryOptions as $beneficiary)
                            <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }} @if($beneficiary->code) - {{ $beneficiary->code }} @endif</option>
                        @endforeach
                    </select>
                    @error('prayerBeneficiaryId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field app-form-field-full"><span>{{ __('prayer.request_title') }}</span><input type="text" wire:model="prayerTitle" placeholder="{{ __('web_app.forms.placeholders.prayer_title') }}">@error('prayerTitle') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field app-form-field-full"><span>{{ __('prayer.body') }}</span><textarea wire:model="prayerBody" rows="4" placeholder="{{ __('web_app.forms.placeholders.prayer_body') }}"></textarea>@error('prayerBody') <small>{{ $message }}</small> @enderror</label>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closePrayerForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
                <button type="button" wire:click="savePrayer" wire:loading.attr="disabled" class="app-primary-button">{{ __('web_app.forms.prayer.save') }}</button>
            </div>
        </div>
    </section>
@endif
