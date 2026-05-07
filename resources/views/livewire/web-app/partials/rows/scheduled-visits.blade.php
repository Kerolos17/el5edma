<td>
    <strong>{{ $record->beneficiary?->full_name ?? 'بدون اسم' }}</strong>
    <span>{{ $record->beneficiary?->serviceGroup?->name ?? 'بدون مجموعة' }}</span>
</td>
<td>{{ $record->scheduled_date?->format('Y-m-d') }}{{ $record->scheduled_time ? ' - ' . $record->scheduled_time : '' }}</td>
<td>{{ $record->assigned_servant_names }}</td>
<td><span class="app-status-pill tone-slate">{{ $record->status }}</span></td>
<td>{{ \Illuminate\Support\Str::limit($record->notes ?: '—', 44) }}</td>
<td>
    <div class="app-inline-actions">
        @if ($record->status === 'pending')
            @if (auth()->user()->can('update', $record))
                <button type="button" wire:click="editScheduledVisit({{ $record->id }})" class="app-link-inline">تعديل</button>
            @endif
            <button type="button" wire:click="openVisitFromScheduled({{ $record->id }})" class="app-link-inline">تسجيل الآن</button>
            @if (auth()->user()->can('delete', $record) || (auth()->user()->isServant() && $record->isAssignedTo(auth()->id())))
                <button type="button" wire:click="cancelScheduledVisit({{ $record->id }})" class="app-link-inline app-link-danger">إلغاء</button>
            @endif
        @endif
    </div>
</td>
