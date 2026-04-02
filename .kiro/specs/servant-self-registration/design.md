# Design Document: Servant Self-Registration

## Overview

نظام التسجيل الذاتي للخدام يسمح للخدام الجدد بالتسجيل في النظام بشكل مستقل من خلال رابط عام فريد لكل مجموعة خدمة. يهدف هذا النظام إلى تبسيط عملية إضافة الخدام الجدد وتقليل العبء الإداري على قادة الأسر وأمناء الخدمة.

### Key Features

- توليد روابط تسجيل فريدة لكل مجموعة خدمة
- نموذج تسجيل عام لا يتطلب مصادقة مسبقة
- إنشاء حسابات تلقائياً بصلاحيات "servant"
- إشعارات فورية لقادة المجموعات عند التسجيل الجديد
- حماية ضد التسجيل المكرر والإساءة
- تسجيل كامل لعمليات التسجيل (audit trail)

### Design Goals

- **Simplicity**: تجربة مستخدم بسيطة للخدام الجدد
- **Security**: حماية ضد الإساءة والتسجيل غير المصرح به
- **Traceability**: تتبع كامل لجميع عمليات التسجيل
- **Integration**: تكامل سلس مع نظام الصلاحيات والإشعارات الحالي

## Architecture

### High-Level Architecture

```
┌─────────────────┐
│  Public User    │
│  (Unauth)       │
└────────┬────────┘
         │
         │ 1. Access Registration Link
         │    /register/{token}
         ▼
┌─────────────────────────────────────┐
│  Registration Controller            │
│  - Validate token                   │
│  - Display form                     │
│  - Process submission               │
└────────┬────────────────────────────┘
         │
         │ 2. Submit Form
         ▼
┌─────────────────────────────────────┐
│  Registration Service               │
│  - Validate data                    │
│  - Check duplicates                 │
│  - Create user account              │
│  - Send notifications               │
│  - Log audit trail                  │
└────────┬────────────────────────────┘
         │
         ├─────────────────────────────┐
         │                             │
         ▼                             ▼
┌──────────────────┐         ┌──────────────────┐
│  User Model      │         │  Notification    │
│  (servant role)  │         │  Service         │
└──────────────────┘         └──────────────────┘
```

### Component Layers

1. **Presentation Layer**
   - Public registration form (Blade view)
   - Filament admin panel for link management
   - Registration link widget/action in ServiceGroup resource

2. **Application Layer**
   - `RegistrationController`: Handles public registration flow
   - `RegistrationService`: Business logic for registration process
   - `RegistrationLinkService`: Token generation and validation

3. **Domain Layer**
   - `User` model: Extended with registration-related methods
   - `ServiceGroup` model: Extended with registration token field
   - `AuditLog` model: Records registration events

4. **Infrastructure Layer**
   - Rate limiting middleware
   - CSRF protection
   - Database transactions
   - Push notification integration

## Components and Interfaces

### 1. Database Schema Changes

#### service_groups Table Extension

```php
// New migration: add_registration_token_to_service_groups_table.php
Schema::table('service_groups', function (Blueprint $table) {
    $table->string('registration_token', 64)->unique()->nullable()->after('is_active');
    $table->timestamp('registration_token_generated_at')->nullable()->after('registration_token');
    $table->index('registration_token'); // للبحث السريع
});
```

**Fields:**
- `registration_token`: رمز فريد 64 حرف (cryptographically secure)
- `registration_token_generated_at`: تاريخ توليد الرمز (للتتبع)

#### audit_logs Table Extension

```php
// Extend existing action enum to include new action type
// في migration جديد: add_servant_self_registered_action_to_audit_logs.php
DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('created', 'updated', 'deleted', 'servant_self_registered')");
```

**New Action Type:**
- `servant_self_registered`: يُستخدم لتسجيل عمليات التسجيل الذاتي

### 2. Services

#### RegistrationLinkService

