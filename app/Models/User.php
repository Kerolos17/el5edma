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
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'phone',
        'personal_code', 'fcm_token', 'role',
        'service_group_id', 'locale', 'is_active', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'fcm_token',
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

    // ── Filament ──

  public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }
}
