# laravel-websockets setup (local / self-hosted)

This guide walks through installing and configuring `beyondcode/laravel-websockets` with the existing broadcast wiring in this repo.

Goals

- Use the `pusher` broadcast driver with a local WebSocket server (laravel-websockets).
- Ensure private `user.{id}` channels authenticate and the frontend `Echo` client connects.

Commands to run locally

1. Install the package and required frontend libs:

```bash
composer require beyondcode/laravel-websockets
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
php artisan migrate

# Frontend
npm install
npm install --save laravel-echo pusher-js
npm run build
```

2. .env changes (example)

```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1

# laravel-websockets expects these when using pusher driver
WEBSOCKETS_PORT=6001
WEBSOCKETS_HOST=127.0.0.1
```

3. Edit `config/broadcasting.php` `connections.pusher` options to match websockets (example):

```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'useTLS' => false,
        'host' => env('WEBSOCKETS_HOST', '127.0.0.1'),
        'port' => env('WEBSOCKETS_PORT', 6001),
        'scheme' => env('WEBSOCKETS_SCHEME', 'http'),
        'encrypted' => false,
    ],
],
```

4. Start the websockets server (development):

```bash
php artisan websockets:serve
```

5. Start queue worker and websockets in separate terminals (if using queued notifications):

```bash
# terminal 1
php artisan queue:work --sleep=3 --tries=3

# terminal 2
php artisan websockets:serve
```

6. Frontend Echo bootstrap (example)

If you don't already initialize Echo, add this to `resources/js/bootstrap.js` or at the top of `resources/js/app.js`:

```js
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY || process.env.MIX_PUSHER_APP_KEY,
    wsHost: import.meta.env.VITE_WEBSOCKETS_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_WEBSOCKETS_PORT || 6001,
    forceTLS: false,
    disableStats: true,
    enabledTransports: ["ws", "wss"],
});
```

7. Confirm broadcast works

- Open browser console, reload, and ensure `Echo` connected.
- Trigger a notification (artisan tinker or via existing `InternalNotificationService`) and verify the Livewire bell updates immediately.

Notes

- Private channel auth uses `routes/channels.php` (already added) which authorizes `user.{id}` channels.
- If deploying to production, secure websockets (TLS) and proper Pusher or server hosting is required.

---

# Using Hosted Pusher instead (quick setup)

If you prefer a hosted Pusher service instead of running `laravel-websockets`, follow these steps:

1. Create a Pusher account and an app. Copy `app_id`, `key`, `secret`, and `cluster`.

2. Add to your `.env` (example):

```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=your_cluster

# For Vite frontend
VITE_PUSHER_APP_KEY=your_key
VITE_PUSHER_APP_CLUSTER=your_cluster
VITE_PUSHER_FORCE_TLS=true
```

3. Ensure `config/broadcasting.php` has the `pusher` driver configured (the default Laravel scaffolding works for hosted Pusher). For Vite, rebuild assets:

```bash
npm run build
```

4. Verify in browser DevTools that `window.Echo` is connected and then trigger notifications — hosted Pusher will deliver broadcasts to subscribed clients.
