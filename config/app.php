<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS / Proxy Behavior
    |--------------------------------------------------------------------------
    |
    | These must be read through config (not env()) at runtime: once the
    | configuration is cached, env() returns null outside of config files.
    |
    | force_https:    null = follow APP_ENV (production forces https),
    |                 true/false overrides.
    | trusted_proxies: comma-separated IPs/CIDRs, or "*"; null = trust none.
    */

    'force_https' => env('FORCE_HTTPS'),

    'trusted_proxies' => env('TRUSTED_PROXIES'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Scheduler commands use the application timezone. The ministry operates
    | in Egypt, so production defaults to Africa/Cairo while remaining
    | configurable from the environment.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Africa/Cairo'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    */

    'locale' => env('APP_LOCALE', 'ar'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'ar'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    'available_locales' => ['ar', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', '')),
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store'  => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Ping Token
    |--------------------------------------------------------------------------
    |
    | Shared secret for the POST /maintenance/ping endpoint that lets an
    | external trigger (GitHub Actions) run schedule:run and drain the queue
    | on hosts where crontab is unavailable. Leave empty to disable the
    | endpoint entirely.
    |
    */

    'maintenance_token' => env('MAINTENANCE_TOKEN'),

];
