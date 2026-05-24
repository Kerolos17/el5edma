<?php

declare(strict_types=1);

namespace App\Livewire\WebApp\Concerns;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait ManagesServiceGroups
{
    public bool $showServiceGroupForm = false;

    public ?int $editingServiceGroupId = null;

    public string $serviceGroupName = '';

    public string $serviceGroupDescription = '';

    public ?int $serviceGroupLeaderId = null;

    public ?int $serviceGroupServiceLeaderId = null;

    public bool $serviceGroupIsActive = true;

    public function openServiceGroupForm(?int $serviceGroupId = null): void
    {
        $actor = auth()->user();

        $this->resetServiceGroupForm();

        if ($serviceGroupId === null) {
            abort_unless($actor->can('create', ServiceGroup::class), 403);

            $this->serviceGroupServiceLeaderId = $actor->isServiceLeader() ? $actor->id : null;
            $this->showServiceGroupForm = true;

            return;
        }

        $record = WebAppScope::serviceGroups($actor)->whereKey($serviceGroupId)->firstOrFail();

        abort_unless($actor->can('update', $record), 403);

        $this->editingServiceGroupId = $record->id;
        $this->serviceGroupName = $record->name;
        $this->serviceGroupDescription = (string) ($record->description ?? '');
        $this->serviceGroupLeaderId = $record->leader_id;
        $this->serviceGroupServiceLeaderId = $record->service_leader_id;
        $this->serviceGroupIsActive = (bool) $record->is_active;
        $this->showServiceGroupForm = true;
    }

    public function closeServiceGroupForm(): void
    {
        $this->showServiceGroupForm = false;
        $this->resetServiceGroupForm();
    }

    public function saveServiceGroup(): void
    {
        $actor = auth()->user();
        $record = $this->editingServiceGroupId
            ? WebAppScope::serviceGroups($actor)->whereKey($this->editingServiceGroupId)->firstOrFail()
            : null;

        abort_unless($record ? $actor->can('update', $record) : $actor->can('create', ServiceGroup::class), 403);

        $data = $this->validate([
            'serviceGroupName' => ['required', 'string', 'max:255'],
            'serviceGroupDescription' => ['nullable', 'string', 'max:1000'],
            'serviceGroupLeaderId' => ['nullable', 'integer'],
            'serviceGroupServiceLeaderId' => ['nullable', 'integer'],
            'serviceGroupIsActive' => ['boolean'],
        ]);

        $leaderId = $data['serviceGroupLeaderId'] ? (int) $data['serviceGroupLeaderId'] : null;
        $serviceLeaderId = $actor->isServiceLeader()
            ? $actor->id
            : ($data['serviceGroupServiceLeaderId'] ? (int) $data['serviceGroupServiceLeaderId'] : null);

        $this->ensureServiceGroupLeadersAllowed($actor, $record, $leaderId, $serviceLeaderId);

        $payload = [
            'name' => $data['serviceGroupName'],
            'description' => $data['serviceGroupDescription'] ?: null,
            'leader_id' => $leaderId,
            'service_leader_id' => $serviceLeaderId,
            'is_active' => (bool) $data['serviceGroupIsActive'],
        ];

        if ($record) {
            $record->update($payload);
            $this->syncInactiveServiceGroupBeneficiaries($record);
            $message = __('web_app.toasts.service_group_updated');
        } else {
            ServiceGroup::create($payload);
            $message = __('web_app.toasts.service_group_created');
        }

        $this->showServiceGroupForm = false;
        $this->resetServiceGroupForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function toggleServiceGroupActive(int $serviceGroupId): void
    {
        $actor = auth()->user();
        $record = WebAppScope::serviceGroups($actor)->whereKey($serviceGroupId)->firstOrFail();

        abort_unless($actor->can('update', $record), 403);

        $record->update(['is_active' => ! $record->is_active]);
        $this->syncInactiveServiceGroupBeneficiaries($record);
        $this->dispatch(
            'toast',
            message: $record->is_active ? __('web_app.toasts.service_group_enabled') : __('web_app.toasts.service_group_disabled'),
            type: 'success'
        );
    }

    private function resetServiceGroupForm(): void
    {
        $this->reset([
            'editingServiceGroupId',
            'serviceGroupName',
            'serviceGroupDescription',
            'serviceGroupLeaderId',
            'serviceGroupServiceLeaderId',
            'serviceGroupIsActive',
        ]);

        $this->serviceGroupIsActive = true;
    }

    private function serviceGroupLeaderOptions(User $actor): Collection
    {
        $query = User::query()
            ->where('role', UserRole::FamilyLeader)
            ->where('is_active', true)
            ->orderBy('name');

        if ($actor->role === UserRole::ServiceLeader) {
            $query->whereIn('service_group_id', $actor->managedServiceGroupIds());
        } elseif ($actor->role === UserRole::FamilyLeader) {
            $query->whereKey($actor->id);
        } elseif ($actor->role !== UserRole::SuperAdmin) {
            $query->whereRaw('1 = 0');
        }

        return $query->pluck('name', 'id');
    }

    private function serviceGroupServiceLeaderOptions(User $actor): Collection
    {
        if ($actor->isServiceLeader()) {
            return collect([$actor->id => $actor->name]);
        }

        if ($actor->role !== UserRole::SuperAdmin) {
            return collect();
        }

        return User::query()
            ->where('role', UserRole::ServiceLeader)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    private function ensureServiceGroupLeadersAllowed(User $actor, ?ServiceGroup $record, ?int $leaderId, ?int $serviceLeaderId): void
    {
        if ($leaderId !== null) {
            if (! $record) {
                throw ValidationException::withMessages([
                    'serviceGroupLeaderId' => __('web_app.validation.group_leader_after_create'),
                ]);
            }

            $leader = User::query()
                ->whereKey($leaderId)
                ->where('role', UserRole::FamilyLeader)
                ->where('is_active', true)
                ->first();

            if (! $leader) {
                throw ValidationException::withMessages([
                    'serviceGroupLeaderId' => __('web_app.validation.invalid_family_leader'),
                ]);
            }

            if ($record && $leader->service_group_id !== $record->id) {
                throw ValidationException::withMessages([
                    'serviceGroupLeaderId' => __('web_app.validation.family_leader_must_match_group'),
                ]);
            }

            if ($actor->isServiceLeader() && ! in_array($leader->service_group_id, $actor->managedServiceGroupIds(), true)) {
                throw ValidationException::withMessages([
                    'serviceGroupLeaderId' => __('web_app.validation.family_leader_out_of_scope'),
                ]);
            }
        }

        if ($serviceLeaderId === null) {
            return;
        }

        if ($actor->isServiceLeader() && $serviceLeaderId !== $actor->id) {
            throw ValidationException::withMessages([
                'serviceGroupServiceLeaderId' => __('web_app.validation.cannot_assign_other_service_leader'),
            ]);
        }

        if (! User::query()->whereKey($serviceLeaderId)->where('role', UserRole::ServiceLeader)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'serviceGroupServiceLeaderId' => __('web_app.validation.invalid_service_leader'),
            ]);
        }
    }

    private function syncInactiveServiceGroupBeneficiaries(ServiceGroup $serviceGroup): void
    {
        if ($serviceGroup->is_active) {
            return;
        }

        Beneficiary::query()
            ->where('service_group_id', $serviceGroup->id)
            ->update(['status' => 'inactive']);
    }
}
