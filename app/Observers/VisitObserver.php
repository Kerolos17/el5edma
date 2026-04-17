<?php

namespace App\Observers;

use App\Jobs\SendFcmNotificationJob;
use App\Models\AuditLog;
use App\Models\User;
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

        if ($visit->is_critical) {
            $this->sendCriticalCaseNotification($visit);
        }
    }

    public function updated(Visit $visit): void
    {
        $old = collect($visit->getOriginal())->except($this->excluded)->toArray();
        $new = collect($visit->getDirty())->except($this->excluded)->toArray();

        if (! empty($new)) {
            $this->log($visit, 'updated', $old, $new);
            $this->invalidateDashboardCache($visit);
        }

        if ($visit->isDirty('is_critical') && $visit->is_critical) {
            $this->sendCriticalCaseNotification($visit);
        }
    }

    public function deleted(Visit $visit): void
    {
        $this->log($visit, 'deleted', $visit->getOriginal(), null);
        $this->invalidateDashboardCache($visit);
    }

    private function sendCriticalCaseNotification(Visit $visit): void
    {
        $visit->loadMissing('beneficiary.serviceGroup');
        $beneficiary = $visit->beneficiary;

        if (! $beneficiary) {
            return;
        }

        $notifier = app(InternalNotificationService::class);
        $title    = __('notifications.critical_case_title');
        $body     = __('notifications.critical_case_body', ['name' => $beneficiary->full_name]);
        $data     = [
            'beneficiary_id' => $beneficiary->id,
            'visit_id'       => $visit->id,
            'url'            => route('filament.admin.resources.visits.view', ['record' => $visit->id]),
        ];

        $notifier->notifyRelatedUsers($beneficiary, 'critical_case', $title, $body, $data);

        // Send FCM push to related users
        $userIds = collect();

        if ($beneficiary->assigned_servant_id) {
            $userIds->push($beneficiary->assigned_servant_id);
        }
        if ($beneficiary->serviceGroup?->leader_id) {
            $userIds->push($beneficiary->serviceGroup->leader_id);
        }

        $tokens = User::whereIn('id', $userIds->unique())
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();

        if (! empty($tokens)) {
            SendFcmNotificationJob::dispatch($tokens, $title, $body, $data);
        }
    }

    private function invalidateDashboardCache(Visit $visit): void
    {
        $userId = $visit->created_by;

        if (! $userId) {
            return;
        }

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
