<?php

namespace App\Filament\Resources\MinistryNotifications\Pages;

use App\Filament\Resources\MinistryNotifications\MinistryNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMinistryNotifications extends ListRecords
{
    protected static string $resource = MinistryNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
