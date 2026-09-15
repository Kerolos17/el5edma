<?php

namespace App\Filament\Resources\PrayerRequests\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\PrayerRequests\PrayerRequestResource;
use App\Models\Beneficiary;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreatePrayerRequest extends CreateRecord
{
    protected static string $resource = PrayerRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateBeneficiaryScope($data['beneficiary_id']);

        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function validateBeneficiaryScope(int $beneficiaryId): void
    {
        $user = Auth::user();

        if ($user->role === UserRole::SuperAdmin) {
            return;
        }

        $query = Beneficiary::where('id', $beneficiaryId)->where('status', 'active');

        match ($user->role) {
            UserRole::ServiceLeader => $query->whereIn('service_group_id', $user->managedServiceGroupIds()),
            UserRole::FamilyLeader  => $query->where('service_group_id', $user->service_group_id),
            UserRole::Servant       => $query->where('assigned_servant_id', $user->id),
            default                 => $query->whereRaw('1 = 0'),
        };

        if (! $query->exists()) {
            throw ValidationException::withMessages([
                'data.beneficiary_id' => __('web_app.validation.service_group_out_of_scope'),
            ]);
        }
    }
}
