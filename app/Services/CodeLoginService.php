<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * الدخول بالكود الشخصي + كلمة المرور (عاملان إلزاميان).
 *
 * الكود وحده لا يكفي: يثبت أن صاحب الكود هو من يملكه، وكلمة المرور تمنع
 * استغلال كود مسرّب. المحاولات الفاشلة تُقيد على مستوى الحساب (إيقاف مؤقت
 * عند التخمين المتكرر) بجانب حد المعدل على مستوى IP. لا يُسجَّل الكود ولا
 * كلمة المرور في أي سجل.
 */
class CodeLoginService
{
    private const MAX_ACCOUNT_ATTEMPTS = 5;

    private const ACCOUNT_DECAY_SECONDS = 900;

    /**
     * Verify a code + password pair.
     *
     * @return array{user: ?User, locked_for: int} user null + locked_for=0
     *                                             means invalid credentials; locked_for>0 means the account is in a
     *                                             guessing lockout and may not be attempted right now.
     */
    public function attempt(string $code, string $password): array
    {
        $user = User::query()
            ->where('personal_code_hash', User::hashPersonalCode(trim($code)))
            ->first();

        if (! $user) {
            return ['user' => null, 'locked_for' => 0];
        }

        $accountKey = 'code-login:account|' . $user->id;

        if (RateLimiter::tooManyAttempts($accountKey, self::MAX_ACCOUNT_ATTEMPTS)) {
            return ['user' => null, 'locked_for' => RateLimiter::availableIn($accountKey)];
        }

        if ($password === '' || ! Hash::check($password, (string) $user->password)) {
            RateLimiter::hit($accountKey, self::ACCOUNT_DECAY_SECONDS);

            return ['user' => null, 'locked_for' => 0];
        }

        // The code + password are valid, but the account state decides the
        // rest: approved members in, open-request applicants onto the waiting
        // page (their session is confined there), everyone else out.
        if (! ($user->is_active || $user->hasPendingJoinRequest())) {
            return ['user' => null, 'locked_for' => 0];
        }

        RateLimiter::clear($accountKey);

        return ['user' => $user, 'locked_for' => 0];
    }
}
