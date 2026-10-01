<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل مراجعة غير قابل للتعديل: كل قرار إداري على طلب انضمام يُ appended هنا
 * ولا يُحدَّث أو يُحذف. الطابع الزمني يُكتب من PHP (قاعدة الاستضافة بتوقيت UTC).
 */
class JoinRequestReview extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'join_request_id', 'reviewer_id', 'action', 'note',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $review) {
            $review->created_at ??= now();
        });
    }

    public function joinRequest(): BelongsTo
    {
        return $this->belongsTo(JoinRequest::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function actionLabel(): string
    {
        return __("join_requests.actions.{$this->action}");
    }
}
