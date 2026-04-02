<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Visit;
use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VisitObserver
{
    private array $excluded = ['updated_at'];

    public function created(Visit $visit): void
    {
        $this->log($visit, 'created', null, $visit->getAttributes());
        $this->invalidateDashboardCache($visit);
    }

    public function updated(Visit $visit): void
    {
        $old = collect($visit->getOriginal())->except($this->excluded)->toArray();
        $new = collect($visit->getDirty())->except($this->excluded)->toArray();

        if (! empty($new)) {
            $this->log($visit, 'updated', $old, $new);
            $this->invalidateDashboardCache($visit);
        }
    }

    public function deleted(Visit $visit): void
    {
        $this->log($visit, 'deleted', $visit->getOriginal(), null);
        $this->invalidateDashboardCache($visit);
    }

    private function invalidateDashboardCache(Visit $visit): void
    {
        $userId = $visit->created_by;

        if (! $userId) return;

        // Forget all period variants so the next request recomputes fresh stats
        foreach (['week', 'month', 'year'] as $period) {
            Cache::forget("dashboard:stats:{$userId}:{$period}");
        }
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
