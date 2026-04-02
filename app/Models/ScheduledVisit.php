<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'beneficiary_id', 'assigned_servant_id', 'scheduled_date',
        'scheduled_time', 'notes', 'status', 'reminder_sent_at',
        'completed_visit_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date'   => 'date',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function assignedServant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_servant_id');
    }

    public function completedVisit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'completed_visit_id');
    }
}
