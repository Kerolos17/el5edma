<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\ServiceGroup;
use App\Services\CacheService;
use Illuminate\Support\Facades\Auth;

class ServiceGroupObserver
{
    private array $excluded = ['updated_at'];

    public function created(ServiceGroup $group): void
    {
        $this->log($group, 'created', null, $group->getAttributes());
    }

    public function updated(ServiceGroup $group): void
    {
        $old = collect($group->getOriginal())->except($this->excluded)->toArray();
        $new = collect($group->getDirty())->except($this->excluded)->toArray();

        if (! empty($new)) {
            $this->log($group, 'updated', $old, $new);
        }

        CacheService::invalidateServiceGroupCaches();
    }

    public function deleted(ServiceGroup $group): void
    {
        $this->log($group, 'deleted', $group->getOriginal(), null);
        CacheService::invalidateServiceGroupCaches();
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