```php
namespace App\Services;

class RegistrationLinkService
{
    /**
     * توليد أو استرجاع رمز التسجيل لمجموعة خدمة
     * Requirements: 1.1, 1.5
     */
    public function getOrCreateToken(ServiceGroup $serviceGroup): string;
    
    /**
     * إعادة توليد رمز جديد وإبطال القديم
     * Requirements: 1.6
     */
    public function regenerateToken(ServiceGroup $serviceGroup): string;
    
    /**
     * التحقق من صحة الرمز وإرجاع مجموعة الخدمة
     * Requirements: 2.4, 10.3
     */
    public function validateToken(string $token): ?ServiceGroup;
    
    /**
     * توليد رابط التسجيل الكامل
     * Requirements: 1.2, 1.3
     */
    public function generateRegistrationUrl(ServiceGroup $serviceGroup): string;
}
```

#### RegistrationService

```php
namespace App\Services;

class RegistrationService
{
    /**
     * معالجة طلب التسجيل وإنشاء الحساب
     * Requirements: 4.1-4.7, 3.1-3.7
     */
    public function register(array $data, ServiceGroup $serviceGroup, string $ipAddress): User;
    
    /**
     * التحقق من عدم وجود تسجيل مكرر
     * Requirements: 9.1, 9.2
     */
    public function checkDuplicates(string $email, string $phone): array;
    
    /**
     * إرسال إشعارات للقادة
     * Requirements: 5.1-5.5
     */
    public function notifyLeaders(User $newServant, ServiceGroup $serviceGroup): void;
    
    /**
     * تسجيل عملية التسجيل في audit log
     * Requirements: 8.1-8.5
     */
    public function logRegistration(User $user, ServiceGroup $serviceGroup, string $token, string $ipAddress): void;
}
```

### 3. Controllers

#### RegistrationController

```php
namespace App\Http\Controllers;

class RegistrationController extends Controller
{
    /**
     * عرض نموذج التسجيل
     * Route: GET /register/{token}
     * Requirements: 2.1-2.6
     */
    public function show(string $token): View|RedirectResponse;
    
    /**
     * معالجة طلب التسجيل
     * Route: POST /register/{token}
     * Requirements: 3.1-3.7, 4.1-4.7
     * Middleware: throttle:5,60 (rate limiting)
     */
    public function store(Request $request, string $token): RedirectResponse;
}
```

### 4. Filament Integration

#### ServiceGroup Resource Extension

```php
// في app/Filament/Resources/ServiceGroups/ServiceGroupResource.php

/**
 * إضافة action لتوليد/عرض رابط التسجيل
 * Requirements: 7.1-7.6
 */
public static function getActions(): array
{
    return [
        Action::make('registration_link')
            ->label(__('service_groups.registration_link'))
            ->icon('heroicon-o-link')
            ->visible(fn () => Auth::user()->can('manageRegistrationLink', ServiceGroup::class))
            ->modalContent(fn (ServiceGroup $record) => view('filament.modals.registration-link', [
                'url' => app(RegistrationLinkService::class)->generateRegistrationUrl($record),
                'registeredCount' => $record->servants()->count(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('common.close')),
            
        Action::make('regenerate_token')
            ->label(__('service_groups.regenerate_token'))
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->visible(fn () => Auth::user()->can('manageRegistrationLink', ServiceGroup::class))
            ->action(function (ServiceGroup $record) {
                app(RegistrationLinkService::class)->regenerateToken($record);
                Notification::make()
                    ->success()
                    ->title(__('service_groups.token_regenerated'))
                    ->send();
            }),
    ];
}
```

### 5. Views

#### Registration Form View

```blade
{{-- resources/views/registration/form.blade.php --}}
{{-- Requirements: 2.1-2.6 --}}

<div class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
        <h1 class="text-2xl font-bold text-center mb-6">
            {{ __('registration.title') }}
        </h1>
        
        <div class="mb-6 p-4 bg-blue-50 rounded">
            <p class="text-sm text-gray-700">
                {{ __('registration.service_group') }}: 
                <strong>{{ $serviceGroup->name }}</strong>
            </p>
        </div>
        
        <form method="POST" action="{{ route('register.store', $token) }}">
            @csrf
            
            {{-- Name field --}}
            {{-- Email field --}}
            {{-- Phone field --}}
            {{-- Password field --}}
            {{-- Password confirmation field --}}
            
            <button type="submit" class="w-full btn-primary">
                {{ __('registration.submit') }}
            </button>
        </form>
        
        <div class="mt-4 text-center">
            <a href="{{ route('filament.admin.auth.login') }}" class="text-sm text-blue-600">
                {{ __('registration.already_have_account') }}
            </a>
        </div>
    </div>
</div>
```

