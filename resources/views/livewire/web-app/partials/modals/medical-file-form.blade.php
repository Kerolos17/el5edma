@if ($showMedicalFileForm)
    <div class="app-modal-backdrop" wire:click="closeMedicalFileForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="رفع ملف طبي">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div><p class="app-section-label">الملفات الطبية</p><h3>رفع ملف طبي</h3></div>
                <button type="button" wire:click="closeMedicalFileForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field">
                    <span>المخدوم</span>
                    <select wire:model="medicalFileBeneficiaryId">
                        <option value="">اختر المخدوم</option>
                        @foreach ($beneficiaryOptions as $beneficiary)
                            <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }}{{ $beneficiary->code ? ' - ' . $beneficiary->code : '' }}</option>
                        @endforeach
                    </select>
                    @error('medicalFileBeneficiaryId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>نوع الملف</span>
                    <select wire:model="medicalFileType">
                        @foreach ($medicalFileTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('medicalFileType') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field app-form-field-full"><span>عنوان الملف</span><input type="text" wire:model="medicalFileTitle" placeholder="مثال: تقرير متابعة">@error('medicalFileTitle') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field app-form-field-full"><span>الملف</span><input type="file" wire:model="medicalUploadedFile">@error('medicalUploadedFile') <small>{{ $message }}</small> @enderror</label>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeMedicalFileForm" class="app-secondary-button">إلغاء</button>
                <button type="button" wire:click="saveMedicalFile" class="app-primary-button">حفظ الملف</button>
            </div>
        </div>
    </section>
@endif
