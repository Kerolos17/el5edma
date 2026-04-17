<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'leader_id', 'service_leader_id',
        'description', 'is_active',
        'registration_token', 'registration_token_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'                       => 'boolean',
            'registration_token_generated_at' => 'datetime',
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function serviceLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_leader_id');
    }

    public function servants(): HasMany
    {
        return $this->hasMany(User::class)->where('role', UserRole::Servant);
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }

    /**
     * التحقق من وجود رمز تسجيل نشط
     */
    public function hasActiveRegistrationToken(): bool
    {
        if (empty($this->registration_token) || empty($this->registration_token_generated_at)) {
            return false;
        }

        $expiryHours = config('registration.token_expiry_hours', 72);

        return $this->registration_token_generated_at->diffInHours(now()) < $expiryHours;
    }

    /**
     * الحصول على عدد الخدام المسجلين ذاتياً
     * (يتم تتبعهم من خلال audit logs)
     */
    public function getSelfRegisteredServantsCount(): int
    {
        return AuditLog::where('model_type', User::class)
            ->where('action', 'servant_self_registered')
            ->whereJsonContains('new_values->service_group_id', $this->id)
            ->count();
    }
}