#### Registration Link Modal

```blade
{{-- resources/views/filament/modals/registration-link.blade.php --}}
{{-- Requirements: 7.1-7.5 --}}

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium mb-2">
            {{ __('service_groups.registration_url') }}
        </label>
        <div class="flex gap-2">
            <input 
                type="text" 
                value="{{ $url }}" 
                readonly 
                class="flex-1 px-3 py-2 border rounded"
                id="registration-url"
            />
            <button 
                type="button"
                onclick="copyToClipboard()"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
                {{ __('common.copy') }}
            </button>
        </div>
    </div>
    
    <div class="p-4 bg-gray-50 rounded">
        <p class="text-sm text-gray-600">
            {{ __('service_groups.registered_servants_count') }}: 
            <strong>{{ $registeredCount }}</strong>
        </p>
    </div>
    
    <script>
        function copyToClipboard() {
            const input = document.getElementById('registration-url');
            input.select();
            document.execCommand('copy');
            // Show success notification
        }
    </script>
</div>
```

### 6. Routes

```php
// في routes/web.php

// Public registration routes (no auth required)
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/register/{token}', [RegistrationController::class, 'show'])
        ->name('register.show');
    
    Route::post('/register/{token}', [RegistrationController::class, 'store'])
        ->name('register.store')
        ->middleware('throttle:5,60'); // 5 attempts per hour per IP
});
```

### 7. Policies

```php
// في app/Policies/ServiceGroupPolicy.php

/**
 * تحديد من يمكنه إدارة روابط التسجيل
 * Requirements: 7.6
 */
public function manageRegistrationLink(User $user, ServiceGroup $serviceGroup): bool
{
    // Super admin and service leader can manage all groups
    if ($user->isAdmin() || $user->isServiceLeader()) {
        return true;
    }
    
    // Family leader can only manage their own group
    if ($user->isFamilyLeader()) {
        return $user->service_group_id === $serviceGroup->id;
    }
    
    return false;
}
```

## Data Models

### ServiceGroup Model Extension

```php
// في app/Models/ServiceGroup.php

protected $fillable = [
    'name', 'leader_id', 'service_leader_id',
    'description', 'is_active',
    'registration_token', 'registration_token_generated_at', // جديد
];

protected function casts(): array
{
    return [
        'is_active' => 'boolean',
        'registration_token_generated_at' => 'datetime', // جديد
    ];
}

/**
 * التحقق من وجود رمز تسجيل نشط
 */
public function hasActiveRegistrationToken(): bool
{
    return !empty($this->registration_token);
}

/**
 * الحصول على عدد الخدام المسجلين ذاتياً
 * (يمكن تتبعهم من خلال audit logs)
 */
public function getSelfRegisteredServantsCount(): int
{
    return AuditLog::where('model_type', User::class)
        ->where('action', 'servant_self_registered')
        ->whereJsonContains('new_values->service_group_id', $this->id)
        ->count();
}
```

### User Model Extension

```php
// في app/Models/User.php

/**
 * توليد personal_code فريد للخادم الجديد
 * Requirements: 4.7
 */
public static function generateUniquePersonalCode(): string
{
    do {
        $code = str_pad((string) random_int(1000, 999999), 4, '0', STR_PAD_LEFT);
        $hash = hash('sha256', $code);
        $exists = self::where('personal_code_hash', $hash)->exists();
    } while ($exists);
    
    return $code;
}

/**
 * إنشاء خادم جديد من خلال التسجيل الذاتي
 * Requirements: 4.1-4.7
 */
public static function createFromSelfRegistration(array $data, ServiceGroup $serviceGroup): self
{
    return self::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'password' => $data['password'], // will be hashed automatically
        'personal_code' => self::generateUniquePersonalCode(),
        'role' => 'servant',
        'service_group_id' => $serviceGroup->id,
        'locale' => 'ar',
        'is_active' => true,
    ]);
}
```

