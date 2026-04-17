<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'profile_photo',
        'personal_code', 'personal_code_hash', 'fcm_token', 'role',
        'service_group_id', 'locale', 'is_active', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'fcm_token', 'personal_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
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

    // ── Helpers ──

    /**
     * تعيين الكود الشخصي مع توليد الـ hash تلقائياً للبحث
     * الكود يُخزَّن كنص عادي في varchar(10) — الـ hash للبحث السريع
     */
    public function setPersonalCodeAttribute(?string $value): void
    {
        $this->attributes['personal_code']      = $value;
        $this->attributes['personal_code_hash'] = $value !== null ? hash('sha256', $value) : null;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo
            ? '/storage/' . $this->profile_photo
            : null;
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

    // ── Self-Registration Methods ──

    /**
     * توليد personal_code فريد للخادم الجديد
     * Requirements: 4.7
     */
    public static function generateUniquePersonalCode(): string
    {
        do {
            $code   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $hash   = hash('sha256', $code);
            $exists = self::where('personal_code_hash', $hash)->exists();
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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }
}
