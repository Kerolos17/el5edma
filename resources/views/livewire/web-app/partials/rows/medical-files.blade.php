<td>
    <strong>{{ $record->title }}</strong>
    <span>{{ optional($record->created_at)->format('Y-m-d') }}</span>
</td>
<td>{{ $record->beneficiary?->full_name ?? 'بدون اسم' }}</td>
<td>{{ $record->file_type ?: 'عام' }}</td>
<td>{{ $record->uploadedBy?->name ?? 'غير محدد' }}</td>
<td><a href="{{ route('medical-files.download', $record) }}" class="app-link-inline">تنزيل</a></td>
