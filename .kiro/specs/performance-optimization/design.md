# Design Document: Performance Optimization

## Overview

This design addresses comprehensive performance optimization for the Laravel 12 + Filament 4 beneficiary management system. The system currently suffers from N+1 query problems across tables, widgets, reports, and exports, resulting in slow page loads and potential timeouts.

The optimization strategy focuses on four key areas:

1. **Eager Loading**: Eliminate N+1 queries by preloading relationships at the query level
2. **Query Aggregation**: Use database-level aggregation (withCount, withMax) instead of per-record queries
3. **Caching**: Cache frequently accessed, rarely changing data (service groups, servants, governorates)
4. **Database Indexing**: Add strategic indexes to speed up filtering and relationship queries

The solution will maintain backward compatibility with existing functionality while dramatically improving performance. All optimizations will be transparent to end users and will not change the UI or user experience.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Filament UI Layer                        │
│  (Tables, Widgets, Forms, Infolists, Actions)               │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│              Optimized Resource Layer                        │
│  • BeneficiaryResource::getEloquentQuery()                  │
│  • VisitResource::getEloquentQuery()                        │
│  • ScheduledVisitResource::getEloquentQuery()               │
│  • ServiceGroupResource::getEloquentQuery()                 │
│  └─► Eager Loading Configuration                            │
│  └─► Role-Based Scoping                                     │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                 Query Optimization Layer                     │
│  • EagerLoadingService (centralized eager loading)          │
│  • CacheService (filter options caching)                    │
│  • QueryMonitoringService (slow query detection)            │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                   Database Layer                             │
│  • Indexed Tables (beneficiaries, visits, etc.)             │
│  • Redis Cache (filter options, computed data)              │
└─────────────────────────────────────────────────────────────┘
```

### Optimization Strategy by Component

**Tables (Filament Resources)**
- Apply eager loading in `getEloquentQuery()` method
- Use `withCount()` for relationship counts
- Use `withMax()` for last visit dates
- Maintain eager loading when filters are applied

**Widgets (Dashboard)**
- Optimize queries before passing to widget
- Use single aggregated queries instead of per-record calculations
- Cache widget data where appropriate (with short TTL)

**Reports (PDF Generation)**
- Eager load all relationships before passing to view
- Use aggregation for statistics
- Limit result sets to prevent memory exhaustion (max 1000 records)

**Exports (Excel)**
- Eager load relationships in query() method
- Use chunking for large datasets (>500 records)
- Maintain low base query count


## Components and Interfaces

### 1. EagerLoadingService

A centralized service to manage eager loading configurations for different contexts.

```php
namespace App\Services;

class EagerLoadingService
{
    /**
     * Get eager loading configuration for beneficiaries table
     */
    public static function beneficiariesTable(): array
    {
        return [
            'serviceGroup',
            'assignedServant',
            'createdBy',
        ];
    }

    /**
     * Get eager loading with aggregations for beneficiaries table
     */
    public static function beneficiariesTableWithAggregations(): array
    {
        return [
            'relationships' => self::beneficiariesTable(),
            'withMax' => ['visits' => 'visit_date'],
        ];
    }

    /**
     * Get eager loading configuration for beneficiary infolist
     */
    public static function beneficiaryInfolist(): array
    {
        return [
            'serviceGroup',
            'assignedServant',
            'createdBy',
        ];
    }

    /**
     * Get eager loading configuration for visits table
     */
    public static function visitsTable(): array
    {
        return [
            'beneficiary.serviceGroup',
            'createdBy',
        ];
    }

    /**
     * Get eager loading configuration for scheduled visits table
     */
    public static function scheduledVisitsTable(): array
    {
        return [
            'beneficiary',
            'assignedServant',
        ];
    }

    /**
     * Get eager loading configuration for service groups table
     */
    public static function serviceGroupsTable(): array
    {
        return [
            'relationships' => ['leader', 'serviceLeader'],
            'withCount' => ['servants', 'beneficiaries'],
        ];
    }

