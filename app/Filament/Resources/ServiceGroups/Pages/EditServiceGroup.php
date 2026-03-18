<?php

namespace App\Filament\Resources\ServiceGroups\Pages;

use App\Filament\Resources\ServiceGroups\ServiceGroupResource;
use App\Models\Beneficiary;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceGroup extends EditRecord
{
    protected static string $resource = ServiceGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    // عند تعطيل الأسرة → كل مخدوميها يصبحون غير نشطين
    protected function afterSave(): void
    {
        if (! $this->record->is_active) {
            Beneficiary::where('service_group_id', $this->record->id)
                ->update(['status' => 'inactive']);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