### AuditLog Model Extension

```php
// في app/Models/AuditLog.php

/**
 * تسجيل عملية تسجيل ذاتي
 * Requirements: 8.1-8.5
 */
public static function logSelfRegistration(
    User $user,
    ServiceGroup $serviceGroup,
    string $token,
    string $ipAddress
): self
{
    return self::create([
        'user_id' => $user->id,
        'model_type' => User::class,
        'model_id' => $user->id,
        'action' => 'servant_self_registered',
        'old_values' => null,
        'new_values' => [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'service_group_id' => $serviceGroup->id,
            'service_group_name' => $serviceGroup->name,
            'registration_token' => substr($token, 0, 8) . '...', // partial token for security
        ],
        'ip_address' => $ipAddress,
    ]);
}
```

## Data Flow

### Registration Flow Sequence

```
1. Service Leader generates registration link
   ↓
2. Link shared with potential servant
   ↓
3. Servant accesses link → Token validation
   ↓
4. Registration form displayed with service group name
   ↓
5. Servant fills form and submits
   ↓
6. Server-side validation (email, phone, password)
   ↓
7. Duplicate check (email, phone)
   ↓
8. Create User account (role: servant, is_active: true)
   ↓
9. Link user to service group
   ↓
10. Generate personal_code
   ↓
11. Create audit log entry
   ↓
12. Send notifications to leaders (in-app + FCM)
   ↓
13. Redirect to login page with success message
   ↓
14. Servant logs in and accesses dashboard
```

### Notification Flow

