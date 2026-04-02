<?php
namespace App\Filament\Pages\Auth;

use App\Models\User;
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

    // حقول الـ Code Tab
    public string $personalCode = '';

    public function getView(): string
    {
        return 'filament.pages.auth.login';
    }

    // تسجيل الدخول بالكود الشخصي
    public function loginWithCode(): ?LoginResponse
    {
        // حماية من brute force — 5 محاولات كل دقيقة (نفس حد email login)
        // WithRateLimiting موروث من BaseLogin
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            throw ValidationException::withMessages([
                'personalCode' => [
                    __('auth.throttle', ['seconds' => $exception->secondsUntilAvailable]),
                ],
            ]);
        }

        $code = trim($this->personalCode);

        if (empty($code)) {
            throw ValidationException::withMessages([
                'personalCode' => [__('auth.invalid_code')],
            ]);
        }

        $codeHash = hash('sha256', $code);

        $user = User::where('personal_code_hash', $codeHash)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'personalCode' => [__('auth.invalid_code')],
            ]);
        }

        Auth::login($user); // No "remember me" — sessions expire on browser close for security
        $user->update(['last_login_at' => now()]);
        App::setLocale($user->locale ?? 'ar');
        session(['locale' => $user->locale ?? 'ar']);

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
