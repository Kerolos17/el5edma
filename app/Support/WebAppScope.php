<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\PrayerRequest;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;

class WebAppScope
{
    public static function beneficiaries(User $user): Builder
    {
        $query = Beneficiary::query();

        return match ($user->role) {
            UserRole::SuperAdmin    => $query,
            UserRole::ServiceLeader => $query->whereIn('service_group_id', $user->managedServiceGroupIds()),
            UserRole::FamilyLeader  => $query->where('service_group_id', $user->service_group_id),
            UserRole::Servant       => $query->where(function (Builder $builder) use ($user): void {
                $builder->where('assigned_servant_id', $user->id)
                    ->when(
                        $user->service_group_id,
                        fn (Builder $groupQuery) => $groupQuery->orWhere('service_group_id', $user->service_group_id),
                    );
            }),
        };
    }

    public static function visits(User $user): Builder
    {
        return Visit::query()
            ->with(['beneficiary.serviceGroup', 'createdBy'])
            ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', self::beneficiaries($user)->select('id')));
    }

    public static function scheduledVisits(User $user): Builder
    {
        $query = ScheduledVisit::query()
            ->with(['beneficiary.serviceGroup', 'assignedServant', 'servants']);

        return match ($user->role) {
            UserRole::SuperAdmin    => $query,
            UserRole::ServiceLeader => $query->whereHas(
                'beneficiary',
                fn (Builder $builder) => $builder->whereIn('service_group_id', $user->managedServiceGroupIds()),
            ),
            UserRole::FamilyLeader => $query->whereHas(
                'beneficiary',
                fn (Builder $builder) => $builder->where('service_group_id', $user->service_group_id),
            ),
            UserRole::Servant => $query->assignedTo($user),
        };
    }

    public static function prayerRequests(User $user): Builder
    {
        return PrayerRequest::query()
            ->with(['beneficiary.serviceGroup', 'createdBy'])
            ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', self::beneficiaries($user)->select('id')));
    }

    public static function medicalFiles(User $user): Builder
    {
        return MedicalFile::query()
            ->with(['beneficiary.serviceGroup', 'uploadedBy'])
            ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', self::beneficiaries($user)->select('id')));
    }

    public static function users(User $user): Builder
    {
        $query = User::query()->with('serviceGroup');

        return match ($user->role) {
            UserRole::SuperAdmin    => $query,
            UserRole::ServiceLeader => $query->whereIn('service_group_id', $user->managedServiceGroupIds()),
            UserRole::FamilyLeader  => $query->where('service_group_id', $user->service_group_id),
            UserRole::Servant       => $query->whereKey($user->id),
        };
    }

    public static function serviceGroups(User $user): Builder
    {
        $query = ServiceGroup::query()
            ->with(['leader', 'serviceLeader'])
            ->withCount(['beneficiaries', 'servants']);

        return match ($user->role) {
            UserRole::SuperAdmin    => $query,
            UserRole::ServiceLeader => $query->whereIn('id', $user->managedServiceGroupIds()),
            UserRole::FamilyLeader, UserRole::Servant => $query->whereKey($user->service_group_id),
        };
    }

    public static function roleLabel(UserRole $role): string
    {
        return __("web_app.roles.{$role->value}");
    }
}
