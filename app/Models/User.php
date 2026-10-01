<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\ResetPasswordArabic;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'profile_photo',
        'personal_code', 'personal_code_hash', 'fcm_token', 'role',
        'service_group_id', 'locale', 'is_active', 'suspended_at', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'fcm_token', 'personal_code', 'personal_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'suspended_at'      => 'datetime',
            'is_active'         => 'boolean',
            'password'          => 'hashed',
            'role'              => UserRole::class,
        ];
    }

    protected static function booted(): void
    {
        // personal_code_hash is set by the setPersonalCodeAttribute mutator
    }

    // ── Relationships ──

    public function serviceGroup()
    {
        return $this->belongsTo(ServiceGroup::class);
    }

    public function assignedBeneficiaries()
    {
        return $this->hasMany(Beneficiary::class, 'assigned_servant_id');
    }

    public function visits()
    {
        return $this->hasMany(Visit::class, 'created_by');
    }

    public function ministryNotifications()
    {
        return $this->hasMany(MinistryNotification::class);
    }

    public function pushDevices()
    {
        return $this->hasMany(PushDevice::class);
    }

    public function joinRequest()
    {
        return $this->hasOne(JoinRequest::class);
    }

    /**
     * Return all active push tokens for this user.
     *
     * The legacy users.fcm_token value is included during the transition so
     * existing sessions keep receiving pushes until all devices re-register.
     */
    public function pushTokens(): array
    {
        $devices = $this->relationLoaded('pushDevices')
            ? $this->pushDevices
            : $this->pushDevices()->get(['id', 'user_id', 'token']);

        // Access through the model instances so the encrypted cast is applied.
        $tokens = $devices
            ->pluck('token')
            ->filter()
            ->all();

        if ($this->fcm_token) {
            $tokens[] = $this->fcm_token;
        }

        return array_values(array_unique($tokens));
    }

    // ── Helpers ──

    /**
     * Peppered blind-index hash for personal-code lookups.
     *
     * Uses HMAC-SHA256 keyed by APP_KEY (a pepper that lives outside the
     * database) so a database-only leak cannot brute-force the short codes
     * via rainbow tables.
     */
    public static function hashPersonalCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    /**
     * تعيين الكود الشخصي: يُشفَّر عند التخزين ويُولَّد له blind-index hash للبحث.
     * التشفير يحمي الكود في حال تسريب قاعدة البيانات، والـ HMAC يمنع الكسر بجداول قوس قزح.
     */
    public function setPersonalCodeAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['personal_code']      = null;
            $this->attributes['personal_code_hash'] = null;

            return;
        }

        $this->attributes['personal_code']      = Crypt::encryptString($value);
        $this->attributes['personal_code_hash'] = self::hashPersonalCode($value);
    }

    /**
     * قراءة الكود الشخصي المشفّر. مع fallback آمن للقيم القديمة غير المشفّرة (plaintext)
     * أثناء فترة الانتقال قبل تشغيل migration التشفير.
     */
    public function getPersonalCodeAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value; // legacy plaintext value, not yet migrated
        }
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo
            ? '/storage/' . $this->profile_photo
            : null;
    }

    /**
     * WhatsApp deep link for the servant's phone (Egypt country code,
     * matching the beneficiary accessor convention).
     */
    public function getWhatsappUrlAttribute(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', $this->phone);

        return $clean !== '' ? "https://wa.me/2{$clean}" : null;
    }

    public function getTelUrlAttribute(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $clean = preg_replace('/[^0-9+]/', '', $this->phone);

        return $clean !== '' ? "tel:{$clean}" : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isServiceLeader(): bool
    {
        return $this->role === UserRole::ServiceLeader;
    }

    public function isFamilyLeader(): bool
    {
        return $this->role === UserRole::FamilyLeader;
    }

    public function isServant(): bool
    {
        return $this->role === UserRole::Servant;
    }

    public function managedServiceGroups(): Collection
    {
        if ($this->role === UserRole::SuperAdmin) {
            return ServiceGroup::query()->get();
        }

        if ($this->role === UserRole::ServiceLeader) {
            return ServiceGroup::query()
                ->where('service_leader_id', $this->id)
                ->get();
        }

        if (in_array($this->role, [UserRole::FamilyLeader, UserRole::Servant], true) && $this->service_group_id) {
            return ServiceGroup::query()
                ->whereKey($this->service_group_id)
                ->get();
        }

        return new Collection;
    }

    public function managedServiceGroupIds(): array
    {
        return $this->managedServiceGroups()
            ->pluck('id')
            ->all();
    }

    public function managesServiceGroup(?int $serviceGroupId): bool
    {
        if ($this->role === UserRole::SuperAdmin) {
            return true;
        }

        if (! $serviceGroupId) {
            return false;
        }

        return in_array($serviceGroupId, $this->managedServiceGroupIds(), true);
    }

    /**
     * The dashboard route this role lands on after login. Servants work
     * from the mobile-first servant panel; everyone else uses the web app.
     * (/admin is back-office only and is never a login destination.)
     *
     * A member whose join request is still open holds at most a waiting-page
     * session — this route is their only landing, and the access middleware
     * keeps redirecting them here until the request is approved.
     */
    public function homeRoute(): string
    {
        if (! $this->is_active && $this->hasPendingJoinRequest()) {
            return 'registration.status';
        }

        return $this->role === UserRole::Servant
            ? 'servant.dashboard'
            : 'app.dashboard';
    }

    public function hasPendingJoinRequest(): bool
    {
        if (! $this->exists) {
            return false;
        }

        return $this->joinRequest()
            ->whereIn('status', [JoinRequest::STATUS_INCOMPLETE, JoinRequest::STATUS_PENDING])
            ->exists();
    }

    // ── Self-Registration Methods ──

    /**
     * توليد كود خادم فريد بصيغة KH-XXXX-XX — عشوائي بالكامل، بلا أي بيانات
     * شخصية ولا ترتيب، بحروف/أرقام غير قابلة للخلط (بلا I/O/0/1). يُخزَّن
     * مشفّراً عبر الـ mutator مع blind-index hash للبحث.
     * Requirements: 4.7
     */
    public static function generateUniquePersonalCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $chunk = '';
            for ($i = 0; $i < 4; $i++) {
                $chunk .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $code = 'KH-' . $chunk . '-' . str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);

            $exists = self::where('personal_code_hash', self::hashPersonalCode($code))->exists();
        } while ($exists);

        return $code;
    }

    /**
     * إنشاء خادم جديد من خلال التسجيل الذاتي
     * Requirements: 4.1-4.7
     *
     * الحساب يتم إنشاؤه غير نشط (is_active = false) — يتطلب موافقة مدير النظام أو أمين الخدمة
     */
    public static function createFromSelfRegistration(array $data, ServiceGroup $serviceGroup): self
    {
        return self::create([
            'name'             => $data['name'],
            'email'            => $data['email'],
            'phone'            => $data['phone'],
            'password'         => $data['password'], // يتم تشفيره تلقائياً
            'personal_code'    => self::generateUniquePersonalCode(),
            'role'             => UserRole::Servant->value,
            'service_group_id' => $serviceGroup->id,
            'locale'           => app()->getLocale(),
            'is_active'        => false,
        ]);
    }

    // ── Filament ──

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_photo
            ? '/storage/' . $this->profile_photo
            : null;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordArabic($token));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // The Filament login page is the product's only login UI, so every
        // active user must pass this gate to authenticate. Back-office pages
        // stay protected by the RedirectNonAdmin middleware, and the login
        // response routes each role to its own dashboard.
        //
        // Applicants with an open join request may authenticate too, but their
        // homeRoute() pins them to the waiting page and the access middleware
        // confines every other path there — no data is ever reachable.
        return $this->is_active || $this->hasPendingJoinRequest();
    }
}
