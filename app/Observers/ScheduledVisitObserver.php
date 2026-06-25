<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\ScheduledVisit;
use Illuminate\Support\Facades\Auth;

class ScheduledVisitObserver
{
    private array $excluded = ['updated_at'];

    public function created(ScheduledVisit $scheduled): void
    {
        $this->log($scheduled, 'created', null, collect($scheduled->getAttributes())->except($this->excluded)->toArray());
    }

    public function updated(ScheduledVisit $scheduled): void
    {
        $old = collect($scheduled->getOriginal())->except($this->excluded)->toArray();
        $new = collect($scheduled->getDirty())->except($this->excluded)->toArray();

        if (! empty($new)) {
            $this->log($scheduled, 'updated', $old, $new);
        }
    }

    public function deleted(ScheduledVisit $scheduled): void
    {
        $this->log($scheduled, 'deleted', collect($scheduled->getOriginal())->except($this->excluded)->toArray(), null);
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
