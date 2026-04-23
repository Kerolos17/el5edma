<?php

namespace App\Filament\Resources\Beneficiaries\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditBeneficiary extends EditRecord
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
        } elseif ($actor->role !== UserRole::SuperAdmin && ! in_array((int) ($data['service_group_id'] ?? $this->record->service_group_id), $allowedGroupIds, true)) {
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
