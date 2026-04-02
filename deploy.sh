#!/bin/bash
# =============================================================
# سكريبت النشر على Shared Hosting
# شغّله على السيرفر بعد رفع الملفات:  bash deploy.sh
# =============================================================

set -e

# Skip on CI environments without PHP (e.g. Cloudflare Pages)
if ! command -v php &> /dev/null || ! command -v composer &> /dev/null; then
    echo "⏭️  PHP/Composer not available, skipping deploy script."
    exit 0
fi

echo "🚀 بدء النشر..."

# 1. تثبيت الحزم (بدون dev dependencies)
echo "📦 تثبيت Composer packages..."
composer install --no-dev --optimize-autoloader --no-interaction

# 2. توليد APP_KEY إن لم يكن موجوداً
if grep -q "APP_KEY=$" .env || grep -q 'APP_KEY=""' .env; then
    echo "🔑 توليد APP_KEY..."
    php artisan key:generate --force
fi

# 3. صلاحيات المجلدات
echo "🔐 ضبط الصلاحيات..."
chmod -R 775 storage bootstrap/cache
chmod -R 755 public

# 4. تشغيل الـ migrations
echo "🗄️  تشغيل Migrations..."
php artisan migrate --force

# 5. تشغيل الـ Seeders (roles + admin user فقط)
echo "🌱 تشغيل Seeders..."
php artisan db:seed --force

# 6. إنشاء رابط storage
echo "🔗 إنشاء Storage Link..."
php artisan storage:link --force

# 7. مسح وإعادة بناء الـ Cache
echo "⚡ بناء Cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache

# 8. مسح permission cache
php artisan permission:cache-reset

echo ""
echo "✅ تم النشر بنجاح!"
echo ""
echo "⚠️  تذكر:"
echo "   - تأكد من إعداد Cron Job (راجع DEPLOYMENT.md)"
echo "   - غيّر كلمات مرور المستخدمين الافتراضيين فوراً"
echo "   - تأكد أن APP_DEBUG=false في .env"
