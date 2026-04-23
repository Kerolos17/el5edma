<?php

namespace App\Filament\Resources\Beneficiaries\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateBeneficiary extends CreateRecord
{
    protected static string $resource = BeneficiaryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $this->guardBeneficiaryAssignments($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function guardBeneficiaryAssignments(array $data): array
    {
        $actor           = Auth::user();
        $allowedGroupIds = $actor->managedServiceGroupIds();

        if ($actor->role === UserRole::FamilyLeader) {
            if (isset($data['service_group_id']) && (int) $data['service_group_id'] !== (int) $actor->service_group_id) {
                throw ValidationException::withMessages([
                    'service_group_id' => __('users.unauthorized_role'),
                ]);
            }

            $data['service_group_id'] = $actor->service_group_id;
        } elseif ($actor->role !== UserRole::SuperAdmin && ! in_array((int) ($data['service_group_id'] ?? 0), $allowedGroupIds, true)) {
            throw ValidationException::withMessages([
                'service_group_id' => __('users.unauthorized_role'),
            ]);
        }

        if (! empty($data['assigned_servant_id'])) {
            $servant = User::query()
                ->whereKey($data['assigned_servant_id'])
                ->where('role', UserRole::Servant)
                ->where('is_active', true)
                ->first();

            if (! $servant || (int) $servant->service_group_id !== (int) $data['service_group_id']) {
                throw ValidationException::withMessages([
                    'assigned_servant_id' => __('users.unauthorized_role'),
                ]);
            }
        }

        return $data;
    }
}
