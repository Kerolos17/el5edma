<strong>{{ $record->title }}</strong>
<p>{{ $record->beneficiary?->full_name ?? 'بدون اسم' }}</p>
<div class="app-mobile-meta">
    <span>{{ $record->file_type ?: 'عام' }}</span>
    <a href="{{ route('medical-files.download', $record) }}">تنزيل</a>
</div>
