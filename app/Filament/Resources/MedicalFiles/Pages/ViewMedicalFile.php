<?php

namespace App\Filament\Resources\MedicalFiles\Pages;

use App\Filament\Resources\MedicalFiles\MedicalFileResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicalFile extends ViewRecord
{
    protected static string $resource = MedicalFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label(app()->getLocale() === 'ar' ? 'تحميل الملف' : 'Download File')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn () => route('medical-files.download', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
