<?php

namespace App\Filament\Resources\ScheduledVisits\Pages;

use App\Filament\Resources\ScheduledVisits\ScheduledVisitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScheduledVisit extends EditRecord
{
    protected static string $resource = ScheduledVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
