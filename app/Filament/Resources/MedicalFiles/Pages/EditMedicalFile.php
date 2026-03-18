<?php

namespace App\Filament\Resources\MedicalFiles\Pages;

use App\Filament\Resources\MedicalFiles\MedicalFileResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMedicalFile extends EditRecord
{
    protected static string $resource = MedicalFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
