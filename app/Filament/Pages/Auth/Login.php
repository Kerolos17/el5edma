<?php

namespace App\Filament\Pages\Auth;

use App\Services\CodeLoginService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    // WithRateLimiting موروث من BaseLogin — لا نعيد تعريفه

    // الـ Tab النشط: email أو code
    public string $activeTab = 'email';

    // كود الخادم — بديل كامل لتسجيل الدخول بالبريد: الكود وحده كافٍ
    public string $personalCode = '';

    public function getView(): string
    {
        return 'filament.pages.auth.login';
    }

    // تسجيل الدخول بالكود الشخصي
    public function loginWithCode(): ?LoginResponse
    {
        // حماية من brute force — 5 محاولات كل دقيقة (نفس حد email login)
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            throw ValidationException::withMessages([
                'personalCode' => [
                    __('auth.throttle', ['seconds' => $exception->secondsUntilAvailable]),
                ],
            ]);
        }

        $user = app(CodeLoginService::class)->attempt($this->personalCode);

        if (! $user) {
            throw ValidationException::withMessages([
                'personalCode' => [__('auth.invalid_code')],
            ]);
        }

        Auth::login($user); // No "remember me" — sessions expire on browser close for security
        $user->update(['last_login_at' => now()]);
        App::setLocale($user->locale ?? 'ar');
        session(['locale' => $user->locale ?? 'ar']);

        $this->personalCode = '';

        return app(LoginResponse::class);
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab    = $tab;
        $this->personalCode = '';
    }

    /**
     * إضافة رابط التسجيل أسفل نموذج تسجيل الدخول
     */
    protected function getFooterWidgetsData(): array
    {
        return [
            'registerUrl' => route('register.public'),
        ];
    }

    protected function hasFooter(): bool
    {
        return true;
    }
}
