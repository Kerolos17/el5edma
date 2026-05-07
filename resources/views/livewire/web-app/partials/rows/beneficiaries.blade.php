<td>
    <strong>{{ $record->full_name }}</strong>
    <span>{{ $record->code ?: 'بدون كود' }}</span>
</td>
<td>{{ $record->serviceGroup?->name ?? 'غير محددة' }}</td>
<td>{{ $record->assignedServant?->name ?? 'غير معين' }}</td>
<td>{{ number_format($record->visits_count) }}</td>
<td><span class="app-status-pill tone-slate">{{ $record->status ?: 'نشط' }}</span></td>
<td>
    <div class="app-inline-actions">
        @if (auth()->user()->can('update', $record))
            <button type="button" wire:click="openBeneficiaryForm({{ $record->id }})" class="app-link-inline">
                <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                تعديل
            </button>
        @endif
        <button type="button" wire:click="openVisitForm({{ $record->id }})" class="app-link-inline">
            <i class="ph ph-plus" aria-hidden="true"></i>
            زيارة
        </button>
        <button type="button" wire:click="openPrayerForm({{ $record->id }})" class="app-link-inline">
            <i class="ph ph-hands-praying" aria-hidden="true"></i>
            صلاة
        </button>
        @if (auth()->user()->can('create', \App\Models\MedicalFile::class))
            <button type="button" wire:click="openMedicalFileForm({{ $record->id }})" class="app-link-inline">
                <i class="ph ph-upload-simple" aria-hidden="true"></i>
                ملف طبي
            </button>
        @endif
    </div>
</td>
