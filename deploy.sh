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

# These checks inspect the release .env before any deployment command. Never
# generate or replace APP_KEY here: it protects encrypted production data.
if [ "${MINISTRY_BACKUP_CONFIRMED:-}" != "yes" ]; then
    echo "Refusing deployment: verify the database, storage, and .env backups, then set MINISTRY_BACKUP_CONFIRMED=yes."
    exit 1
fi

if ! grep -qx 'APP_ENV=production' .env; then
    echo "Refusing deployment: .env must contain APP_ENV=production."
    exit 1
fi

if ! grep -qx 'APP_DEBUG=false' .env; then
    echo "Refusing deployment: .env must contain APP_DEBUG=false."
    exit 1
fi

app_key_line="$(grep '^APP_KEY=' .env | tail -n 1 || true)"
if [ -z "$app_key_line" ] || [ "$app_key_line" = 'APP_KEY=""' ] || [ "$app_key_line" = "APP_KEY=''" ]; then
    echo "Refusing deployment: .env must contain the existing, non-empty APP_KEY."
    exit 1
fi
if ! printf '%s\n' "$app_key_line" | grep -Eq '^APP_KEY=[^[:space:]]+$'; then
    echo "Refusing deployment: .env must contain the existing, non-empty APP_KEY."
    exit 1
fi

if command -v node >/dev/null 2>&1 && command -v npm >/dev/null 2>&1; then
    echo "Installing and building frontend assets..."
    npm ci
    npm run build
else
    echo "Node/npm not available. Verifying prebuilt frontend assets..."
    if [ ! -s "public/build/manifest.json" ]; then
        echo "Refusing deployment: public/build/manifest.json is missing. Build assets before upload."
        exit 1
    fi
fi

echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Putting application in maintenance mode..."
php artisan down --render="errors::503" || true

echo "Preparing storage and cache directories..."
chmod -R 775 storage bootstrap/cache || true
chmod -R 755 public || true

echo "Running database migrations..."
php artisan migrate --force

# Never seed production automatically. DatabaseSeeder includes demo accounts
# with known credentials as well as sample ministry data. First-admin
# provisioning must be completed through a separately reviewed secure process.
echo "Verifying an active super administrator exists..."
if ! php artisan tinker --execute='echo \App\Models\User::where("role", "super_admin")->where("is_active", true)->exists() ? "ACTIVE_SUPER_ADMIN_PRESENT" : "MISSING_ACTIVE_SUPER_ADMIN";' | grep -qx 'ACTIVE_SUPER_ADMIN_PRESENT'; then
    echo "Refusing to continue: no active super_admin was confirmed. The application remains in maintenance mode."
    exit 1
fi

echo "Creating storage symlink..."
# NOTE: hosts without symlink()/exec() (e.g. restricted shared hosting) cannot
# create the link. In that case keep a real directory synced via cron:
#   */5 * * * * cp -rn <app>/storage/app/public/. <app>/public/storage/
if ! php artisan storage:link --force; then
    echo "Symlink unsupported on this host. Falling back to copied storage..."
    mkdir -p public/storage
    cp -rn storage/app/public/. public/storage/
fi

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

# Tell any persistent workers to finish their current job and boot the new code.
# This is safe even when the hosting provider starts workers separately.
echo "Restarting queue workers..."
php artisan queue:restart || true

echo "Bringing application online..."
php artisan up

echo "Deployment finished."
echo ""
echo "=== Required Server Jobs ==="
echo ""
echo "1. Cron Scheduler (run every minute):"
echo "   * * * * * cd /home/username/el5edma && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "2. Queue Worker (run in background):"
echo "   nohup php artisan queue:work --sleep=3 --tries=3 --max-time=3600 >> /dev/null 2>&1 &"
echo ""
echo "3. Firebase credentials file:"
echo "   Ensure storage/app/firebase-credentials.json exists on the server"
echo ""
echo "4. Production .env must set:"
echo "   APP_ENV=production"
echo "   APP_DEBUG=false"
echo "   SESSION_ENCRYPT=true"
echo "   SESSION_SECURE_COOKIE=true"
echo ""
echo "5. Web root must point to /public"
