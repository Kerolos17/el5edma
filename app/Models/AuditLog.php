<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false; // immutable — created_at فقط

    protected $fillable = [
        'user_id', 'model_type', 'model_id',
        'action', 'old_values', 'new_values', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * تسجيل عملية تسجيل ذاتي
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public static function logSelfRegistration(
        User $user,
        ServiceGroup $serviceGroup,
        string $token,
        string $ipAddress,
    ): self {
        return self::create([
            'user_id'    => $user->id,
            'model_type' => User::class,
            'model_id'   => $user->id,
            'action'     => 'servant_self_registered',
            'old_values' => null,
            'new_values' => [
                'name'               => $user->name,
                'email'              => $user->email,
                'phone'              => $user->phone,
                'service_group_id'   => $serviceGroup->id,
                'service_group_name' => $serviceGroup->name,
                'registration_token' => substr($token, 0, 8) . '...', // partial token for security
            ],
            'ip_address' => $ipAddress,
        ]);
    }
}
