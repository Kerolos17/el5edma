<strong>{{ $record->name }}</strong>
<p>{{ $record->serviceGroup?->name ?? 'غير محددة' }}</p>
<div class="app-mobile-meta">
    <span>{{ $record->role?->label() ?? $record->role }}</span>
    <span>{{ $record->is_active ? 'نشط' : 'غير مفعل' }}</span>
</div>
