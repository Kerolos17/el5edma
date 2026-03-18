<?php

namespace App\Filament\Resources\ServiceGroups\Pages;

use App\Filament\Resources\ServiceGroups\ServiceGroupResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewServiceGroup extends ViewRecord
{
    protected static string $resource = ServiceGroupResource::class;

    protected function getHeaderActions(): array    {
        return [
            EditAction::make(),

            Action::make('group_report_pdf')
            ->label($this->record->name . ' — PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->url(fn() => route('reports.service-group.pdf', $this->record))
            ->openUrlInNewTab(),

            Action::make('group_beneficiaries_pdf')
            ->label(__('service_groups.beneficiaries_tab') . ' — PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->url(fn() => route('reports.service-group.beneficiaries.pdf', $this->record))
            ->openUrlInNewTab(),
        ];    }
}
