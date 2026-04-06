<?php

namespace App\Filament\Resources\ScheduledVisits\Pages;

use App\Filament\Resources\ScheduledVisits\ScheduledVisitResource;
use App\Models\ScheduledVisit;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListScheduledVisits extends ListRecords
{
    protected static string $resource = ScheduledVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('visits.schedule'))
                ->visible(fn () => Auth::user()->can('create', ScheduledVisit::class)),
        ];
    }
}
