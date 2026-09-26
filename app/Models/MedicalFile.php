<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalFile extends Model
{
    use HasFactory;

    public $timestamps = false; // immutable — created_at فقط

    protected $fillable = [
        'beneficiary_id', 'file_path', 'file_type', 'title', 'uploaded_by',
    ];

    protected static function booted(): void
    {
        // created_at is DATETIME DEFAULT CURRENT_TIMESTAMP and the MySQL
        // server runs in UTC — DB-filled values displayed 3 hours behind.
        // Always stamp from PHP (app timezone) instead.
        static::creating(function (self $file) {
            $file->created_at ??= now();
        });
    }

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
