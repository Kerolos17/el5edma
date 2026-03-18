<?php

namespace App\Filament\Resources\MinistryNotifications\Pages;

use App\Filament\Resources\MinistryNotifications\MinistryNotificationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMinistryNotification extends EditRecord
{
    protected static string $resource = MinistryNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
