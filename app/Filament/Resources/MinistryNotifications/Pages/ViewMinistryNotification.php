<?php

namespace App\Filament\Resources\MinistryNotifications\Pages;

use App\Filament\Resources\MinistryNotifications\MinistryNotificationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMinistryNotification extends ViewRecord
{
    protected static string $resource = MinistryNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
