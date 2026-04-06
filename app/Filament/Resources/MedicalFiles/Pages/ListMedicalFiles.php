<?php

namespace App\Filament\Resources\MedicalFiles\Pages;

use App\Filament\Resources\MedicalFiles\MedicalFileResource;
use App\Models\MedicalFile;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListMedicalFiles extends ListRecords
{
    protected static string $resource = MedicalFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('medical.upload_file'))
                ->visible(fn () => Auth::user()->can('create', MedicalFile::class)),
        ];
    }
}
