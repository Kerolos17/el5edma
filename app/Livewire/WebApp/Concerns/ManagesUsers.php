<?php

declare(strict_types=1);

namespace App\Livewire\WebApp\Concerns;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait ManagesUsers
{
    public bool $showUserForm = false;

    public ?int $editingUserId = null;

    public string $userName = '';

    public string $userEmail = '';

    public string $userPhone = '';

    public string $userPassword = '';

    public string $userRole = '';

    public ?int $userServiceGroupId = null;

    public string $userLocale = 'ar';

    public bool $userIsActive = true;

    public function updatedUserRole(): void
    {
        $role = UserRole::tryFrom($this->userRole);

        if ($role?->isAdminLevel()) {
            $this->userServiceGroupId = null;
        }
    }

    public function openUserForm(?int $userId = null): void
    {
        $actor = auth()->user();

        $this->resetUserForm();

        if ($userId === null) {
            abort_unless($actor->can('create', User::class), 403);

            $this->userRole           = $this->userRoleOptionsForActor($actor)->keys()->first() ?? UserRole::Servant->value;
            $firstServiceGroupId      = $this->userServiceGroupOptionsForActor($actor)->keys()->first();
            $this->userServiceGroupId = $firstServiceGroupId ? (int) $firstServiceGroupId : null;
            $this->showUserForm       = true;

            return;
        }

        $record = WebAppScope::users($actor)->whereKey($userId)->firstOrFail();

        abort_unless($actor->can('update', $record), 403);

        $this->editingUserId      = $record->id;
        $this->userName           = $record->name;
        $this->userEmail          = $record->email;
        $this->userPhone          = (string) ($record->phone ?? '');
        $this->userRole           = $record->role->value;
        $this->userServiceGroupId = $record->service_group_id;
        $this->userLocale         = $record->locale ?: 'ar';
        $this->userIsActive       = (bool) $record->is_active;
        $this->showUserForm       = true;
    }

    public function closeUserForm(): void
    {
        $this->showUserForm = false;
        $this->resetUserForm();
    }

