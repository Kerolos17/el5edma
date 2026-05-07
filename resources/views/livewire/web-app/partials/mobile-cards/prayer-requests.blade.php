<strong>{{ $record->title }}</strong>
<p>{{ $record->beneficiary?->full_name ?? 'بدون اسم' }}</p>
<div class="app-mobile-meta">
    <span>{{ $record->status }}</span>
    <span>{{ optional($record->created_at)->format('Y-m-d') }}</span>
</div>
