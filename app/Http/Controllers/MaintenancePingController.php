<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MaintenancePingController extends Controller
{
    /**
     * External cron replacement for hosts without crontab: runs the due
     * scheduler commands and drains the queue. Triggered by GitHub Actions
     * every five minutes and authenticated with a shared token.
     */
    public function __invoke()
    {
        $expected = (string) config('app.maintenance_token');

        if ($expected === '' || ! hash_equals($expected, (string) request()->header('X-Maintenance-Token', ''))) {
            // A uniform 404 keeps the endpoint's existence ambiguous.
            abort(404);
        }

        // GitHub's 5-minute cron has jitter; overlapping pings would run the
        // scheduler twice and split the queue work, so hold a cache lock and
        // let a concurrent ping know it was skipped.
        $lock = Cache::lock('maintenance:ping', 120);

        if (! $lock->get()) {
            return response()->json(['skipped' => 'another run holds the lock']);
        }

        try {
            $queue    = $this->artisan('queue:work', ['--stop-when-empty' => true, '--max-time' => 50]);
            $schedule = $this->artisan('schedule:run');

            Log::info('maintenance.ping', ['queue' => $queue, 'schedule' => $schedule]);

            return response()->json(['queue' => $queue, 'schedule' => $schedule]);
        } finally {
            $lock->release();
        }
    }

    private function artisan(string $command, array $parameters = []): string
    {
        Artisan::call($command, $parameters);

        return trim(Artisan::output());
    }
}
