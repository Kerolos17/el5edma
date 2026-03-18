<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Auth;

class BeneficiaryObserver
{
    // الحقول المستبعدة من الـ audit
    private array $excluded = ['updated_at'];

    public function created(Beneficiary $beneficiary): void
    {
        $this->log($beneficiary, 'created', null, $beneficiary->getAttributes());
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
        if (! Auth::check()) return;

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
