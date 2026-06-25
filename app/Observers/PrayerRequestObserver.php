<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\PrayerRequest;
use Illuminate\Support\Facades\Auth;

class PrayerRequestObserver
{
    private array $excluded = ['updated_at'];

    public function created(PrayerRequest $prayer): void
    {
        $this->log($prayer, 'created', null, collect($prayer->getAttributes())->except($this->excluded)->toArray());
    }

    public function updated(PrayerRequest $prayer): void
    {
        $old = collect($prayer->getOriginal())->except($this->excluded)->toArray();
        $new = collect($prayer->getDirty())->except($this->excluded)->toArray();

        if (! empty($new)) {
            $this->log($prayer, 'updated', $old, $new);
        }
    }

    public function deleted(PrayerRequest $prayer): void
    {
        $this->log($prayer, 'deleted', collect($prayer->getOriginal())->except($this->excluded)->toArray(), null);
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
