<?php
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
            'sql'        => $query->sql,
            'bindings'   => $query->bindings,
            'time'       => $query->time . 'ms',
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
