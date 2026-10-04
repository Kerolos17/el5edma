<?php

namespace App\Services;

use App\Models\User;

/**
 * الدخول بالكود الشخصي — بديل كامل لتسجيل الدخول بالبريد: الكود وحده كافٍ.
 *
 * الكود يُتحقق منه عبر blind-index hash (بدون كلمة مرور) بقرار المالك
 * (2026-10-04). الحماية من التخمين: حد المعدل على مستوى IP في نقطة الدخول
 * (5/دقيقة + قفل تراكمي 10/15 دقيقة). حالة الحساب تُطبَّق هنا أيضًا:
 * الأعضاء النشطون وأصحاب طلبات الانضمام المفتوحة فقط يدخلون.
 * لا يُسجَّل الكود في أي سجل.
 */
class CodeLoginService
{
    public function attempt(string $code): ?User
    {
        $user = User::query()
            ->where('personal_code_hash', User::hashPersonalCode(trim($code)))
            ->first();

        if (! $user) {
            return null;
        }

        if (! ($user->is_active || $user->hasPendingJoinRequest())) {
            return null;
        }

        return $user;
    }
}
