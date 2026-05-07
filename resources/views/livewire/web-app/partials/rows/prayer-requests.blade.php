<td>
    <strong>{{ $record->title }}</strong>
    <span>{{ \Illuminate\Support\Str::limit($record->body ?: 'بدون تفاصيل', 46) }}</span>
</td>
<td>{{ $record->beneficiary?->full_name ?? 'بدون اسم' }}</td>
<td><span class="app-status-pill tone-slate">{{ $record->status }}</span></td>
<td>{{ $record->createdBy?->name ?? 'غير محدد' }}</td>
<td>{{ optional($record->created_at)->format('Y-m-d') }}</td>
<td>
    <div class="app-inline-actions">
        @if (auth()->user()->can('update', $record))
            @if ($record->status === 'open')
                <button type="button" wire:click="markPrayerAnswered({{ $record->id }})" class="app-link-inline">مستجاب</button>
                <button type="button" wire:click="closePrayerRequest({{ $record->id }})" class="app-link-inline app-link-danger">إغلاق</button>
            @else
                <button type="button" wire:click="reopenPrayerRequest({{ $record->id }})" class="app-link-inline">إعادة فتح</button>
            @endif
        @endif
    </div>
</td>
