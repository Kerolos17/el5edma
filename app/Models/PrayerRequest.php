<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'beneficiary_id', 'title', 'body',
        'status', 'created_by', 'answered_at',
    ];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime'];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
