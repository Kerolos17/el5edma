<?php

namespace App\Filament\Resources\ScheduledVisits\Pages;

use App\Filament\Resources\ScheduledVisits\ScheduledVisitResource;
use App\Helpers\PermissionHelper;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewScheduledVisit extends ViewRecord
{
    protected static string $resource = ScheduledVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => PermissionHelper::canModify()
                    && $this->record->status === 'pending',
                ),
        ];
    }
}
