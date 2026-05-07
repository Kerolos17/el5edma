@if ($showVisitForm)
    <div class="app-modal-backdrop" wire:click="closeVisitForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="تسجيل زيارة">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div><p class="app-section-label">إجراء يومي</p><h3>{{ $editingVisitId ? 'تعديل زيارة' : 'تسجيل زيارة' }}</h3></div>
                <button type="button" wire:click="closeVisitForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field">
                    <span>المخدوم</span>
                    <select wire:model="visitBeneficiaryId">
                        <option value="">اختر المخدوم</option>
                        @foreach ($beneficiaryOptions as $beneficiary)
                            <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }}{{ $beneficiary->code ? ' - ' . $beneficiary->code : '' }}</option>
                        @endforeach
                    </select>
                    @error('visitBeneficiaryId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>نوع الزيارة</span>
                    <select wire:model="visitType">
                        <option value="">اختر النوع</option>
                        @foreach ($visitTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visitType') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field"><span>وقت الزيارة</span><input type="datetime-local" wire:model="visitDate">@error('visitDate') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field">
                    <span>الحالة العامة</span>
                    <select wire:model="beneficiaryStatus">
                        <option value="">اختر الحالة</option>
                        @foreach ($beneficiaryStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('beneficiaryStatus') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field"><span>المدة بالدقائق</span><input type="number" min="1" max="480" wire:model="durationMinutes" placeholder="مثال: 45">@error('durationMinutes') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field app-form-field-full"><span>ملاحظات الزيارة</span><textarea wire:model="visitFeedback" rows="4" placeholder="ملخص الحالة والاحتياجات وما تم خلال الزيارة"></textarea>@error('visitFeedback') <small>{{ $message }}</small> @enderror</label>
                <div class="app-check-grid app-form-field-full">
                    <label class="app-check-row"><input type="checkbox" wire:model="isCritical"><span>حالة حرجة</span></label>
                    <label class="app-check-row"><input type="checkbox" wire:model="needsFamilyLeader"><span>تحتاج أمين الأسرة</span></label>
                    <label class="app-check-row"><input type="checkbox" wire:model="needsServiceLeader"><span>تحتاج رئيس الخدمة</span></label>
                </div>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeVisitForm" class="app-secondary-button">إلغاء</button>
                <button type="button" wire:click="saveVisit" class="app-primary-button">{{ $editingVisitId ? 'تحديث الزيارة' : 'حفظ الزيارة' }}</button>
            </div>
        </div>
    </section>
@endif
