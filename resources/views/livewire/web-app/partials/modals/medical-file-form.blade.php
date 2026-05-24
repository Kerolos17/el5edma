@if ($showMedicalFileForm)
    <div class="app-modal-backdrop" wire:click="closeMedicalFileForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="{{ __('medical.upload_file') }}">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div><p class="app-section-label">{{ __('medical.files_title') }}</p><h3>{{ __('medical.upload_file') }}</h3></div>
                <button type="button" wire:click="closeMedicalFileForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field app-form-field-full">
                    <span>{{ __('visits.beneficiary') }}</span>
                    <select wire:model="medicalBeneficiaryId">
                        <option value="">{{ __('web_app.forms.select.beneficiary') }}</option>
                        @foreach ($beneficiaryOptions as $beneficiary)
                            <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }} @if($beneficiary->code) - {{ $beneficiary->code }} @endif</option>
                        @endforeach
                    </select>
                    @error('medicalBeneficiaryId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>{{ __('medical.file_type') }}</span>
                    <select wire:model="medicalFileType">
                        @foreach ($medicalFileTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('medicalFileType') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field app-form-field-full"><span>{{ __('medical.file_title') }}</span><input type="text" wire:model="medicalFileTitle" placeholder="{{ __('web_app.forms.placeholders.medical_file_title') }}">@error('medicalFileTitle') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field app-form-field-full"><span>{{ __('web_app.table.file') }}</span><input type="file" wire:model="medicalUploadedFile">@error('medicalUploadedFile') <small>{{ $message }}</small> @enderror</label>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeMedicalFileForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
                <button type="button" wire:click="saveMedicalFile" wire:loading.attr="disabled" class="app-primary-button">{{ __('web_app.forms.medical_file.save') }}</button>
            </div>
        </div>
    </section>
@endif
