<?php

namespace App\Filament\Resources\ScheduledVisits\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\ScheduledVisits\ScheduledVisitResource;
use App\Models\Beneficiary;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditScheduledVisit extends EditRecord
{
    protected static string $resource = ScheduledVisitResource::class;

    protected array $pendingAssignedServantIds = [];

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $actor       = Auth::user();
        $beneficiary = Beneficiary::query()->find($data['beneficiary_id'] ?? $this->record->beneficiary_id);

        if (! $beneficiary || ! $actor->managesServiceGroup($beneficiary->service_group_id)) {
            throw ValidationException::withMessages([
                'beneficiary_id' => __('users.unauthorized_role'),
            ]);
        }

        $servantIds = collect($data['assigned_servant_ids'] ?? $this->record->servants()->pluck('users.id')->all())
            ->whenEmpty(fn ($collection) => $this->record->assigned_servant_id ? $collection->push($this->record->assigned_servant_id) : $collection)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($servantIds === []) {
            throw ValidationException::withMessages([
                'assigned_servant_ids' => __('users.unauthorized_role'),
            ]);
        }

        $validIds = User::query()
            ->whereIn('id', $servantIds)
            ->where('role', UserRole::Servant)
            ->where('is_active', true)
            ->where('service_group_id', $beneficiary->service_group_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($servantIds !== $validIds) {
            throw ValidationException::withMessages([
                'assigned_servant_ids' => __('users.unauthorized_role'),
            ]);
        }

        $this->pendingAssignedServantIds = $servantIds;
        $data['assigned_servant_id']     = $servantIds[0];
        unset($data['assigned_servant_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncAssignedServants($this->pendingAssignedServantIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
