<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * انضمام خادم جديد: يُنشأ الطلب مع الحساب في نفس اللحظة، ويبقى الحساب مغلوقًا
 * (is_active=false) حتى يقبل مسؤول مخوّل الطلب. حالة الطلب — وليس أي مدخل من
 * المتصفح — هي ما يحدد ما يستطيع صاحبه رؤيته.
 */
class JoinRequest extends Model
{
    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_CHANGES_REQUESTED = 'changes_requested';

    public const ACTION_SUSPENDED = 'suspended';

    public const ACTION_REACTIVATED = 'reactivated';

    protected $fillable = [
        'user_id', 'service_group_id', 'desired_role', 'status',
        'reviewed_by', 'reviewed_at', 'final_role', 'final_service_group_id',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceGroup(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class);
    }

    public function finalServiceGroup(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class, 'final_service_group_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(JoinRequestReview::class)->latest('created_at');
    }

    // ── Scopes & helpers ──

    /** طلبات لا تزال في انتظار قرار (بما فيها تلك التي تنتظر استكمال بيانات). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_INCOMPLETE, self::STATUS_PENDING]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_INCOMPLETE, self::STATUS_PENDING], true);
    }

    public function statusLabel(): string
    {
        return __("join_requests.statuses.{$this->status}");
    }

    public function desiredRoleLabel(): string
    {
        return __("users.roles.{$this->desired_role}");
    }

    public function finalRoleLabel(): ?string
    {
        return $this->final_role
            ? __("users.roles.{$this->final_role}")
            : null;
    }

    /**
     * Every inactive user account that predates the join-request system
     * becomes a pending request, preserving the historical approval queue.
     * Idempotent: accounts that already have a request are skipped.
     */
    public static function backfillInactiveUsers(): int
    {
        $created = 0;

        User::query()
            ->where('is_active', false)
            ->whereDoesntHave('joinRequest')
            ->chunkById(200, function ($users) use (&$created) {
                foreach ($users as $user) {
                    self::create([
                        'user_id'          => $user->id,
                        'service_group_id' => $user->service_group_id,
                        'desired_role'     => $user->role->value,
                        'status'           => self::STATUS_PENDING,
                    ]);
                    $created++;
                }
            });

        return $created;
    }
}
