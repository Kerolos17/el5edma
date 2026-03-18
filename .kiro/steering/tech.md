# Tech Stack

## Backend

- **PHP 8.2+** with **Laravel 12**
- **Filament 4** — admin panel framework (resources, forms, tables, widgets, pages)
- **Spatie Laravel Permission** — role/permission management
- **Maatwebsite Excel** — Excel exports
- **mPDF** — PDF report generation
- **Predis** — Redis client (used for cache/queue)
- **Laravel Pint** — code style fixer (PSR-12 based)

## Frontend

- **Vite 7** with `laravel-vite-plugin`
- **Tailwind CSS 4** via `@tailwindcss/vite`
- **Livewire** (bundled with Filament)
- Entry points: `resources/css/app.css`, `resources/js/app.js`, `resources/css/filament/admin/theme.css`

## Database

- SQLite (local dev: `database/database.sqlite`)
- Migrations in `database/migrations/`

## Common Commands

```bash
# Initial setup
composer run setup

# Start all dev processes (server + queue + logs + vite)
composer run dev

# Run tests
composer run test

# Build frontend assets
npm run build

# Run frontend dev server
npm run dev

# Code style fix
./vendor/bin/pint

# Artisan shortcuts
php artisan migrate
php artisan migrate:fresh --seed
php artisan tinker
php artisan filament:upgrade
```

## Queue & Scheduled Commands

Artisan commands in `app/Console/Commands/`:

- `SendBirthdayReminders`
- `SendScheduledVisitReminders`
- `SendUnvisitedAlerts`

These are intended to run via the scheduler or queue worker.
