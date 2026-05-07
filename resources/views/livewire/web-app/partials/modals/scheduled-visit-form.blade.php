@if ($showScheduledVisitForm)
    <div class="app-modal-backdrop" wire:click="closeScheduledVisitForm"></div>
    <section class="app-modal-sheet" role="dialog" aria-modal="true" aria-label="جدولة زيارة">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div><p class="app-section-label">تنظيم الزيارات</p><h3>جدولة زيارة</h3></div>
                <button type="button" wire:click="closeScheduledVisitForm" class="app-icon-button"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="app-form-grid">
                <label class="app-form-field">
                    <span>المخدوم</span>
                    <select wire:model="scheduledVisitBeneficiaryId">
                        <option value="">اختر المخدوم</option>
                        @foreach ($beneficiaryOptions as $beneficiary)
                            <option value="{{ $beneficiary->id }}">{{ $beneficiary->full_name }}{{ $beneficiary->code ? ' - ' . $beneficiary->code : '' }}</option>
                        @endforeach
                    </select>
                    @error('scheduledVisitBeneficiaryId') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field">
                    <span>الخدام المكلفون</span>
                    <div class="app-check-grid">
                        @forelse ($servantOptions as $servant)
                            <label class="app-check-row"><input type="checkbox" value="{{ $servant->id }}" wire:model="scheduledVisitAssignedServantIds"><span>{{ $servant->name }}</span></label>
                        @empty
                            <div class="app-check-empty">اختر المخدوم أولًا لعرض خدام نفس المجموعة.</div>
                        @endforelse
                    </div>
                    @if (count($scheduledVisitAssignedServantIds) > 0)
                        <div class="app-chip-row">
                            @foreach ($servantOptions->whereIn('id', array_map('intval', $scheduledVisitAssignedServantIds)) as $servant)
                                <span class="app-status-pill tone-blue">{{ $servant->name }}</span>
                            @endforeach
                        </div>
                    @endif
                    @error('scheduledVisitAssignedServantIds') <small>{{ $message }}</small> @enderror
                    @error('scheduledVisitAssignedServantIds.*') <small>{{ $message }}</small> @enderror
                </label>
                <label class="app-form-field"><span>التاريخ</span><input type="date" wire:model="scheduledVisitDate">@error('scheduledVisitDate') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field"><span>الوقت</span><input type="time" wire:model="scheduledVisitTime">@error('scheduledVisitTime') <small>{{ $message }}</small> @enderror</label>
                <label class="app-form-field app-form-field-full"><span>ملاحظات</span><textarea wire:model="scheduledVisitNotes" rows="4" placeholder="سبب الزيارة أو التنسيق المطلوب قبل الموعد"></textarea>@error('scheduledVisitNotes') <small>{{ $message }}</small> @enderror</label>
            </div>
            <div class="app-modal-actions">
                <button type="button" wire:click="closeScheduledVisitForm" class="app-secondary-button">إلغاء</button>
                <button type="button" wire:click="saveScheduledVisit" class="app-primary-button">حفظ الموعد</button>
            </div>
        </div>
    </section>
@endif
