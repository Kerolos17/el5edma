<?php

namespace App\Filament\Resources\ScheduledVisits\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\ScheduledVisits\ScheduledVisitResource;
use App\Models\Beneficiary;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateScheduledVisit extends CreateRecord
{
    protected static string $resource = ScheduledVisitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $this->guardScheduledVisitAssignments($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function guardScheduledVisitAssignments(array $data): array
    {
        $actor       = Auth::user();
        $beneficiary = Beneficiary::query()->find($data['beneficiary_id'] ?? null);

        if (! $beneficiary || ! $actor->managesServiceGroup($beneficiary->service_group_id)) {
            throw ValidationException::withMessages([
                'beneficiary_id' => __('users.unauthorized_role'),
            ]);
        }

        $servant = User::query()
            ->whereKey($data['assigned_servant_id'] ?? null)
            ->where('role', UserRole::Servant)
            ->where('is_active', true)
            ->first();

        if (! $servant || (int) $servant->service_group_id !== (int) $beneficiary->service_group_id) {
            throw ValidationException::withMessages([
                'assigned_servant_id' => __('users.unauthorized_role'),
            ]);
        }

        return $data;
    }
}
