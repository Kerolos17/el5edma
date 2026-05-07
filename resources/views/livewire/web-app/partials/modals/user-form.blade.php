@if ($showUserForm)
    <div class="app-modal-backdrop" wire:click="closeUserForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="إدارة مستخدم">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div><p class="app-section-label">إدارة المستخدمين</p><h3>{{ $editingUserId ? 'تعديل مستخدم' : 'إضافة مستخدم' }}</h3></div>
                <button type="button" wire:click="closeUserForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field"><span>الاسم</span><input type="text" wire:model="userName" placeholder="اسم المستخدم">@error('userName') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>البريد الإلكتروني</span><input type="email" wire:model="userEmail" placeholder="name@example.com">@error('userEmail') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>الهاتف</span><input type="text" wire:model="userPhone" placeholder="رقم الهاتف">@error('userPhone') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>{{ $editingUserId ? 'كلمة مرور جديدة' : 'كلمة المرور' }}</span><input type="password" wire:model="userPassword" placeholder="{{ $editingUserId ? 'اتركها فارغة بدون تغيير' : '8 أحرف على الأقل' }}">@error('userPassword') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field">
                    <span>الدور</span>
                    <select wire:model.live="userRole" @disabled($editingUserId && $editingUserId === auth()->id())>
                        <option value="">اختر الدور</option>
                        @foreach ($userRoleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('userRole') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>مجموعة الخدمة</span>
                    <select wire:model="userServiceGroupId" @disabled(in_array($userRole, ['super_admin', 'service_leader'], true) || ($editingUserId && $editingUserId === auth()->id()))>
                        <option value="">اختر المجموعة</option>
                        @foreach ($userServiceGroupOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('userServiceGroupId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field"><span>اللغة</span><select wire:model="userLocale"><option value="ar">العربية</option><option value="en">English</option></select>@error('userLocale') <small>{{ $message }}</small> @enderror</label>
                @if (! $editingUserId || $editingUserId !== auth()->id())
                    <div class="app-check-grid app-form-field-full"><label class="app-check-row"><input type="checkbox" wire:model="userIsActive"><span>الحساب مفعل</span></label></div>
                @endif
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeUserForm" class="app-secondary-button">إلغاء</button>
                <button type="button" wire:click="saveUser" class="app-primary-button">حفظ المستخدم</button>
            </div>
        </div>
    </section>
@endif
