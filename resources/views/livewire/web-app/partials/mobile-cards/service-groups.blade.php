<strong>{{ $record->name }}</strong>
<p>{{ $record->leader?->name ?? 'بدون أمين أسرة' }}</p>
<div class="app-mobile-meta">
    <span>{{ number_format($record->beneficiaries_count) }} مخدوم</span>
    <span>{{ number_format($record->servants_count) }} خادم</span>
</div>