```
New Servant Registered
   ↓
Identify Leaders:
   - Service Group leader (leader_id)
   - Service Leader (service_leader_id)
   ↓
Create ministry_notifications records (bulk insert)
   ↓
Dispatch SendFcmNotificationJob
   ↓
PushNotificationService sends FCM to leaders with tokens
   ↓
Leaders receive in-app + push notifications
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, I identified the following redundancies:
- Properties 4.2, 4.3, 4.4, 4.6 can be combined with 4.1 into a comprehensive "account creation" property
- Property 7.4 is duplicate of 1.6 (token invalidation)
- Property 9.1 is duplicate of 3.3 (email uniqueness)
- Property 9.2 is duplicate of 3.5 (phone uniqueness)
- Properties 8.2, 8.3, 8.4, 8.5 can be combined with 8.1 into a comprehensive "audit logging" property
- Properties 5.1, 5.2, 5.5 can be combined into a comprehensive "notification creation" property
- Property 10.2 can be combined with 10.1 (token generation security)

### Property 1: Token Generation Uniqueness and Security

*For any* service group, when a registration token is generated, it should be unique across all service groups, cryptographically secure (using random_bytes), and at least 32 characters long.

**Validates: Requirements 1.1, 10.1, 10.2**

### Property 2: Registration URL Contains Token

*For any* registration token, the generated registration URL should contain that token and be a valid URL format.

**Validates: Requirements 1.2**

### Property 3: Token Reuse Idempotence

*For any* service group with an existing registration token, calling getOrCreateToken multiple times should return the same token without generating a new one.

**Validates: Requirements 1.5**

### Property 4: Token Regeneration Invalidates Previous

*For any* service group with an existing token, regenerating the token should produce a different token, and attempting to use the old token for registration should fail validation.

**Validates: Requirements 1.6, 7.4**

### Property 5: Required Fields Validation

*For any* registration submission with one or more missing required fields (name, email, phone, password), the system should reject the submission and return validation errors.

**Validates: Requirements 3.1**

### Property 6: Email Format Validation

*For any* registration submission with an invalid email format, the system should reject the submission and return an email format error.

**Validates: Requirements 3.2**

### Property 7: Email Uniqueness Validation

*For any* email address that already exists in the users table, attempting to register with that email should fail validation and return a duplicate email error.

**Validates: Requirements 3.3, 9.1**

### Property 8: Phone Format Validation

*For any* registration submission with an invalid phone number format, the system should reject the submission and return a phone format error.

**Validates: Requirements 3.4**

### Property 9: Phone Uniqueness Validation

*For any* phone number that already exists in the users table, attempting to register with that phone should fail validation and return a duplicate phone error.

**Validates: Requirements 3.5, 9.2**

### Property 10: Password Minimum Length Validation

*For any* registration submission with a password shorter than 8 characters, the system should reject the submission and return a password length error.

**Validates: Requirements 3.6**

### Property 11: Validation Error Specificity

*For any* registration submission that fails validation, the error response should include field-specific error messages indicating which fields failed and why.

**Validates: Requirements 3.7, 9.3**

### Property 12: Complete Account Creation

*For any* valid registration data and valid service group token, the system should create a new user account with: role = "servant", is_active = true, locale = "ar", service_group_id matching the token's service group, and a hashed password (not plain text).

**Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6**

### Property 13: Personal Code Uniqueness

*For any* newly registered servant, the generated personal_code should be unique across all users in the system.

**Validates: Requirements 4.7**

### Property 14: Leader Notification Creation

*For any* successful self-registration, the system should create ministry_notifications records for all leaders of the service group (leader_id and service_leader_id if present), containing the servant name and registration timestamp.

**Validates: Requirements 5.1, 5.2, 5.3, 5.5**

### Property 15: FCM Push Notification Dispatch

*For any* successful self-registration where service group leaders have FCM tokens registered, the system should dispatch push notifications to those leaders.

**Validates: Requirements 5.4**

### Property 16: Authentication Round Trip

*For any* newly registered servant, they should be able to successfully authenticate using their registered email and password immediately after registration.

**Validates: Requirements 6.3**

### Property 17: Login Timestamp Recording

*For any* successful login, the system should update the user's last_login_at field with the current timestamp.

**Validates: Requirements 6.5**

### Property 18: Self-Registered Servants Count Accuracy

*For any* service group, the count of self-registered servants (from audit logs with action "servant_self_registered") should match the number of servants who registered through that group's token.

**Validates: Requirements 7.5**

### Property 19: Registration Link Access Authorization

*For any* user attempting to access registration link management, super_admin and service_leader should have access to all service groups, while family_leader should only have access to their own service group, and servant should have no access.

**Validates: Requirements 7.6**

### Property 20: Comprehensive Audit Log Creation

*For any* successful self-registration, the system should create an audit log entry with: action = "servant_self_registered", model_type = User::class, model_id = new user's id, new_values containing user details and partial token, and the request IP address.

**Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.5**

### Property 21: Token Validation on Registration

*For any* registration attempt with an invalid or non-existent token, the system should reject the attempt and return an error before processing any form data.

**Validates: Requirements 10.3**

### Property 22: Rate Limiting Enforcement

*For any* IP address, after 5 registration attempts within a 60-minute window, further registration attempts from that IP should be blocked with a rate limit error.

**Validates: Requirements 10.4, 10.5**

### Property 23: CSRF Protection

*For any* registration form submission without a valid CSRF token, the system should reject the request with a 419 status code.

**Validates: Requirements 10.6**

## Error Handling

### Validation Errors

```php
// في RegistrationController::store()

try {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ], [
        'email.unique' => __('registration.errors.email_exists'),
        'phone.unique' => __('registration.errors.phone_exists'),
        'password.min' => __('registration.errors.password_min'),
    ]);
} catch (ValidationException $e) {
    return back()
        ->withErrors($e->errors())
        ->withInput($request->except('password'));
}
```

### Token Validation Errors

```php
// في RegistrationController::show() و store()

$serviceGroup = $this->registrationLinkService->validateToken($token);

