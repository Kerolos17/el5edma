<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->guardUserAssignments($data);
    }

    private function guardUserAssignments(array $data): array
    {
        $actor = Auth::user();
        $role  = UserRole::from($data['role']);

        if ($actor->role === UserRole::SuperAdmin) {
            if (in_array($role, [UserRole::SuperAdmin, UserRole::ServiceLeader], true)) {
                $data['service_group_id'] = null;

                return $data;
            }

            return $data;
        }

        if ($actor->role !== UserRole::ServiceLeader) {
            throw ValidationException::withMessages([
                'role' => __('users.unauthorized_role'),
            ]);
        }

        if (! in_array($role, [UserRole::FamilyLeader, UserRole::Servant], true)) {
            throw ValidationException::withMessages([
                'role' => __('users.unauthorized_role'),
            ]);
        }

        if (! $actor->managesServiceGroup((int) ($data['service_group_id'] ?? 0))) {
            throw ValidationException::withMessages([
                'service_group_id' => __('users.unauthorized_role'),
            ]);
        }

        return $data;
    }
}
