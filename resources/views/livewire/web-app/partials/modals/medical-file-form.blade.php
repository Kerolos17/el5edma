<x-web-app.modal
    :show="$showMedicalFileForm"
    title="{{ __('medical.upload_file') }}"
    description="{{ __('medical.files_title') }}"
    close="closeMedicalFileForm"
>
    <div class="app-form-grid">
        <label class="app-form-field app-form-field-full" for="medical-beneficiary">
            <span>{{ __('visits.beneficiary') }}</span>
            <select id="medical-beneficiary" wire:model="medicalFileBeneficiaryId" aria-required="true" aria-invalid="{{ $errors->has('medicalFileBeneficiaryId') ? 'true' : 'false' }}">
                <option value="">{{ __('web_app.forms.select.beneficiary') }}</option>
                @foreach ($beneficiaryOptions as $beneficiary)
                    <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }} @if($beneficiary->code) - {{ $beneficiary->code }} @endif</option>
                @endforeach
            </select>
            @error('medicalFileBeneficiaryId') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field" for="medical-type">
            <span>{{ __('medical.file_type') }}</span>
            <select id="medical-type" wire:model="medicalFileType" aria-invalid="{{ $errors->has('medicalFileType') ? 'true' : 'false' }}">
                @foreach ($medicalFileTypeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('medicalFileType') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field app-form-field-full" for="medical-title">
            <span>{{ __('medical.file_title') }}</span>
            <input id="medical-title" type="text" wire:model="medicalFileTitle" placeholder="{{ __('web_app.forms.placeholders.medical_file_title') }}" aria-invalid="{{ $errors->has('medicalFileTitle') ? 'true' : 'false' }}">
            @error('medicalFileTitle') <small>{{ $message }}</small> @enderror
        </label>
        <label class="app-form-field app-form-field-full" for="medical-file">
            <span>{{ __('web_app.table.file') }}</span>
            <input id="medical-file" type="file" wire:model="medicalUploadedFile" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" aria-invalid="{{ $errors->has('medicalUploadedFile') ? 'true' : 'false' }}">
            <small>{{ __('web_app.forms.medical_file.max_size', ['size' => '10 MB']) }}</small>
            @error('medicalUploadedFile') <small>{{ $message }}</small> @enderror
        </label>
    </div>

    <x-slot:actions>
        <button type="button" wire:click="closeMedicalFileForm" class="app-secondary-button">{{ __('web_app.actions.cancel') }}</button>
        <button type="button" wire:click="saveMedicalFile" wire:loading.attr="disabled" class="app-primary-button">
            <span wire:loading.remove wire:target="saveMedicalFile">{{ __('web_app.forms.medical_file.save') }}</span>
            <span wire:loading wire:target="saveMedicalFile">{{ __('web_app.actions.saving') }}</span>
        </button>
    </x-slot:actions>
</x-web-app.modal>
