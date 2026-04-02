<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    const TTL_SERVICE_GROUPS = 3600;  // 1 hour

    const TTL_SERVANTS = 3600;  // 1 hour

    const TTL_GOVERNORATES = 86400; // 24 hours

    /**
     * Get cached service groups for filters
     */
    public static function getServiceGroups(): array
    {
        return Cache::remember('filter_options:service_groups', self::TTL_SERVICE_GROUPS, fn () => ServiceGroup::query()
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray());
    }

    /**
     * Get cached active servants for filters
     */
    public static function getActiveServants(): array
    {
        return Cache::remember('filter_options:servants', self::TTL_SERVANTS, function () {
            return User::query()
                ->where('role', UserRole::Servant)
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Get cached governorates for filters
     */
    public static function getGovernorates(): array
    {
        return Cache::remember('filter_options:governorates', self::TTL_GOVERNORATES, fn () => Beneficiary::query()
            ->whereNotNull('governorate')
            ->distinct()
            ->pluck('governorate', 'governorate')
            ->toArray());
    }

    /**
     * Invalidate service group related caches
     */
    public static function invalidateServiceGroupCaches(): void
    {
        Cache::forget('filter_options:service_groups');
    }

    /**
     * Invalidate user related caches
     */
    public static function invalidateUserCaches(): void
    {
        Cache::forget('filter_options:servants');
    }

    /**
     * Invalidate all filter caches
     */
    public static function invalidateAllFilterCaches(): void
    {
        Cache::forget('filter_options:service_groups');
        Cache::forget('filter_options:servants');
        Cache::forget('filter_options:governorates');
    }

    /**
     * Get active service groups with full model data (leaders eager-loaded)
     */
    public static function getActiveServiceGroups(): Collection
    {
        return Cache::remember('active_service_groups', self::TTL_SERVICE_GROUPS, fn () => ServiceGroup::where('is_active', true)
            ->with(['leader', 'serviceLeader'])
            ->get());
    }

    /**
     * Get active users (lightweight — id, name, role, service_group_id only)
     */
    public static function getActiveUsers(): Collection
    {
        return Cache::remember('active_users', self::TTL_SERVANTS, fn () => User::where('is_active', true)
            ->select('id', 'name', 'role', 'service_group_id')
            ->get());
    }

    /**
     * Clear user and service group model caches
     */
    public static function clearUserCache(): void
    {
        Cache::forget('active_users');
        Cache::forget('active_service_groups');
        self::invalidateUserCaches();
        self::invalidateServiceGroupCaches();
    }
}
