<?php
namespace App\Http\Controllers;

use App\Services\RegistrationLinkService;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * معالج التسجيل الذاتي للخدام
 * Requirements: 2.1-2.6, 3.1-3.7, 4.1-4.7, 6.1-6.2
 */
class RegistrationController extends Controller
{
    public function __construct(
        protected RegistrationLinkService $registrationLinkService,
        protected RegistrationService $registrationService
    ) {}

    /**
     * عرض نموذج التسجيل
     * Route: GET /register/{token}
     * Requirements: 2.1-2.6
     *
     * @param  string  $token
     * @return View|RedirectResponse
     */
    public function show(string $token): View | RedirectResponse
    {
        // التحقق من صحة الرمز
        $serviceGroup = $this->registrationLinkService->validateToken($token);

        if (! $serviceGroup) {
            return redirect()
                ->route('filament.admin.auth.login')
                ->with('error', __('registration.errors.invalid_token'));
        }

        // عرض نموذج التسجيل
        return view('registration.form', [
            'token'        => $token,
            'serviceGroup' => $serviceGroup,
        ]);
    }

    /**
     * معالجة طلب التسجيل
     * Route: POST /register/{token}
     * Requirements: 3.1-3.7, 4.1-4.7, 6.1-6.2
     * Middleware: throttle:5,60 (rate limiting)
     *
     * @param  Request  $request
     * @param  string   $token
     * @return RedirectResponse
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        // التحقق من صحة الرمز
        $serviceGroup = $this->registrationLinkService->validateToken($token);

        if (! $serviceGroup) {
            return redirect()
                ->route('filament.admin.auth.login')
                ->with('error', __('registration.errors.invalid_token'));
        }

        // التحقق من صحة البيانات المدخلة
        try {
            $validated = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'unique:users,email'],
                'phone'    => ['required', 'string', 'max:20', 'unique:users,phone'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'name.required'      => __('registration.errors.name_required'),
                'email.required'     => __('registration.errors.email_required'),
                'email.email'        => __('registration.errors.email_format'),
                'email.unique'       => __('registration.errors.email_exists'),
                'phone.required'     => __('registration.errors.phone_required'),
                'phone.unique'       => __('registration.errors.phone_exists'),
                'password.required'  => __('registration.errors.password_required'),
                'password.min'       => __('registration.errors.password_min'),
                'password.confirmed' => __('registration.errors.password_confirmation'),
            ]);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->except('password', 'password_confirmation'));
        }

        // معالجة التسجيل
        try {
            $ipAddress = $request->ip();

            // إضافة الرمز للبيانات (للتسجيل في audit log)
            $validated['token'] = $token;

            $user = $this->registrationService->register(
                $validated,
                $serviceGroup,
                $ipAddress
            );

            Log::info('Self-registration successful', [
                'user_id'          => $user->id,
                'email'            => $user->email,
                'service_group_id' => $serviceGroup->id,
            ]);

            return redirect()
                ->route('filament.admin.auth.login')
                ->with('success', __('registration.success'));

        } catch (\Exception $e) {
            Log::error('Self-registration failed', [
                'email'            => $validated['email'],
                'service_group_id' => $serviceGroup->id,
                'error'            => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', __('registration.errors.system_error'));
        }
    }

    /**
     * عرض نموذج التسجيل العام (بدون token)
     * Route: GET /register
     *
     * @return View
     */
    public function showPublic(): View
    {
        // جلب جميع مجموعات الخدمة النشطة
        $serviceGroups = \App\Models\ServiceGroup::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('registration.public-form', [
            'serviceGroups' => $serviceGroups,
        ]);
    }

    /**
     * معالجة طلب التسجيل العام (بدون token)
     * Route: POST /register
     * Middleware: throttle:5,60 (rate limiting)
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function storePublic(Request $request): RedirectResponse
    {
        // التحقق من صحة البيانات المدخلة
        try {
            $validated = $request->validate([
                'name'             => ['required', 'string', 'max:255'],
                'email'            => ['required', 'email', 'unique:users,email'],
                'phone'            => ['required', 'string', 'max:20', 'unique:users,phone'],
                'password'         => ['required', 'string', 'min:8', 'confirmed'],
                'service_group_id' => ['required', 'exists:service_groups,id'],
            ], [
                'name.required'             => __('registration.errors.name_required'),
                'email.required'            => __('registration.errors.email_required'),
                'email.email'               => __('registration.errors.email_format'),
                'email.unique'              => __('registration.errors.email_exists'),
                'phone.required'            => __('registration.errors.phone_required'),
                'phone.unique'              => __('registration.errors.phone_exists'),
                'password.required'         => __('registration.errors.password_required'),
                'password.min'              => __('registration.errors.password_min'),
                'password.confirmed'        => __('registration.errors.password_confirmation'),
                'service_group_id.required' => __('registration.errors.service_group_required'),
                'service_group_id.exists'   => __('registration.errors.service_group_invalid'),
            ]);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->except('password', 'password_confirmation'));
        }

        // جلب مجموعة الخدمة
        $serviceGroup = \App\Models\ServiceGroup::find($validated['service_group_id']);

        if (! $serviceGroup || ! $serviceGroup->is_active) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', __('registration.errors.service_group_inactive'));
        }

        // معالجة التسجيل
        try {
            $ipAddress = $request->ip();

            $user = $this->registrationService->register(
                $validated,
                $serviceGroup,
                $ipAddress
            );

            Log::info('Public self-registration successful', [
                'user_id'          => $user->id,
                'email'            => $user->email,
                'service_group_id' => $serviceGroup->id,
            ]);

            return redirect()
                ->route('filament.admin.auth.login')
                ->with('success', __('registration.success'));

        } catch (\Exception $e) {
            Log::error('Public self-registration failed', [
                'email'            => $validated['email'],
                'service_group_id' => $serviceGroup->id,
                'error'            => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', __('registration.errors.system_error'));
        }
    }
}
