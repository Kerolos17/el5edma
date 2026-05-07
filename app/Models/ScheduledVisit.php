<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function servants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'scheduled_visit_servants', 'scheduled_visit_id', 'servant_id')
            ->wherePivotNotNull('servant_id');
    }

    public function completedVisit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'completed_visit_id');
    }

    public function scopeAssignedTo(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where(function (Builder $builder) use ($userId): void {
            $builder->where('assigned_servant_id', $userId)
                ->orWhereHas('servants', fn (Builder $servants) => $servants->whereKey($userId));
        });
    }

    public function isAssignedTo(User|int|null $user): bool
    {
        if ($user === null) {
            return false;
        }

        $userId = $user instanceof User ? $user->id : $user;

        if ((int) $this->assigned_servant_id === (int) $userId) {
            return true;
        }

        return $this->servants->contains('id', $userId);
    }

    public function syncAssignedServants(array $servantIds): void
    {
        $ids = collect($servantIds)
            ->filter()
            ->map(fn (mixed $id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->servants()->sync($ids);

        $this->forceFill([
            'assigned_servant_id' => $ids[0] ?? null,
        ])->saveQuietly();

        $this->unsetRelation('servants');
        $this->unsetRelation('assignedServant');
    }

    public function getAssignedServantNamesAttribute(): string
    {
        $names = $this->servants
            ->pluck('name')
            ->filter()
            ->values();

        if ($names->isEmpty() && $this->assignedServant?->name) {
            $names = collect([$this->assignedServant->name]);
        }

        return $names->isNotEmpty()
            ? $names->implode('، ')
            : 'غير معين';
    }
}