if (!$serviceGroup) {
    return redirect()
        ->route('filament.admin.auth.login')
        ->with('error', __('registration.errors.invalid_token'));
}
```

### Rate Limiting Errors

```php
// في routes/web.php middleware

Route::post('/register/{token}', [RegistrationController::class, 'store'])
    ->middleware('throttle:5,60'); // Laravel handles this automatically

// في Handler.php للتخصيص
protected function throttled(Request $request, Throwable $e)
{
    return response()->json([
        'message' => __('registration.errors.rate_limit_exceeded'),
        'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
    ], 429);
}
```

### Database Transaction Errors

```php
// في RegistrationService::register()

DB::beginTransaction();

try {
    $user = User::createFromSelfRegistration($data, $serviceGroup);
    
    $this->logRegistration($user, $serviceGroup, $token, $ipAddress);
    
    $this->notifyLeaders($user, $serviceGroup);
    
    DB::commit();
    
    return $user;
    
} catch (\Exception $e) {
    DB::rollBack();
    
    Log::error('Self-registration failed', [
        'email' => $data['email'],
        'service_group_id' => $serviceGroup->id,
        'error' => $e->getMessage(),
        'ip' => $ipAddress,
    ]);
    
    throw new RegistrationException(
        __('registration.errors.system_error'),
        previous: $e
    );
}
```

### Notification Errors

```php
// في RegistrationService::notifyLeaders()

try {
    // Create in-app notifications (critical - must succeed)
    $notifications = $this->createNotificationRecords($user, $serviceGroup);
    
    // Dispatch FCM job (non-critical - can fail silently)
    try {
        SendFcmNotificationJob::dispatch($leaders, $title, $body, $data);
    } catch (\Exception $e) {
        Log::warning('FCM notification dispatch failed for self-registration', [
            'user_id' => $user->id,
            'error' => $e->getMessage(),
        ]);
        // Don't throw - in-app notifications are sufficient
    }
    
} catch (\Exception $e) {
    Log::error('Failed to create notifications for self-registration', [
        'user_id' => $user->id,
        'error' => $e->getMessage(),
    ]);
    // Don't throw - registration itself succeeded
}
```

## Security Considerations

### 1. Token Security

- **Cryptographically Secure Generation**: Use `Str::random(64)` which uses `random_bytes()` internally
- **Minimum Length**: 64 characters (exceeds requirement of 32)
- **Uniqueness**: Database unique constraint on `service_groups.registration_token`
- **No Expiration**: Tokens don't expire but can be regenerated to invalidate old ones

```php
// في RegistrationLinkService::generateToken()

use Illuminate\Support\Str;

protected function generateToken(): string
{
    return Str::random(64); // Cryptographically secure
}
```

### 2. Rate Limiting

- **IP-Based Throttling**: 5 attempts per hour per IP address
- **Laravel Throttle Middleware**: Built-in protection against brute force
- **Granular Control**: Applied only to registration submission, not form display

```php
Route::post('/register/{token}', [RegistrationController::class, 'store'])
    ->middleware('throttle:5,60');
```

### 3. CSRF Protection

- **Laravel CSRF Middleware**: Automatic token validation on all POST requests
- **Token in Form**: `@csrf` directive in Blade template
- **Session-Based**: CSRF token tied to user session

```blade
<form method="POST" action="{{ route('register.store', $token) }}">
    @csrf
    {{-- form fields --}}
