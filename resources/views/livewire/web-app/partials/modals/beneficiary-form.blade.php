@if ($showBeneficiaryForm)
    <div class="app-modal-backdrop" wire:click="closeBeneficiaryForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="إدارة مخدوم">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div>
                    <p class="app-section-label">بيانات المخدوم</p>
                    <h3>{{ $editingBeneficiaryId ? 'تعديل مخدوم' : 'إضافة مخدوم' }}</h3>
                </div>
                <button type="button" wire:click="closeBeneficiaryForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>

            <div class="app-form-grid">
                <label class="app-form-field"><span>الاسم الكامل</span><input type="text" wire:model="beneficiaryFullName" placeholder="اسم المخدوم">@error('beneficiaryFullName') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>تاريخ الميلاد</span><input type="date" wire:model="beneficiaryBirthDate">@error('beneficiaryBirthDate') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field">
                    <span>النوع</span>
                    <select wire:model="beneficiaryGender">
                        <option value="">اختر النوع</option>
                        <option value="male">ذكر</option>
                        <option value="female">أنثى</option>
                    </select>
                    @error('beneficiaryGender') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>الحالة</span>
                    <select wire:model="beneficiaryRecordStatus">
                        @foreach ($beneficiaryRecordStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('beneficiaryRecordStatus') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>مجموعة الخدمة</span>
                    <select wire:model.live="beneficiaryServiceGroupId" @disabled(auth()->user()->isFamilyLeader())>
                        <option value="">اختر المجموعة</option>
                        @foreach ($beneficiaryServiceGroupOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('beneficiaryServiceGroupId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>الخادم المسؤول</span>
                    <select wire:model="beneficiaryAssignedServantId">
                        <option value="">غير معين</option>
                        @foreach ($beneficiaryServantOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('beneficiaryAssignedServantId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field"><span>الهاتف</span><input type="text" wire:model="beneficiaryPhone" placeholder="رقم الهاتف">@error('beneficiaryPhone') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>واتساب</span><input type="text" wire:model="beneficiaryWhatsapp" placeholder="رقم واتساب">@error('beneficiaryWhatsapp') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>اسم ولي الأمر</span><input type="text" wire:model="beneficiaryGuardianName" placeholder="اختياري">@error('beneficiaryGuardianName') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>هاتف ولي الأمر</span><input type="text" wire:model="beneficiaryGuardianPhone" placeholder="اختياري">@error('beneficiaryGuardianPhone') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field app-form-field-full"><span>العنوان</span><textarea wire:model="beneficiaryAddressText" rows="3" placeholder="العنوان أو ملاحظات الوصول"></textarea>@error('beneficiaryAddressText') <small>{{ $message }}</small> @enderror</label>
            </div>

            <div class="app-modal-actions">
                <button type="button" wire:click="closeBeneficiaryForm" class="app-secondary-button">إلغاء</button>
                <button type="button" wire:click="saveBeneficiary" class="app-primary-button">حفظ المخدوم</button>
            </div>
        </div>
    </section>
@endif
