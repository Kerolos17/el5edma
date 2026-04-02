# Tech Stack

## Backend
- **PHP 8.2+** / **Laravel 12**
- **Filament 4.0** — admin panel framework (all UI is Filament-based)
- **Spatie Laravel Permission 7.2** — roles & permissions
- **Kreait Firebase (kreait/laravel-firebase)** — FCM push notifications
- **Maatwebsite Excel 3.1** — Excel exports
- **mPDF 8.3** — PDF report generation
- **Predis 3.4** — Redis client

## Frontend
- **Livewire** — real-time components (notifications bell, stats widget)
- **Tailwind CSS 4.2** — via `@tailwindcss/vite` plugin
- **Vite 7** — asset bundling
- **Firebase JS SDK 12** — client-side FCM token registration
- **Axios** — HTTP client

## Database & Infrastructure
- **SQLite** — local development default
- **MySQL/PostgreSQL** — production
- **Database queue driver** — default for jobs
- **Redis** — available for cache/queue (optional)
- **File cache** — local default

## Key Config
- Default locale: `ar` (Arabic), fallback: `en`
- Queue: `database` driver, failed jobs in `failed_jobs` table
- Sessions: `database` driver
- Storage: `local` disk, public symlink for uploads

## Common Commands

```bash
# First-time setup
composer setup

# Development (starts PHP server + queue worker + Vite + log tail)
composer dev

# Run tests
composer test

# Individual artisan commands
php artisan reminders:birthdays          # Send birthday reminders (3 days ahead)
php artisan reminders:unvisited          # Alert for beneficiaries unvisited 14+ days
php artisan reminders:scheduled-visits  # Scheduled visit reminders
php artisan notifications:cleanup        # Prune old notifications
php artisan notifications:stats          # Display notification statistics
php artisan test:push-notification       # Test FCM integration

# Frontend
npm run build   # Production build
npm run dev     # Vite dev server
```

## Testing
- **PHPUnit 11** — test runner
- Tests live in `tests/Unit/` and `tests/Feature/`
- Heavy use of **property-based testing** (many `*PropertyTest.php` files)
- Run with `composer test` or `php artisan test`
