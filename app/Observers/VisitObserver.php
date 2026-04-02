<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Visit;
use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\Auth;

class VisitObserver
{
    private array $excluded = ['updated_at'];

    public function created(Visit $visit): void
    {
        $this->log($visit, 'created', null, $visit->getAttributes());

        // تحميل علاقة المخدوم إذا تطلب الأمر لمعرفة اسمه
        $visit->loadMissing('beneficiary');
        $beneficiaryName = $visit->beneficiary ? $visit->beneficiary->name : 'مخدوم';

        // إرسال إشعار داخلي
        $notifier = app(InternalNotificationService::class);
        $adderName = Auth::check() ? Auth::user()->name : 'النظام';
        
        if ($visit->beneficiary) {
            $notifier->notifyRelatedUsers(
                $visit->beneficiary,
                'visit_reminder',
                'إضافة زيارة جديدة',
                "تم ترتيب زيارة للمخدوم {$beneficiaryName} بواسطة {$adderName}",
                ['visit_id' => $visit->id]
            );
        }

        // إرسال إشعار إضافي مخصص للحالات الحرجة
        if ($visit->is_critical && $visit->beneficiary) {
            $notifier->notifyRelatedUsers(
                $visit->beneficiary,
                'critical_case',
                'حالة حرجة تتطلب التدخل!',
                "تم الإبلاغ أن المخدوم {$beneficiaryName} في حالة حرجة بواسطة {$adderName} أثناء الزيارة.",
                ['visit_id' => $visit->id]
            );
        }
    }

    public function updated(Visit $visit): void
    {
        $old = collect($visit->getOriginal())->except($this->excluded)->toArray();
        $new = collect($visit->getDirty())->except($this->excluded)->toArray();

        if (! empty($new)) {
            $this->log($visit, 'updated', $old, $new);
            
            // تحقق ما إذا تم تغيير حالة الزيارة لتصبح "حرجة" في هذا التحديث
            if (isset($new['is_critical']) && $new['is_critical'] == true && 
                (!isset($old['is_critical']) || $old['is_critical'] == false)) {
                
                $visit->loadMissing('beneficiary');
                $beneficiaryName = $visit->beneficiary ? $visit->beneficiary->name : 'مخدوم';
                $adderName = Auth::check() ? Auth::user()->name : 'النظام';
                
                $notifier = app(InternalNotificationService::class);
                if ($visit->beneficiary) {
                    $notifier->notifyRelatedUsers(
                        $visit->beneficiary,
                        'critical_case',
                        'تحديث: حالة حرجة تتطلب التدخل!',
                        "تم تحديث بيانات المخدوم {$beneficiaryName} وتم الإبلاغ بأنه في حالة حرجة بواسطة {$adderName}.",
                        ['visit_id' => $visit->id]
                    );
                }
            }
        }
    }

    public function deleted(Visit $visit): void
    {
        $this->log($visit, 'deleted', $visit->getOriginal(), null);
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
