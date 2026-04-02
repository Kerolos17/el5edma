<?php

namespace App\Filament\Resources\PrayerRequests\Pages;

use App\Filament\Resources\PrayerRequests\PrayerRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrayerRequests extends ListRecords
{
    protected static string $resource = PrayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('prayer.add')),
        ];
    }
}
