<?php

namespace App\Filament\Resources\ScheduledVisits\Pages;

use App\Filament\Resources\ScheduledVisits\ScheduledVisitResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewScheduledVisit extends ViewRecord
{
    protected static string $resource = ScheduledVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => Auth::user()->can('update', $this->record)
                    && $this->record->status === 'pending',
                ),
        ];
    }
}
