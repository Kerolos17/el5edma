<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Helpers\PermissionHelper;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class ViewVisit extends ViewRecord
{
    protected static string $resource = VisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => PermissionHelper::canModify()
                    && (! $this->record->is_critical
                        || in_array(Auth::user()?->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]))
                ),

            Action::make('resolve_critical')
                ->label(__('visits.critical_resolved'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->is_critical
                    && is_null($this->record->critical_resolved_at)
                    && in_array(Auth::user()?->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader])
                )
                ->action(function () {
                    $this->record->update([
                        'critical_resolved_at' => now(),
                        'critical_resolved_by' => Auth::id(),
                    ]);
                    $this->refreshFormData([
                        'critical_resolved_at',
                        'critical_resolved_by',
                    ]);
                }),
        ];
    }
}