</form>
```

### 4. Password Security

- **Minimum Length**: 8 characters (enforced by validation)
- **Automatic Hashing**: Laravel's `password` cast handles bcrypt hashing
- **Confirmation Required**: `password_confirmation` field must match
- **Never Logged**: Password excluded from audit logs and error responses

```php
protected function casts(): array
{
    return [
        'password' => 'hashed', // Automatic bcrypt hashing
    ];
}
```

### 5. Input Validation

- **Server-Side Validation**: All validation done server-side (never trust client)
- **Email Format**: Laravel's `email` rule validates RFC compliance
- **Uniqueness Checks**: Database-level unique constraints + validation rules
- **SQL Injection Protection**: Eloquent ORM parameterizes all queries

### 6. Personal Code Security

- **Encrypted Storage**: Personal codes encrypted using Laravel's encryption
- **Hash for Search**: `personal_code_hash` allows searching without decryption
- **Unique Generation**: Loop until unique code found (collision-resistant)

```php
public function setPersonalCodeAttribute(string $value): void
{
    $this->attributes['personal_code'] = encrypt($value);
    $this->attributes['personal_code_hash'] = hash('sha256', $value);
}
```

### 7. Audit Trail

- **Immutable Logs**: `audit_logs` table has no `updated_at` column
- **IP Address Logging**: Request IP recorded for forensics
- **Partial Token Storage**: Only first 8 characters stored (security vs traceability)
- **Comprehensive Data**: All relevant registration details captured

### 8. Authorization

- **Policy-Based**: All access control through Laravel Policies
- **Role-Based**: Different access levels for different roles
- **Scope Enforcement**: Family leaders can only manage their own group

```php
public function manageRegistrationLink(User $user, ServiceGroup $serviceGroup): bool
{
    if ($user->isAdmin() || $user->isServiceLeader()) {
        return true;
    }
    
    if ($user->isFamilyLeader()) {
        return $user->service_group_id === $serviceGroup->id;
    }
    
    return false;
}
```

## Testing Strategy

### Dual Testing Approach

This feature requires both unit tests and property-based tests for comprehensive coverage:

- **Unit Tests**: Verify specific examples, edge cases, and integration points
- **Property Tests**: Verify universal properties across all inputs using randomized data

### Property-Based Testing Configuration

- **Library**: Use `pest-plugin-faker` or manual Faker integration with Pest PHP
- **Iterations**: Minimum 100 iterations per property test
- **Tagging**: Each property test must reference its design property

```php
// Example property test structure

it('generates unique and secure tokens for any service group', function () {
    // Feature: servant-self-registration, Property 1: Token Generation Uniqueness and Security
    
    $tokens = [];
    
    for ($i = 0; $i < 100; $i++) {
        $serviceGroup = ServiceGroup::factory()->create();
        $token = app(RegistrationLinkService::class)->getOrCreateToken($serviceGroup);
        
        expect($token)
            ->toHaveLength(64)
            ->not->toBeIn($tokens);
        
        $tokens[] = $token;
    }
})->repeat(100);
```

### Unit Test Coverage

**Token Management Tests**:
- Token generation for new service group
- Token reuse for existing service group
- Token regeneration invalidates old token
- URL generation includes token

**Validation Tests**:
- Missing required fields rejected
- Invalid email format rejected
- Duplicate email rejected
- Invalid phone format rejected
- Duplicate phone rejected
- Short password rejected
- Validation errors are field-specific

**Registration Tests**:
- Valid registration creates user account
- User has correct role (servant)
- User linked to correct service group
- User is active by default
- Password is hashed
- Locale is Arabic by default
- Personal code is unique

**Notification Tests**:
- Notifications created for leaders
- FCM job dispatched for leaders with tokens
- Notification contains servant name and timestamp

**Audit Log Tests**:
- Audit log created on registration
- Audit log contains correct action type
- Audit log contains IP address
- Audit log contains partial token

**Authorization Tests**:
- Super admin can access all groups
- Service leader can access all groups
- Family leader can only access own group
- Servant cannot access registration links

**Security Tests**:
- Rate limiting blocks after 5 attempts
- Invalid token rejected
- CSRF protection enforced
- Password not exposed in responses

### Property-Based Test Coverage

Each correctness property should have a corresponding property-based test:

1. **Property 1**: Token uniqueness and security (100 random service groups)
2. **Property 2**: URL contains token (100 random tokens)
3. **Property 3**: Token reuse idempotence (100 random service groups, multiple calls)
4. **Property 4**: Token regeneration invalidation (100 random service groups)
5. **Property 5**: Required fields validation (100 random incomplete submissions)
6. **Property 6**: Email format validation (100 random invalid emails)
7. **Property 7**: Email uniqueness validation (100 random existing emails)
8. **Property 8**: Phone format validation (100 random invalid phones)
9. **Property 9**: Phone uniqueness validation (100 random existing phones)
10. **Property 10**: Password length validation (100 random short passwords)
11. **Property 11**: Validation error specificity (100 random invalid submissions)
12. **Property 12**: Complete account creation (100 random valid registrations)
13. **Property 13**: Personal code uniqueness (100 random registrations)
14. **Property 14**: Leader notification creation (100 random registrations)
15. **Property 15**: FCM dispatch (100 random registrations with FCM tokens)
16. **Property 16**: Authentication round trip (100 random registrations)
17. **Property 17**: Login timestamp recording (100 random logins)
18. **Property 18**: Self-registered count accuracy (100 random service groups)
19. **Property 19**: Access authorization (100 random user/group combinations)
20. **Property 20**: Audit log creation (100 random registrations)
21. **Property 21**: Token validation (100 random invalid tokens)
22. **Property 22**: Rate limiting (100 rapid attempts from same IP)
23. **Property 23**: CSRF protection (100 requests without CSRF token)

### Integration Tests

- **Full Registration Flow**: From link access to successful login
- **Notification Flow**: From registration to FCM delivery
- **Error Flow**: From invalid submission to error display
- **Authorization Flow**: From link access to policy enforcement

### Test Database

- Use SQLite in-memory database for fast test execution
- Use factories for generating test data
- Use database transactions to isolate tests
- Seed minimal required data (roles, permissions)

## Implementation Notes

### Migration Order

1. `add_registration_token_to_service_groups_table.php`
2. `add_servant_self_registered_action_to_audit_logs.php`

### Service Registration

```php
// في AppServiceProvider::register()

