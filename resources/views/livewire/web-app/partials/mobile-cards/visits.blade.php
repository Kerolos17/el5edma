<strong>{{ $record->beneficiary?->full_name ?? 'بدون اسم' }}</strong>
<p>{{ optional($record->visit_date)->format('Y-m-d') }} · {{ $record->type ?: 'زيارة' }}</p>
<div class="app-mobile-meta">
    <span>{{ $record->createdBy?->name ?? 'غير محدد' }}</span>
    <span>{{ $record->is_critical ? 'حرجة' : 'مستقرة' }}</span>
</div>
