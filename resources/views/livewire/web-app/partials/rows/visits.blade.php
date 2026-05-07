<td>
    <strong>{{ $record->beneficiary?->full_name ?? 'بدون اسم' }}</strong>
    <span>{{ $record->beneficiary?->serviceGroup?->name ?? 'بدون مجموعة' }}</span>
</td>
<td>{{ optional($record->visit_date)->format('Y-m-d') }}</td>
<td>{{ $record->type ?: 'زيارة' }}</td>
<td>{{ $record->createdBy?->name ?? 'غير محدد' }}</td>
<td>
    @if ($record->is_critical)
        <span class="app-status-pill tone-rose">حرجة</span>
    @elseif ($record->needs_family_leader || $record->needs_service_leader)
        <span class="app-status-pill tone-amber">تحتاج متابعة</span>
    @else
        <span class="app-status-pill tone-emerald">مستقرة</span>
    @endif
</td>
<td>
    <div class="app-inline-actions">
        @if (auth()->user()->can('update', $record))
            <button type="button" wire:click="editVisit({{ $record->id }})" class="app-link-inline">
                <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                تعديل
            </button>
            @if ($record->is_critical || $record->needs_family_leader || $record->needs_service_leader)
                <button type="button" wire:click="resolveVisitFollowUp({{ $record->id }})" class="app-link-inline">
                    <i class="ph ph-check-circle" aria-hidden="true"></i>
                    إغلاق المتابعة
                </button>
            @endif
        @endif
    </div>
</td>
