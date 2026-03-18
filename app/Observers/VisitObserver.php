<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;

class VisitObserver
{
    private array $excluded = ['updated_at'];

    public function created(Visit $visit): void
    {
        $this->log($visit, 'created', null, $visit->getAttributes());
    }

    public function updated(Visit $visit): void
    {
        $old = collect($visit->getOriginal())->except($this->excluded)->toArray();
        $new = collect($visit->getDirty())->except($this->excluded)->toArray();

        if (! empty($new)) {
            $this->log($visit, 'updated', $old, $new);
        }
    }

    public function deleted(Visit $visit): void
    {
        $this->log($visit, 'deleted', $visit->getOriginal(), null);
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
