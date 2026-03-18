<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Visit extends Model
{
    use HasFactory;
    protected $fillable = [
        'beneficiary_id', 'type', 'visit_date', 'duration_minutes',
        'beneficiary_status', 'feedback', 'is_critical',
        'critical_resolved_at', 'critical_resolved_by',
        'needs_family_leader', 'needs_service_leader', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date'           => 'datetime',
            'critical_resolved_at' => 'datetime',
            'is_critical'          => 'boolean',
            'needs_family_leader'  => 'boolean',
            'needs_service_leader' => 'boolean',
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'critical_resolved_by');
    }

    public function servants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'visit_servants', 'visit_id', 'servant_id');
    }
}
