<?php

namespace App\Filament\Resources\MedicalFiles\Pages;

use App\Filament\Resources\MedicalFiles\MedicalFileResource;
use App\Helpers\PermissionHelper;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMedicalFiles extends ListRecords
{
    protected static string $resource = MedicalFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('medical.upload_file'))
                ->visible(fn () => PermissionHelper::canModify()),
        ];
    }
}
