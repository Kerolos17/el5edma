<?php
namespace App\Filament\Resources\ServiceGroups\Pages;

use App\Filament\Resources\ServiceGroups\ServiceGroupResource;
use App\Services\RegistrationLinkService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewServiceGroup extends ViewRecord
{
    protected static string $resource = ServiceGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            // رابط التسجيل الذاتي للخدام
            Action::make('registration_link')
                ->label(__('service_groups.registration_link'))
                ->icon('heroicon-o-link')
                ->color('success')
                ->visible(fn() => Auth::user()->can('manageRegistrationLink', $this->record))
                ->modalHeading(__('service_groups.registration_link'))
                ->modalContent(fn() => view('filament.modals.registration-link', [
                    'url'             => app(RegistrationLinkService::class)->generateRegistrationUrl($this->record),
                    'registeredCount' => $this->record->getSelfRegisteredServantsCount(),
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('service_groups.close')),

            // إعادة توليد رابط التسجيل
            Action::make('regenerate_token')
                ->label(__('service_groups.regenerate_token'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('service_groups.regenerate_token'))
                ->modalDescription(__('service_groups.regenerate_token_confirm'))
                ->visible(fn() => Auth::user()->can('manageRegistrationLink', $this->record))
                ->action(function () {
                    app(RegistrationLinkService::class)->regenerateToken($this->record);

                    Notification::make()
                        ->success()
                        ->title(__('service_groups.token_regenerated'))
                        ->send();
                }),

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
        ];
    }
}
