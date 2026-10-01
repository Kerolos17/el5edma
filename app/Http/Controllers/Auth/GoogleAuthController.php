<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * تسجيل الدخول بجوجل.
 *
 * نجاح المصادقة مع جوجل يثبت الهوية فقط: الحساب غير الموجود يمر بمسار
 * التسجيل والمراجعة نفسه، والحساب المرفوض أو الموقوف لا يحصل على جلسة.
 * لا يُخزَّن أي سر من جوجل في المتصفح أو المستودع — بيانات العميل في .env فقط.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('filament.admin.auth.login')
                ->with('error', __('auth.google_failed'));
        }

        $email = strtolower((string) $googleUser->getEmail());

        if ($email === '') {
            return redirect()->route('filament.admin.auth.login')
                ->with('error', __('auth.google_failed'));
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            // Active members log in normally. Open-request applicants get a
            // session confined to the waiting page. Everyone else (rejected /
            // suspended / legacy inactive) never receives a session.
            if ($user->is_active || $user->hasPendingJoinRequest()) {
                Auth::login($user);
                $user->update(['last_login_at' => now()]);

                App::setLocale($user->locale ?? 'ar');

                return redirect()->intended(route($user->homeRoute()));
            }

            return redirect()->route('filament.admin.auth.login')
                ->with('error', __('auth.account_rejected_or_suspended'));
        }

        // New Google visitor: route them through the SAME review path —
        // an encrypted, time-limited payload carries the verified identity
        // to the completion form (group + role + password + consent).
        $token = Crypt::encrypt([
            'email'   => $email,
            'name'    => (string) $googleUser->getName(),
            'exp'     => now()->addMinutes(30)->getTimestamp(),
            'purpose' => 'google-registration',
        ]);

        return redirect()->route('registration.google.form', ['token' => $token]);
    }

    public function showCompletionForm(string $token)
    {
        $payload = $this->decodeGooglePayload($token);

        if (! $payload) {
            return redirect()->route('filament.admin.auth.login')
                ->with('error', __('auth.google_session_expired'));
        }

        return view('registration.google-complete', [
            'token'         => $token,
            'googleName'    => $payload['name'],
            'googleEmail'   => $payload['email'],
            'serviceGroups' => ServiceGroup::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function completeRegistration(Request $request, string $token): RedirectResponse
    {
        $payload = $this->decodeGooglePayload($token);

        if (! $payload) {
            return redirect()->route('filament.admin.auth.login')
                ->with('error', __('auth.google_session_expired'));
        }

        if (User::query()->where('email', $payload['email'])->exists()) {
            return redirect()->route('filament.admin.auth.login')
                ->with('error', __('registration.errors.email_exists'));
        }

        try {
            $validated = $request->validate([
                'phone'            => ['required', 'string', 'max:20', 'unique:users,phone'],
                'password'         => ['required', 'string', 'min:8'],
                'service_group_id' => ['required', 'exists:service_groups,id'],
                'desired_role'     => ['required', 'in:servant,family_leader,service_leader'],
                'privacy_consent'  => ['accepted'],
            ], [
                'phone.required'            => __('registration.errors.phone_required'),
                'phone.unique'              => __('registration.errors.phone_exists'),
                'password.required'         => __('registration.errors.password_required'),
                'password.min'              => __('registration.errors.password_min'),
                'service_group_id.required' => __('registration.errors.service_group_required'),
                'desired_role.required'     => __('registration.errors.desired_role_required'),
                'desired_role.in'           => __('registration.errors.desired_role_invalid'),
                'privacy_consent.accepted'  => __('registration.errors.privacy_consent_required'),
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput($request->except('password'));
        }

        $serviceGroup = ServiceGroup::query()->find($validated['service_group_id']);

        if (! $serviceGroup || ! $serviceGroup->is_active) {
            return back()->withInput()->with('error', __('registration.errors.service_group_inactive'));
        }

        $user = app(RegistrationService::class)->register([
            'name'         => $payload['name'],
            'email'        => $payload['email'],
            'phone'        => $validated['phone'],
            'password'     => $validated['password'],
            'desired_role' => $validated['desired_role'],
        ], $serviceGroup, $request->ip());

        // The request is now pending — the applicant may hold a session, but
        // that session is confined to the waiting page by middleware.
        Auth::login($user);
        App::setLocale($user->locale ?? 'ar');

        return redirect()->route('registration.status')
            ->with('success', __('registration.pending_approval_message'));
    }

    private function decodeGooglePayload(string $token): ?array
    {
        try {
            $payload = Crypt::decrypt($token);
        } catch (Throwable) {
            return null;
        }

        if (($payload['purpose'] ?? null) !== 'google-registration') {
            return null;
        }

        if (($payload['exp'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        if (empty($payload['email'])) {
            return null;
        }

        return $payload;
    }
}
