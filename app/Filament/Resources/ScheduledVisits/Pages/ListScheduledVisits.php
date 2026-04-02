<?php

namespace App\Filament\Resources\ScheduledVisits\Pages;

use App\Filament\Resources\ScheduledVisits\ScheduledVisitResource;
use App\Helpers\PermissionHelper;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScheduledVisits extends ListRecords
{
    protected static string $resource = ScheduledVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('visits.schedule'))
                ->visible(fn () => PermissionHelper::canModify()),
        ];
    }
}
