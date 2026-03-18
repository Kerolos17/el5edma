<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
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
        return $this->hasMany(User::class)->where('role', 'servant');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }
}
