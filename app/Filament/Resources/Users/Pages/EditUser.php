<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $actor = Auth::user();

        if ($this->record->id === $actor->id) {
            unset($data['role'], $data['service_group_id'], $data['is_active']);

            return $data;
        }

        $targetRole           = UserRole::from($data['role'] ?? $this->record->role->value);
        $targetServiceGroupId = (int) ($data['service_group_id'] ?? $this->record->service_group_id);

        if ($actor->role === UserRole::SuperAdmin) {
            if (in_array($targetRole, [UserRole::SuperAdmin, UserRole::ServiceLeader], true)) {
                $data['service_group_id'] = null;
            }

            return $data;
        }

        if ($actor->role !== UserRole::ServiceLeader) {
            throw ValidationException::withMessages([
                'role' => __('users.unauthorized_role'),
            ]);
        }

        if (! in_array($this->record->role, [UserRole::FamilyLeader, UserRole::Servant], true)
            || ! in_array($targetRole, [UserRole::FamilyLeader, UserRole::Servant], true)) {
            throw ValidationException::withMessages([
                'role' => __('users.unauthorized_role'),
            ]);
        }

        if (! $actor->managesServiceGroup($this->record->service_group_id)
            || ! $actor->managesServiceGroup($targetServiceGroupId)) {
            throw ValidationException::withMessages([
                'service_group_id' => __('users.unauthorized_role'),
            ]);
        }

        return $data;
    }
}