$this->app->singleton(RegistrationLinkService::class);
$this->app->singleton(RegistrationService::class);
```

### Localization Keys

```php
// في resources/lang/ar/registration.php

return [
    'title' => 'تسجيل خادم جديد',
    'service_group' => 'مجموعة الخدمة',
    'submit' => 'تسجيل',
    'already_have_account' => 'لديك حساب بالفعل؟ تسجيل الدخول',
    'success' => 'تم التسجيل بنجاح! يمكنك الآن تسجيل الدخول.',
    
    'fields' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
    ],
    
    'errors' => [
        'invalid_token' => 'رابط التسجيل غير صالح أو منتهي الصلاحية.',
        'email_exists' => 'البريد الإلكتروني مستخدم بالفعل. يرجى تسجيل الدخول.',
        'phone_exists' => 'رقم الهاتف مستخدم بالفعل.',
        'password_min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
        'rate_limit_exceeded' => 'تم تجاوز الحد المسموح من المحاولات. يرجى المحاولة لاحقاً.',
        'system_error' => 'حدث خطأ في النظام. يرجى المحاولة لاحقاً.',
    ],
];
```

### Performance Considerations

- **Token Lookup**: Index on `service_groups.registration_token` for fast validation
- **Duplicate Check**: Unique indexes on `users.email` and `users.phone` for fast validation
- **Notification Bulk Insert**: Use `DB::table()->insert()` for multiple notifications
- **FCM Queue**: Dispatch FCM job to queue to avoid blocking registration response
- **Database Transactions**: Keep transactions short to minimize lock time

### Monitoring and Observability

```php
// في RegistrationService::register()

Log::info('Self-registration initiated', [
    'service_group_id' => $serviceGroup->id,
    'email' => $data['email'],
    'ip' => $ipAddress,
]);

Log::info('Self-registration completed', [
    'user_id' => $user->id,
    'service_group_id' => $serviceGroup->id,
    'duration_ms' => $duration,
]);
```

### Future Enhancements

- **Email Verification**: Require email verification before account activation
- **Token Expiration**: Add optional expiration date for registration tokens
- **Registration Approval**: Add optional manual approval workflow
- **Custom Fields**: Allow service groups to request additional information
- **Registration Limits**: Limit number of registrations per token
- **Analytics Dashboard**: Track registration metrics and conversion rates
