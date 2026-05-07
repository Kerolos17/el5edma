@if ($showPrayerForm)
    <div class="app-modal-backdrop" wire:click="closePrayerForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="إضافة طلب صلاة">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div><p class="app-section-label">إجراء يومي</p><h3>إضافة طلب صلاة</h3></div>
                <button type="button" wire:click="closePrayerForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field">
                    <span>المخدوم</span>
                    <select wire:model="prayerBeneficiaryId">
                        <option value="">اختر المخدوم</option>
                        @foreach ($beneficiaryOptions as $beneficiary)
                            <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }}{{ $beneficiary->code ? ' - ' . $beneficiary->code : '' }}</option>
                        @endforeach
                    </select>
                    @error('prayerBeneficiaryId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field app-form-field-full"><span>عنوان الطلب</span><input type="text" wire:model="prayerTitle" placeholder="مثال: صلاة لأجل الشفاء">@error('prayerTitle') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field app-form-field-full"><span>تفاصيل الطلب</span><textarea wire:model="prayerBody" rows="4" placeholder="اكتب تفاصيل تساعد فريق الخدمة على المتابعة"></textarea>@error('prayerBody') <small>{{ $message }}</small> @enderror</label>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closePrayerForm" class="app-secondary-button">إلغاء</button>
                <button type="button" wire:click="savePrayer" class="app-primary-button">حفظ الطلب</button>
            </div>
        </div>
    </section>
@endif
