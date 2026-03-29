<?php
namespace App\Filament\Resources\Beneficiaries\Pages;

use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class ListBeneficiaries extends ListRecords
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('beneficiaries.add'))
                ->visible(fn() => in_array(
                    Auth::user()?->role,
                    [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]
                )),
        ];
    }
}
