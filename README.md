# Ministry System

Laravel 12 + Livewire 3 ministry management system for Arabic-first church service workflows.

## Interface policy (decided 2026-09-25)

- **`/app/*` is the primary interface for all roles** — daily service work, reports, notifications, profile. It is the official dashboard of the product.
- **`/servant/*` is the mobile-first companion for servants** — field work, quick visit recording, offline sync. Not a general dashboard.
- **`/admin` (Filament) is back-office only** — internal administration and data correction by super admins via direct URL. It is never linked from product navigation and never a login destination.
- After login, users are routed by role (`User::homeRoute()`): servants land on `/servant/dashboard`, everyone else on `/app/dashboard`.

## Main URLs

- `/` redirects to `/app/dashboard`
- `/app/dashboard` main dashboard
- `/app/beneficiaries`
- `/app/visits`
- `/app/scheduled-visits`
- `/app/prayer-requests`
- `/app/medical-files`
- `/app/reports`
- `/app/users`
- `/app/service-groups`
- `/admin` Filament fallback/admin panel

## Roles

- `super_admin`: full system access
- `service_leader`: manages service groups and scoped operational data
- `family_leader`: manages one family/service group scope
- `servant`: daily work interface for beneficiaries, visits, scheduled visits, prayer requests, and medical files

## Local Setup

Requirements:

- PHP 8.4 recommended, PHP 8.2 minimum
- Composer 2
- Node.js 20+ and npm
- SQLite for local development, MySQL/PostgreSQL recommended for production

Commands:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

In this workspace, PHP is available at:

```bash
.\.tools\php-8.4.20\php.exe artisan test
```

## Test Gates

Run these before deployment:

```bash
php artisan test
npm run build
php artisan route:cache
php artisan route:clear
```

For focused Web App checks:

```bash
php artisan test tests/Feature/WebApp/AppShellTest.php tests/Feature/WebApp/ResourceActionsTest.php tests/Feature/WebApp/ViewStructureTest.php
```

## Production Checklist

Set production environment values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=file
```

Then run:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Server requirements:

- Web root must point to `public/`
- HTTPS is required for PWA install and push notifications
- Cron must run Laravel scheduler every minute:

```bash
* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
```

- A queue worker must be running:

```bash
php artisan queue:work --tries=3 --timeout=90
```

## PWA Status

Current:

- Web App install prompt
- Service worker registration
- Offline shell
- Online/offline banner
- Firebase push notification plumbing

Remaining for full PWA parity:

- Offline read cache for core `/app` screens
- Offline queue for safe daily actions in the Web App
- Conflict handling UI for queued sync

## Important Deployment Note

The migration `2026_04_30_120000_create_scheduled_visit_servants_table.php` is required for multi-servant scheduled visits. If production already has data, run migrations before opening `/app/scheduled-visits`.