    public function saveUser(): void
    {
        $actor  = auth()->user();
        $record = $this->editingUserId
            ? WebAppScope::users($actor)->whereKey($this->editingUserId)->firstOrFail()
            : null;

        abort_unless($record ? $actor->can('update', $record) : $actor->can('create', User::class), 403);

        $roleOptions = $record && $record->id === $actor->id
            ? [$record->role->value]
            : $this->userRoleOptionsForActor($actor)->keys()->all();

        $data = $this->validate([
            'userName'           => ['required', 'string', 'max:255'],
            'userEmail'          => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($record?->id)],
            'userPhone'          => ['nullable', 'string', 'max:20'],
            'userPassword'       => [$record ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'userRole'           => ['required', Rule::in($roleOptions)],
            'userServiceGroupId' => ['nullable', 'integer'],
            'userLocale'         => ['required', Rule::in(['ar', 'en'])],
            'userIsActive'       => ['boolean'],
        ]);

        $role           = UserRole::from($data['userRole']);
        $serviceGroupId = $data['userServiceGroupId'] ? (int) $data['userServiceGroupId'] : null;

        if (in_array($role, [UserRole::FamilyLeader, UserRole::Servant], true) && $serviceGroupId === null) {
            throw ValidationException::withMessages([
                'userServiceGroupId' => __('web_app.validation.service_group_required_for_role'),
            ]);
        }

        $this->ensureUserAssignmentAllowed($actor, $record, $role, $serviceGroupId);

        $payload = [
            'name'   => $data['userName'],
            'email'  => $data['userEmail'],
            'phone'  => $data['userPhone'] ?: null,
            'locale' => $data['userLocale'],
        ];

        if ($record && $record->id === $actor->id) {
            $payload['is_active'] = true;
        } else {
            $payload['role']             = $role;
            $payload['service_group_id'] = $role->isAdminLevel() ? null : $serviceGroupId;
            $payload['is_active']        = (bool) $data['userIsActive'];
        }

        if ($data['userPassword']) {
            $payload['password'] = $data['userPassword'];
        }

        if ($record) {
            $record->update($payload);
            $message = __('web_app.toasts.user_updated');
        } else {
            User::create($payload);
            $message = __('web_app.toasts.user_created');
        }

        $this->showUserForm = false;
        $this->resetUserForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function toggleUserActive(int $userId): void
    {
        $actor  = auth()->user();
        $record = WebAppScope::users($actor)->whereKey($userId)->firstOrFail();

        abort_unless($actor->id !== $record->id && $actor->can('update', $record), 403);

        $this->ensureUserAssignmentAllowed($actor, $record, $record->role, $record->service_group_id);

        $record->update(['is_active' => ! $record->is_active]);
        $this->dispatch(
            'toast',
            message: $record->is_active ? __('web_app.toasts.user_enabled') : __('web_app.toasts.user_disabled'),
            type: 'success',
        );
    }

    public function deleteUser(int $id): void
    {
        $actor  = auth()->user();
        $record = WebAppScope::users($actor)->whereKey($id)->firstOrFail();
        abort_unless($actor->can('delete', $record) && $actor->id !== $record->id, 403);
        $record->delete();
        $this->dispatch('toast', message: __('web_app.toasts.user_deleted'), type: 'success');
    }

    public function approveUser(int $id): void
    {
        $actor = auth()->user();
        abort_unless($actor->can('update', new User), 403);

        $record = WebAppScope::users($actor)->whereKey($id)->firstOrFail();
        $record->update(['is_active' => true]);
        $this->dispatch('toast', message: __('web_app.toasts.user_enabled'), type: 'success');
    }

    private function resetUserForm(): void
    {
        $this->reset([
            'editingUserId',
            'userName',
            'userEmail',
            'userPhone',
            'userPassword',
            'userRole',
            'userServiceGroupId',
            'userLocale',
            'userIsActive',
        ]);

        $this->userLocale   = 'ar';
        $this->userIsActive = true;
    }

    private function userRoleOptionsForActor(User $actor): Collection
    {
        if ($actor->role === UserRole::SuperAdmin) {
            return collect(UserRole::options());
        }

        if ($actor->role === UserRole::ServiceLeader) {
            return collect([
                UserRole::FamilyLeader->value => UserRole::FamilyLeader->label(),
                UserRole::Servant->value      => UserRole::Servant->label(),
            ]);
        }

        return collect();
    }

    private function userServiceGroupOptionsForActor(User $actor): Collection
    {
        $query = ServiceGroup::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($actor->role === UserRole::ServiceLeader) {
            $query->where('service_leader_id', $actor->id);
        } elseif ($actor->role !== UserRole::SuperAdmin) {
            $query->whereRaw('1 = 0');
        }

        return $query->pluck('name', 'id');
    }

    private function ensureUserAssignmentAllowed(User $actor, ?User $record, UserRole $role, ?int $serviceGroupId): void
    {
        if ($record && $record->id === $actor->id) {
            return;
        }

        if ($role->isAdminLevel()) {
            if ($actor->role !== UserRole::SuperAdmin) {
                throw ValidationException::withMessages([
                    'userRole' => __('web_app.validation.role_not_allowed'),
                ]);
            }

            return;
        }

        $allowedGroupIds = $this->userServiceGroupOptionsForActor($actor)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! $serviceGroupId || ! in_array($serviceGroupId, $allowedGroupIds, true)) {
            throw ValidationException::withMessages([
                'userServiceGroupId' => __('web_app.validation.service_group_out_of_scope'),
            ]);
        }

        if ($actor->role === UserRole::ServiceLeader && ! in_array($role, [UserRole::FamilyLeader, UserRole::Servant], true)) {
            throw ValidationException::withMessages([
                'userRole' => __('web_app.validation.service_leader_role_scope'),
            ]);
        }
    }
}
