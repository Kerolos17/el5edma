<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * استعادة كلمة المرور ذاتيًا: طلب رابط عبر البريد ثم تعيين كلمة جديدة.
 * الرسائل الموحدة لا تكشف وجود الحساب أو عدمه، والبريد يفشل بصمت مسجّل
 * بدل كسر الطلب. لا شيء من التوكنات يُسجَّل في السجلات.
 */
class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => __('auth.reset.email_required'),
            'email.email'    => __('auth.reset.email_format'),
        ]);

        $status = Password::sendResetLink($request->only('email'));

        // The broker returns the same generic status whether or not the
        // account exists — never confirm or deny registration.
        if ($status !== Password::RESET_LINK_SENT) {
            Log::warning('Password reset link not sent', ['status' => $status]);
        }

        return back()->with('status', __('auth.reset.link_sent'));
    }

    public function resetForm(string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => request()->query('email', '')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'email.required'     => __('auth.reset.email_required'),
            'email.email'        => __('auth.reset.email_format'),
            'password.required'  => __('auth.reset.password_required'),
            'password.confirmed' => __('auth.reset.password_confirmation'),
            'password.min'       => __('auth.reset.password_min'),
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Any other session (e.g. a stolen one) dies at once.
                DB::table('sessions')->where('user_id', $user->id)->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__('auth.reset.invalid_token')],
            ]);
        }

        // Log the user in and route them to their own dashboard.
        $user = User::query()->where('email', $request->email)->first();

        if ($user && ($user->is_active || $user->hasPendingJoinRequest())) {
            auth()->login($user, remember: false);
            $request->session()->regenerate();

            return redirect()->intended(route($user->homeRoute()))
                ->with('success', __('auth.reset.success'));
        }

        return redirect()->route('filament.admin.auth.login')
            ->with('success', __('auth.reset.success'));
    }
}
