<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    // الـ Tab النشط: email أو code
    public string $activeTab = 'email';

    // حقول الـ Code Tab
    public string $personalCode = '';

    public function getView(): string
    {
        return 'filament.pages.auth.login';
    }

    // تسجيل الدخول بالكود الشخصي
    public function loginWithCode(): LoginResponse|null
    {
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
}
