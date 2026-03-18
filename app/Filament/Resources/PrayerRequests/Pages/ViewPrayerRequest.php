<?php
namespace App\Filament\Resources\PrayerRequests\Pages;

use App\Filament\Resources\PrayerRequests\PrayerRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPrayerRequest extends ViewRecord
{
    protected static string $resource = PrayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn() => \App\Helpers\PermissionHelper::canModify()),

            \Filament\Actions\Action::make('mark_answered')
                ->label(__('prayer.mark_answered'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() => \App\Helpers\PermissionHelper::canModify() && $this->record->status === 'open')
                ->action(function () {
                    $this->record->update([
                        'status'      => 'answered',
                        'answered_at' => now(),
                    ]);
                    $this->refreshFormData(['status', 'answered_at']);
                }),
        ];
    }
}
