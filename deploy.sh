#!/usr/bin/env bash

# Production deployment helper for shared hosting or a single VPS.
# Run from the project root after uploading/pulling the latest code.

set -euo pipefail

if ! command -v php >/dev/null 2>&1; then
    echo "PHP is not available."
    exit 1
fi

if ! command -v composer >/dev/null 2>&1; then
    echo "Composer is not available."
    exit 1
fi

echo "Starting Ministry System deployment..."

if [ ! -f ".env" ]; then
    echo ".env is missing. Copy .env.example to .env and fill production values first."
    exit 1
fi

if command -v node >/dev/null 2>&1 && command -v npm >/dev/null 2>&1; then
    echo "Installing and building frontend assets..."
    npm ci
    npm run build
else
    echo "Node/npm not available. Make sure public/build was generated before upload."
fi

echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

if grep -q '^APP_KEY=$' .env || grep -q '^APP_KEY=""$' .env; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

echo "Putting application in maintenance mode..."
php artisan down --render="errors::503" || true

echo "Preparing storage and cache directories..."
chmod -R 775 storage bootstrap/cache || true
chmod -R 755 public || true

echo "Running database migrations..."
php artisan migrate --force

echo "Creating storage symlink..."
php artisan storage:link --force

echo "Clearing stale caches..."
php artisan optimize:clear

echo "Building production caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache

if php artisan list --raw | grep -q '^permission:cache-reset$'; then
    php artisan permission:cache-reset
fi

echo "Bringing application online..."
php artisan up

echo "Deployment finished."
echo "Required server jobs:"
echo "  * Cron: * * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1"
echo "  * Queue worker: php artisan queue:work --tries=3 --timeout=90"
echo "  * Web root must point to /public"
echo "  * Production .env must set APP_ENV=production and APP_DEBUG=false"
