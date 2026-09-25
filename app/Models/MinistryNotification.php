<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinistryNotification extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'data', 'read_at', 'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'data'       => 'array',
            'read_at'    => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Localized human-readable notification type. Falls back to the raw
     * type slug when no translation exists.
     */
    public function getTypeLabelAttribute(): string
    {
        $key = "notifications.types.{$this->type}";

        return __($key) === $key ? (string) $this->type : __($key);
    }

    /**
     * Title localized for the current viewer. System notifications store
     * their text at creation time in the creator's locale, so known types
     * are re-translated at display time instead of showing a stale string.
     */
    public function getDisplayTitleAttribute(): string
    {
        $key = match ($this->type) {
            'birthday'        => 'notifications.birthday_title',
            'critical_case'   => 'notifications.critical_case_title',
            'visit_reminder'  => 'notifications.visit_reminder_title',
            'unvisited_alert' => 'notifications.unvisited_alert_title',
            'new_beneficiary' => 'notifications.new_beneficiary_title',
            default           => null,
        };

        return $key ? (string) __($key) : (string) $this->title;
    }
}
