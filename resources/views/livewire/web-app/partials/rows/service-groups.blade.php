<td>
    <strong>{{ $record->name }}</strong>
    <span>{{ $record->description ?: 'بدون وصف' }}</span>
</td>
<td>{{ $record->leader?->name ?? 'غير محدد' }}</td>
<td>{{ $record->serviceLeader?->name ?? 'غير محدد' }}</td>
<td>{{ number_format($record->beneficiaries_count) }}</td>
<td>{{ number_format($record->servants_count) }}</td>
<td>
    <div class="app-inline-actions">
        @if (auth()->user()->can('update', $record))
            <button type="button" wire:click="openServiceGroupForm({{ $record->id }})" class="app-link-inline">تعديل</button>
            <button type="button" wire:click="toggleServiceGroupActive({{ $record->id }})" class="app-link-inline {{ $record->is_active ? 'app-link-danger' : '' }}">{{ $record->is_active ? 'تعطيل' : 'تفعيل' }}</button>
        @endif
    </div>
</td>
