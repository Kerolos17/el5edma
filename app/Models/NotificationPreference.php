<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * تفضيلات الإشعارات: صف = نوع كتمه المستخدم، وغياب الصف = مفعّل.
 * الأنواع الحرجة والإدارية (critical_case وطلبات الانضمام وقراراتها)
 * غير قابلة للكتم إطلاقًا — her canMuteTypes.
 */
class NotificationPreference extends Model
{
    /** Types a user may mute. Everything else always arrives. */
    public const MUTABLE_TYPES = [
        'birthday',
        'visit_reminder',
        'unvisited_alert',
        'new_beneficiary',
        'visit_created',
    ];

    protected $fillable = ['user_id', 'type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Does this user accept this notification type? */
    public static function allows(User $user, string $type): bool
    {
        if (! in_array($type, self::MUTABLE_TYPES, true)) {
            return true;
        }

        return ! self::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->exists();
    }

    /** Filter a set of users down to those accepting this type (one query). */
    public static function filterAllowed(iterable $users, string $type): Collection
    {
        $users = collect($users);

        if (! in_array($type, self::MUTABLE_TYPES, true)) {
            return $users;
        }

        $muted = self::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->where('type', $type)
            ->pluck('user_id')
            ->all();

        return $users->reject(fn ($user) => in_array($user->id, $muted, true))->values();
    }

    /** @return list<string> muted types for this user */
    public static function mutedTypesFor(User $user): array
    {
        return self::query()
            ->where('user_id', $user->id)
            ->pluck('type')
            ->all();
    }

    public static function toggle(User $user, string $type): bool
    {
        if (! in_array($type, self::MUTABLE_TYPES, true)) {
            return false;
        }

        $existing = self::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        if ($existing) {
            $existing->delete();

            return true; // now enabled
        }

        self::create(['user_id' => $user->id, 'type' => $type]);

        return false; // now muted
    }
}
