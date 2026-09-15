<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\Auth;

class BeneficiaryObserver
{
    // الحقول المستبعدة من الـ audit — PII/PHI لا تُسجَّل أبداً
    private array $excluded = [
        'updated_at',
        'full_name', 'phone', 'whatsapp', 'guardian_name', 'guardian_phone',
        'address_text', 'area', 'governorate',
        'health_status', 'medical_notes', 'financial_notes',
        'doctor_name', 'hospital_name', 'photo',
    ];

    public function created(Beneficiary $beneficiary): void
    {
        $sanitized = collect($beneficiary->getAttributes())->except($this->excluded)->toArray();
        $this->log($beneficiary, 'created', null, $sanitized);

        $notifier  = app(InternalNotificationService::class);
        $adderName = Auth::check() ? Auth::user()->name : __('notifications.system');

        $notifier->notifyRelatedUsers(
            $beneficiary,
            'new_beneficiary',
            __('notifications.new_beneficiary_title'),
            __('notifications.new_beneficiary_body', [
                'name'  => $beneficiary->full_name,
                'adder' => $adderName,
            ]),
            [
                'beneficiary_id' => $beneficiary->id,
                'url'            => '/app/beneficiary/' . $beneficiary->id,
            ],
        );
    }

    public function updated(Beneficiary $beneficiary): void
    {
        $old = collect($beneficiary->getOriginal())->except($this->excluded)->toArray();
        $new = collect($beneficiary->getDirty())->except($this->excluded)->toArray();

        if (! empty($new)) {
            $this->log($beneficiary, 'updated', $old, $new);
        }
    }

    public function deleted(Beneficiary $beneficiary): void
    {
        $this->log($beneficiary, 'deleted', $beneficiary->getOriginal(), null);
    }

    private function log($model, string $action, ?array $old, ?array $new): void
    {
        if (! Auth::check()) {
            return;
        }

        AuditLog::create([
            'user_id'    => Auth::id(),
            'model_type' => get_class($model),
            'model_id'   => $model->id,
            'action'     => $action,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
        ]);
    }
}
