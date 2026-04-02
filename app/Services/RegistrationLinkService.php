<?php
namespace App\Services;

use App\Models\ServiceGroup;
use Illuminate\Support\Str;

class RegistrationLinkService
{
    /**
     * توليد أو استرجاع رمز التسجيل لمجموعة خدمة
     * Requirements: 1.1, 1.5
     *
     * @param ServiceGroup $serviceGroup
     * @return string
     */
    public function getOrCreateToken(ServiceGroup $serviceGroup): string
    {
        // إذا كان هناك token موجود، نرجعه مباشرة (idempotent)
        if ($serviceGroup->hasActiveRegistrationToken()) {
            return $serviceGroup->registration_token;
        }

        // توليد token جديد
        $token = $this->generateToken();

        // حفظ الـ token في قاعدة البيانات
        $serviceGroup->update([
            'registration_token'              => $token,
            'registration_token_generated_at' => now(),
        ]);

        return $token;
    }

    /**
     * إعادة توليد رمز جديد وإبطال القديم
     * Requirements: 1.6
     *
     * @param ServiceGroup $serviceGroup
     * @return string
     */
    public function regenerateToken(ServiceGroup $serviceGroup): string
    {
        // توليد token جديد
        $token = $this->generateToken();

        // تحديث الـ token (إبطال القديم)
        $serviceGroup->update([
            'registration_token'              => $token,
            'registration_token_generated_at' => now(),
        ]);

        return $token;
    }

    /**
     * التحقق من صحة الرمز وإرجاع مجموعة الخدمة
     * Requirements: 2.4, 10.3
     *
     * @param string $token
     * @return ServiceGroup|null
     */
    public function validateToken(string $token): ?ServiceGroup
    {
        return ServiceGroup::where('registration_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /**
     * توليد رابط التسجيل الكامل
     * Requirements: 1.2, 1.3
     *
     * @param ServiceGroup $serviceGroup
     * @return string
     */
    public function generateRegistrationUrl(ServiceGroup $serviceGroup): string
    {
        $token = $this->getOrCreateToken($serviceGroup);

        return route('register.show', ['token' => $token]);
    }

    /**
     * توليد token آمن باستخدام Str::random(64)
     * Requirements: 10.1, 10.2
     *
     * @return string
     */
    protected function generateToken(): string
    {
        return Str::random(64);
    }
}
