<?php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone',
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
        ];
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
     */
    public function setPersonalCodeAttribute(string $value): void
    {
        $this->attributes['personal_code']      = encrypt($value);
        $this->attributes['personal_code_hash'] = hash('sha256', $value);
    }

    /**
     * فك تشفير الكود الشخصي عند القراءة
     */
    public function getPersonalCodeAttribute(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Exception) {
            return $value; // قيمة غير مشفرة (بيانات قديمة)
        }
    }

    public function isAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isServiceLeader(): bool
    {
        return $this->role === 'service_leader';
    }

    public function isFamilyLeader(): bool
    {
        return $this->role === 'family_leader';
    }

    public function isServant(): bool
    {
        return $this->role === 'servant';
    }

    // ── Self-Registration Methods ──

    /**
     * توليد personal_code فريد للخادم الجديد
     * Requirements: 4.7
     */
    public static function generateUniquePersonalCode(): string
    {
        do {
            $code   = str_pad((string) random_int(1000, 999999), 4, '0', STR_PAD_LEFT);
            $hash   = hash('sha256', $code);
            $exists = self::where('personal_code_hash', $hash)->exists();
        } while ($exists);

        return $code;
    }

    /**
     * إنشاء خادم جديد من خلال التسجيل الذاتي
     * Requirements: 4.1-4.7
     *
     * الحساب يتم إنشاؤه نشطاً (is_active = true) ليتمكن الخادم من تسجيل الدخول فوراً
     */
    public static function createFromSelfRegistration(array $data, ServiceGroup $serviceGroup): self
    {
        return self::create([
            'name'             => $data['name'],
            'email'            => $data['email'],
            'phone'            => $data['phone'],
            'password'         => $data['password'], // يتم تشفيره تلقائياً
            'personal_code'    => self::generateUniquePersonalCode(),
            'role'             => 'servant',
            'service_group_id' => $serviceGroup->id,
            'locale'           => 'ar',
            'is_active'        => true,
        ]);
    }

    // ── Filament ──

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }
}
