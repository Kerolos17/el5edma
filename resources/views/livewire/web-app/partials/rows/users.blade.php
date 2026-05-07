<td>
    <strong>{{ $record->name }}</strong>
    <span>{{ $record->email }}</span>
</td>
<td>{{ $record->role?->label() ?? $record->role }}</td>
<td>{{ $record->serviceGroup?->name ?? 'غير محددة' }}</td>
<td>{{ $record->phone ?: '—' }}</td>
<td><span class="app-status-pill {{ $record->is_active ? 'tone-emerald' : 'tone-slate' }}">{{ $record->is_active ? 'نشط' : 'غير مفعل' }}</span></td>
<td>
    <div class="app-inline-actions">
        @if (auth()->user()->can('update', $record))
            <button type="button" wire:click="openUserForm({{ $record->id }})" class="app-link-inline">تعديل</button>
        @endif
        @if (auth()->id() !== $record->id && auth()->user()->can('update', $record))
            <button type="button" wire:click="toggleUserActive({{ $record->id }})" class="app-link-inline {{ $record->is_active ? 'app-link-danger' : '' }}">{{ $record->is_active ? 'تعطيل' : 'تفعيل' }}</button>
        @endif
    </div>
</td>
