<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Models\ScheduledVisit;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateVisit extends CreateRecord
{
    protected static string $resource = VisitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        // لو is_critical → تأكد من الـ flags
        if (! empty($data['is_critical'])) {
            $data['needs_family_leader']  = true;
            $data['needs_service_leader'] = true;
        }

        return $data;
    }

    // بعد الحفظ: أغلق الزيارة المجدولة المرتبطة لو موجودة
    protected function afterCreate(): void
    {
        $beneficiaryId = $this->record->beneficiary_id;
        $servantId     = $this->record->created_by;

        ScheduledVisit::where('beneficiary_id', $beneficiaryId)
            ->where('assigned_servant_id', $servantId)
            ->where('status', 'pending')
            ->whereDate('scheduled_date', today())
            ->update([
                'status'              => 'completed',
                'completed_visit_id'  => $this->record->id,
            ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
