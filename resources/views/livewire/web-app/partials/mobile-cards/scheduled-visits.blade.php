<strong>{{ $record->beneficiary?->full_name ?? 'بدون اسم' }}</strong>
<p>{{ $record->scheduled_date?->format('Y-m-d') }} · {{ $record->assigned_servant_names }}</p>
<div class="app-mobile-meta">
    <span>{{ $record->status }}</span>
    <span>{{ \Illuminate\Support\Str::limit($record->notes ?: 'بدون ملاحظات', 32) }}</span>
</div>
