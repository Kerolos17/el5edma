@if ($showServiceGroupForm)
    <div class="app-modal-backdrop" wire:click="closeServiceGroupForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="إدارة مجموعة خدمة">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div><p class="app-section-label">إدارة المجموعات</p><h3>{{ $editingServiceGroupId ? 'تعديل مجموعة' : 'إضافة مجموعة' }}</h3></div>
                <button type="button" wire:click="closeServiceGroupForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field"><span>اسم المجموعة</span><input type="text" wire:model="serviceGroupName" placeholder="مثال: أسرة مارمرقس">@error('serviceGroupName') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field">
                    <span>أمين الخدمة</span>
                    <select wire:model="serviceGroupServiceLeaderId" @disabled(auth()->user()->isServiceLeader())>
                        <option value="">غير محدد</option>
                        @foreach ($serviceGroupServiceLeaderOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('serviceGroupServiceLeaderId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>أمين الأسرة</span>
                    <select wire:model="serviceGroupLeaderId" @disabled(! $editingServiceGroupId)>
                        <option value="">غير محدد</option>
                        @foreach ($serviceGroupLeaderOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('serviceGroupLeaderId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field app-form-field-full"><span>الوصف</span><textarea wire:model="serviceGroupDescription" rows="4" placeholder="ملاحظات داخلية عن نطاق المجموعة أو طريقة المتابعة"></textarea>@error('serviceGroupDescription') <small>{{ $message }}</small> @enderror</label>
                <div class="app-check-grid app-form-field-full"><label class="app-check-row"><input type="checkbox" wire:model="serviceGroupIsActive"><span>المجموعة نشطة</span></label></div>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeServiceGroupForm" class="app-secondary-button">إلغاء</button>
                <button type="button" wire:click="saveServiceGroup" class="app-primary-button">حفظ المجموعة</button>
            </div>
        </div>
    </section>
@endif
