<?php

namespace App\Filament\Resources\MedicalFiles\Pages;

use App\Filament\Resources\MedicalFiles\MedicalFileResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMedicalFile extends CreateRecord
{
    protected static string $resource = MedicalFileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
