<strong>{{ $record->full_name }}</strong>
<p>{{ $record->serviceGroup?->name ?? 'غير محددة' }}</p>
<div class="app-mobile-meta">
    <span>{{ $record->assignedServant?->name ?? 'غير معين' }}</span>
    <span>{{ number_format($record->visits_count) }} زيارة</span>
</div>
