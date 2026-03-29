<?php
namespace App\Filament\Resources\Beneficiaries\Pages;

use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Services\EagerLoadingService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class ViewBeneficiary extends ViewRecord
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn() => in_array(
                    Auth::user()?->role,
                    [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]
                )),

            Action::make('download_pdf')
                ->label($this->record->full_name . ' — PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn() => route('reports.beneficiary.pdf', $this->record))
                ->openUrlInNewTab(),

            Action::make('whatsapp_beneficiary')
                ->label(__('beneficiaries.whatsapp_beneficiary'))
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->url(fn() => $this->record->whatsapp_url)
                ->openUrlInNewTab()
                ->visible(fn() => filled($this->record->phone) || filled($this->record->whatsapp)),

            Action::make('whatsapp_guardian')
                ->label(__('beneficiaries.whatsapp_guardian'))
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('info')
                ->url(fn() => $this->record->guardian_whatsapp_url)
                ->openUrlInNewTab()
                ->visible(fn() => filled($this->record->guardian_phone)),
        ];
    }

    protected function modifyRecordQuery(Builder $query): Builder
    {
        return $query->with(EagerLoadingService::beneficiaryInfolist());
    }
}