    /**
     * Get eager loading for single beneficiary PDF
     */
    public static function singleBeneficiaryPdf(): array
    {
        return [
            'serviceGroup',
            'assignedServant',
            'createdBy',
            'medications' => fn($q) => $q->where('is_active', true),
            'visits' => fn($q) => $q->latest('visit_date')->limit(10),
            'visits.createdBy',
            'medicalFiles',
            'prayerRequests',
        ];
    }
}
```

### 2. CacheService

Manages caching of frequently accessed filter options.

```php
namespace App\Services;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    const TTL_SERVICE_GROUPS = 3600; // 1 hour
    const TTL_SERVANTS = 3600; // 1 hour
    const TTL_GOVERNORATES = 86400; // 24 hours

    /**
     * Get cached service groups for filters
     */
    public static function getServiceGroups(): array
    {
        return Cache::remember('filter_options:service_groups', self::TTL_SERVICE_GROUPS, function () {
            return ServiceGroup::query()
                ->where('is_active', true)
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Get cached active servants for filters
     */
    public static function getActiveServants(): array
    {
        return Cache::remember('filter_options:servants', self::TTL_SERVANTS, function () {
            return User::query()
                ->where('role', 'servant')
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
        return Cache::remember('filter_options:governorates', self::TTL_GOVERNORATES, function () {
            return Beneficiary::query()
                ->whereNotNull('governorate')
                ->distinct()
                ->pluck('governorate', 'governorate')
                ->toArray();
        });
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
}
```

### 3. QueryMonitoringService

Monitors and logs slow queries and N+1 query detection.

```php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryMonitoringService
{
    const SLOW_QUERY_THRESHOLD = 1000; // milliseconds

    /**
     * Enable query monitoring
     */
    public static function enable(): void
    {
        DB::listen(function ($query) {
            if ($query->time > self::SLOW_QUERY_THRESHOLD) {
                self::logSlowQuery($query);
            }
        });
    }

    /**
     * Log slow query
     */
    private static function logSlowQuery($query): void
    {
        $logChannel = app()->environment('production') ? 'slow-queries' : 'daily';

        Log::channel($logChannel)->warning('Slow Query Detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms',
            'connection' => $query->connectionName,
        ]);
    }

    /**
     * Get query count for current request (debug mode)
     */
    public static function getQueryCount(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * Enable query logging for debugging
     */
    public static function enableDebugMode(): void
    {
        DB::enableQueryLog();
    }

    /**
     * Get all queries executed (debug mode)
     */
    public static function getQueries(): array
    {
        return DB::getQueryLog();
    }
}
```


## Data Models

### Database Schema Changes

This optimization requires adding strategic indexes to existing tables. No new tables or columns are needed.

#### Beneficiaries Table Indexes

```sql
-- Existing columns, adding indexes only
CREATE INDEX idx_beneficiaries_service_group_id ON beneficiaries(service_group_id);
CREATE INDEX idx_beneficiaries_assigned_servant_id ON beneficiaries(assigned_servant_id);
CREATE INDEX idx_beneficiaries_status ON beneficiaries(status);
CREATE INDEX idx_beneficiaries_governorate ON beneficiaries(governorate);
```

**Rationale**: These indexes optimize filtering by service group (role-based scoping), assigned servant (role-based scoping), status (active/inactive filters), and governorate (location filters).

#### Visits Table Indexes

```sql
-- Existing columns, adding indexes only
CREATE INDEX idx_visits_beneficiary_id ON visits(beneficiary_id);
CREATE INDEX idx_visits_visit_date ON visits(visit_date);
CREATE INDEX idx_visits_created_by ON visits(created_by);
CREATE INDEX idx_visits_critical_cases ON visits(is_critical, critical_resolved_at);
```

**Rationale**: 
- `beneficiary_id`: Optimizes relationship queries and last visit date calculations
- `visit_date`: Speeds up date range filters and sorting
- `created_by`: Optimizes filtering by creator
- Composite index on `(is_critical, critical_resolved_at)`: Optimizes critical cases widget query

#### Scheduled Visits Table Indexes

```sql
-- Existing columns, adding indexes only
CREATE INDEX idx_scheduled_visits_beneficiary_id ON scheduled_visits(beneficiary_id);
CREATE INDEX idx_scheduled_visits_assigned_servant_id ON scheduled_visits(assigned_servant_id);
CREATE INDEX idx_scheduled_visits_scheduled_date ON scheduled_visits(scheduled_date);
```

**Rationale**: Optimizes relationship queries, filtering by assigned servant, and date-based queries.

#### Service Groups Table Indexes

```sql
-- Existing columns, adding indexes only
CREATE INDEX idx_service_groups_leader_id ON service_groups(leader_id);
CREATE INDEX idx_service_groups_service_leader_id ON service_groups(service_leader_id);
CREATE INDEX idx_service_groups_is_active ON service_groups(is_active);
```

**Rationale**: Optimizes relationship queries and active group filtering.

#### Users Table Indexes

```sql
-- Existing columns, adding indexes only
CREATE INDEX idx_users_service_group_id ON users(service_group_id);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_is_active ON users(is_active);
```

**Rationale**: Optimizes role-based queries and active user filtering.

### Migration Structure

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_performance_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Beneficiaries indexes
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->index('service_group_id', 'idx_beneficiaries_service_group_id');
            $table->index('assigned_servant_id', 'idx_beneficiaries_assigned_servant_id');
            $table->index('status', 'idx_beneficiaries_status');
            $table->index('governorate', 'idx_beneficiaries_governorate');
        });

        // Visits indexes
        Schema::table('visits', function (Blueprint $table) {
            $table->index('beneficiary_id', 'idx_visits_beneficiary_id');
            $table->index('visit_date', 'idx_visits_visit_date');
            $table->index('created_by', 'idx_visits_created_by');
            $table->index(['is_critical', 'critical_resolved_at'], 'idx_visits_critical_cases');
        });

        // Scheduled visits indexes
        Schema::table('scheduled_visits', function (Blueprint $table) {
            $table->index('beneficiary_id', 'idx_scheduled_visits_beneficiary_id');
            $table->index('assigned_servant_id', 'idx_scheduled_visits_assigned_servant_id');
            $table->index('scheduled_date', 'idx_scheduled_visits_scheduled_date');
        });

        // Service groups indexes
        Schema::table('service_groups', function (Blueprint $table) {
            $table->index('leader_id', 'idx_service_groups_leader_id');
            $table->index('service_leader_id', 'idx_service_groups_service_leader_id');
            $table->index('is_active', 'idx_service_groups_is_active');
        });

        // Users indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('service_group_id', 'idx_users_service_group_id');
            $table->index('role', 'idx_users_role');
            $table->index('is_active', 'idx_users_is_active');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex('idx_beneficiaries_service_group_id');
            $table->dropIndex('idx_beneficiaries_assigned_servant_id');
            $table->dropIndex('idx_beneficiaries_status');
            $table->dropIndex('idx_beneficiaries_governorate');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex('idx_visits_beneficiary_id');
            $table->dropIndex('idx_visits_visit_date');
            $table->dropIndex('idx_visits_created_by');
            $table->dropIndex('idx_visits_critical_cases');
        });

        Schema::table('scheduled_visits', function (Blueprint $table) {
            $table->dropIndex('idx_scheduled_visits_beneficiary_id');
            $table->dropIndex('idx_scheduled_visits_assigned_servant_id');
            $table->dropIndex('idx_scheduled_visits_scheduled_date');
        });

        Schema::table('service_groups', function (Blueprint $table) {
            $table->dropIndex('idx_service_groups_leader_id');
            $table->dropIndex('idx_service_groups_service_leader_id');
            $table->dropIndex('idx_service_groups_is_active');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_service_group_id');
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_is_active');
        });
    }
};
```

### Cache Configuration

Redis cache configuration in `config/cache.php`:

```php
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

Redis connection in `config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'),
    
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

### Model Observer Updates

Update existing observers to invalidate caches when data changes:

```php
// app/Observers/ServiceGroupObserver.php
use App\Services\CacheService;

class ServiceGroupObserver
{
    public function updated(ServiceGroup $serviceGroup): void
    {
        // Existing audit log code...
        
        // Invalidate cache
        CacheService::invalidateServiceGroupCaches();
    }

    public function deleted(ServiceGroup $serviceGroup): void
    {
        // Existing audit log code...
        
        // Invalidate cache
        CacheService::invalidateServiceGroupCaches();
    }
}

// app/Observers/UserObserver.php
class UserObserver
{
    public function updated(User $user): void
    {
        // Existing audit log code...
        
        // Invalidate cache
        CacheService::invalidateUserCaches();
    }

    public function deleted(User $user): void
    {
        // Existing audit log code...
        
        // Invalidate cache
        CacheService::invalidateUserCaches();
    }
}
```


## Correctness Properties

A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.

### Property 1: Beneficiaries Table Query Count Bound

For any beneficiaries table render with any number of records and any applied filters, the total number of database queries executed SHALL NOT exceed 5.

**Validates: Requirements 1.4, 1.5**

### Property 2: Beneficiaries Table Eager Loading

For any beneficiaries table render, accessing the serviceGroup, assignedServant, or createdBy relationships on any loaded beneficiary SHALL NOT trigger additional database queries.

**Validates: Requirements 1.1, 1.2**

### Property 3: Beneficiaries Last Visit Aggregation

For any beneficiaries table render displaying last_visit_date, the query count SHALL NOT increase proportionally with the number of beneficiaries (i.e., SHALL use aggregation, not N queries).

**Validates: Requirements 1.3**

### Property 4: Visits Table Query Count Bound

For any visits table render with any number of records, the total number of database queries executed SHALL NOT exceed 3.

**Validates: Requirements 2.4**

### Property 5: Visits Table Eager Loading

For any visits table render, accessing the beneficiary, beneficiary.serviceGroup, or createdBy relationships on any loaded visit SHALL NOT trigger additional database queries.

**Validates: Requirements 2.1, 2.2, 2.3**

### Property 6: Visit Actions Query Efficiency

For any visits table render with actions displayed, the query count SHALL NOT increase when rendering action buttons compared to rendering without actions.

**Validates: Requirements 2.5**

### Property 7: Scheduled Visits Table Query Count Bound

For any scheduled visits table render with any number of records, the total number of database queries executed SHALL NOT exceed 3.

**Validates: Requirements 3.3**

### Property 8: Scheduled Visits Table Eager Loading

For any scheduled visits table render, accessing the beneficiary or assignedServant relationships on any loaded scheduled visit SHALL NOT trigger additional database queries.

**Validates: Requirements 3.1, 3.2**

### Property 9: Service Groups Table Query Count Bound

For any service groups table render with any number of records, the total number of database queries executed SHALL NOT exceed 3.

**Validates: Requirements 4.4**

### Property 10: Service Groups Table Eager Loading

For any service groups table render, accessing the leader or serviceLeader relationships on any loaded service group SHALL NOT trigger additional database queries.

**Validates: Requirements 4.1, 4.2**

### Property 11: Service Groups Count Aggregation

For any service groups table render displaying servants_count or beneficiaries_count, accessing these counts SHALL NOT load the full servants or beneficiaries relationships.

**Validates: Requirements 4.3**

### Property 12: Stats Overview Widget Query Count

For any StatsOverviewWidget render, the total number of database queries executed SHALL be exactly 4 (one per statistic).

**Validates: Requirements 5.1**

### Property 13: Critical Cases Widget Eager Loading

For any CriticalCasesWidget render, accessing the beneficiary or createdBy relationships on any loaded visit SHALL NOT trigger additional database queries.

**Validates: Requirements 5.2**

### Property 14: Unvisited Widget Query Optimization

For any UnvisitedWidget render displaying last visit dates, the query count SHALL NOT increase proportionally with the number of unvisited beneficiaries.

**Validates: Requirements 5.3, 5.4**

### Property 15: Birthday Widget Query Efficiency

For any BirthdayWidget render, filtering beneficiaries by birthday SHALL occur in the database query before eager loading relationships (not in application code after loading all beneficiaries).

**Validates: Requirements 5.5**

### Property 16: Visits Chart Widget Query Count Bound

For any VisitsChartWidget render for 6 months, the total number of database queries executed SHALL NOT exceed 12.

**Validates: Requirements 5.6**

### Property 17: PDF Report Eager Loading

For any PDF report generation (beneficiaries, visits, unvisited, single beneficiary, service group, service group beneficiaries), all relationships displayed in the report SHALL be eager loaded before passing data to the view.

**Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5, 6.6**

### Property 18: PDF Report Size Limit

For any PDF report generation, the result set SHALL be limited to a maximum of 1000 records.

**Validates: Requirements 6.7**

### Property 19: Excel Export Eager Loading

For any Excel export (beneficiaries, visits), all relationships used in the export SHALL be eager loaded in the query() method.

**Validates: Requirements 7.1, 7.2**

### Property 20: Excel Export Chunking

For any Excel export exceeding 500 records, the export SHALL use chunking to process records in batches.

**Validates: Requirements 7.3**

### Property 21: Excel Export Query Count Bound

For any Excel export regardless of record count, the base query count (excluding chunk queries) SHALL NOT exceed 3.

**Validates: Requirements 7.4**

### Property 22: Filter Options Caching

For any filter options load (service groups, servants, governorates), the data SHALL be retrieved from cache if available, and cached with the appropriate TTL (1 hour for service groups and servants, 24 hours for governorates) if not.

**Validates: Requirements 8.1, 8.2, 8.3**

### Property 23: Cache Invalidation on Updates

For any service group or user update/delete operation, the related filter caches SHALL be invalidated.

**Validates: Requirements 8.4, 8.5**

### Property 24: Beneficiary Infolist Query Count Bound

For any beneficiary infolist render, the total number of database queries executed SHALL NOT exceed 2.

**Validates: Requirements 9.4**

### Property 25: Beneficiary Infolist Eager Loading

For any beneficiary infolist render, accessing the serviceGroup, assignedServant, or createdBy relationships SHALL NOT trigger additional database queries.

**Validates: Requirements 9.1, 9.2, 9.3**

### Property 26: Database Indexes Existence

For all tables (beneficiaries, visits, scheduled_visits, service_groups, users), all required indexes as specified in the migration SHALL exist in the database.

**Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8**

### Property 27: Role-Based Scoping Index Usage

For any role-based query scoping (family_leader by service_group_id, servant by assigned_servant_id), the database query plan SHALL use the appropriate index.

**Validates: Requirements 11.1, 11.2**

### Property 28: Scoped Query Eager Loading Preservation

For any resource query with role-based scoping applied, all eager loading optimizations SHALL remain active and query counts SHALL stay within bounds.

**Validates: Requirements 11.3**

### Property 29: Scoping Before Eager Loading

For any resource query with role-based scoping, the scoping filters SHALL be applied in the WHERE clause before eager loading relationships (verified by query execution order).

**Validates: Requirements 11.4**

### Property 30: Pagination Eager Loading Efficiency

For any paginated table, eager loading SHALL only load relationships for records on the current page, not for all records in the table.

**Validates: Requirements 12.2**

### Property 31: Pagination Count Efficiency

For any paginated table, displaying the total record count SHALL NOT require loading all records into memory.

**Validates: Requirements 12.3**

### Property 32: Pagination Consistency

For any paginated table, changing pages SHALL maintain all eager loading optimizations with query counts staying within bounds.

**Validates: Requirements 12.4**

### Property 33: Slow Query Logging

For any database query exceeding 1000ms execution time, the query SHALL be logged with its SQL, bindings, execution time, and connection name.

**Validates: Requirements 13.1**

### Property 34: Resource List Page Eager Loading

For any resource list page load, eager loading SHALL be configured in the getEloquentQuery() method and query counts SHALL stay within specified bounds.

**Validates: Requirements 14.1**

### Property 35: Resource View Page Eager Loading

For any resource view page load, all relationships displayed in the infolist SHALL be eager loaded and query counts SHALL stay within specified bounds.

**Validates: Requirements 14.2**

### Property 36: Resource Edit Page Selective Loading

For any resource edit page load, only relationships required by the form SHALL be eager loaded (not all relationships).

**Validates: Requirements 14.3**

### Property 37: Count Aggregation Usage

For any query displaying relationship counts, the query SHALL use withCount() and SHALL NOT load the full relationship collections.

**Validates: Requirements 15.1, 15.2**

### Property 38: Never Visited Query Optimization

For any query displaying "never visited" beneficiaries, the query SHALL use whereDoesntHave() with indexed columns.

**Validates: Requirements 15.3**

### Property 39: Count Method Efficiency

For any code path where withCount() is available and used, the code SHALL NOT call count() on loaded relationship collections.

**Validates: Requirements 15.4**

