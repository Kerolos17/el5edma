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

        $servant = User::query()
            ->whereKey($data['assigned_servant_id'] ?? $this->record->assigned_servant_id)
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
