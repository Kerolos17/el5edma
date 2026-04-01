<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'phone',
        'personal_code', 'fcm_token', 'role',
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
        static::saving(function (User $user) {
            if ($user->isDirty('personal_code') && $user->personal_code !== null) {
                $user->personal_code_hash = hash('sha256', $user->personal_code);
            }
        });
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

    // ── Filament ──

  public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }
}
