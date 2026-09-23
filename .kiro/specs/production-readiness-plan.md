# خطة الجاهزية الكاملة للنشر — نظام الخدمة

> تاريخ الإنشاء: سبتمبر 2026
> الهدف: تحويل المشروع من حالته الحالية إلى نظام جاهز بالكامل للنشر والاستخدام الفعلي

---

## المرحلة 1 — إصلاح الأخطاء الحرجة (Critical Bugs)
**الأولوية: فورية — قبل أي شيء آخر**
**التقدير: 2-3 ساعات**

### 1.1 أخطاء في الإشعارات (Commands)

| # | الملف | المشكلة | الإصلاح |
|---|-------|---------|---------|
| B1 | `SendScheduledVisitReminders.php` سطر 82 | `$body` مش محدد بشكل صحيح قبل الـ dispatch — الإشعار بيوصل بنص فارغ | تعريف `$body` بشكل منفصل قبل الـ loop |
| B2 | `SendUnvisitedAlerts.php` سطر 117 | `$body` خارج scope — نفس المشكلة | تعريف `$body` قبل الـ loop |
| B3 | `SendBirthdayReminders.php` سطر 133 | `$title` و `$body` خارج scope الـ foreach | نقل التعريف خارج الـ loop |

### 1.2 خطأ في الـ Data Layer

| # | الملف | المشكلة | الإصلاح |
|---|-------|---------|---------|
| B4 | `InternalNotificationService.php::notifyUser()` | يضع PHP array في `data` field بدون `json_encode` — يسبب type error في MySQL | تغيير لـ `json_encode($data)` |

---

## المرحلة 2 — إصلاح مشاكل الأمان والـ Authorization
**الأولوية: عالية جداً — قبل النشر**
**التقدير: 1 يوم**

### 2.1 ثغرات Authorization

| # | الملف | المشكلة | الإصلاح |
|---|-------|---------|---------|
| S1 | `PrayerRequestResource.php` | مفيش `canAccess()` — أي مستخدم authenticated يدخل | إضافة `canAccess()` يفوض لـ Policy |
| S2 | `ScheduledVisitResource.php` | نفس مشكلة S1 | إضافة `canAccess()` |
| S3 | `ReportController.php` | يعمل manual role checks بدل Policies | استبدال بـ `Gate::authorize()` |
| S4 | `UserForm.php` | FamilyLeader يقدر يختار role = super_admin من الـ dropdown | فلترة الـ options حسب role المستخدم الحالي |
| S5 | `VisitPolicy::view()` | null dereference على `$visit->beneficiary` إذا مش loaded | إضافة `loadMissing('beneficiary')` + null check |

### 2.2 تسريب معلومات في الـ Logs

| # | الملف | المشكلة | الإصلاح |
|---|-------|---------|---------|
| S6 | `QueryMonitoringService.php` | يـlog الـ bindings كاملة — يكشف PII في logs | استبدال بـ `count($query->bindings)` فقط |

---

## المرحلة 3 — إصلاح مشاكل الأداء
**الأولوية: عالية — حيوية في production**
**التقدير: 2-3 أيام**

### 3.1 أداء قاعدة البيانات

| # | الملف | المشكلة | الإصلاح |
|---|-------|---------|---------|
| P1 | `InternalNotificationService.php` | `User::all()` بدون حدود — full table scan مع كل إشعار | `User::where('is_active', true)->select(['id','locale'])->chunkById(200, ...)` |
| P2 | `UserResource.php` | N+1: يعرض `serviceGroup.name` بدون eager loading | إضافة `->with(['serviceGroup'])` في `getEloquentQuery()` |
| P3 | `ServiceGroup::getSelfRegisteredServantsCount()` | `whereJsonContains` على `audit_logs.new_values` — full table scan | إضافة عمود `service_group_id` في `audit_logs` أو counter مستقل |

### 3.2 الـ Caching

| # | الملف | المشكلة | الإصلاح |
|---|-------|---------|---------|
| P4 | `VisitsChartWidget.php` | Aggregate queries ثقيلة بدون caching على كل render | `Cache::remember("dashboard:chart:{$period}", 300, ...)` |
| P5 | `BirthdayWidget.php` | Query بدون caching على كل dashboard render | `Cache::remember("birthdays", 3600, ...)` |
| P6 | `NotificationsBellWidget.php` | Query منفصل للـ count مع كل render | تطبيق caching مثل `NotificationsBell` Livewire |

### 3.3 العمليات الثقيلة

| # | الملف | المشكلة | الإصلاح |
|---|-------|---------|---------|
| P7 | `ReportService.php` | PDF generation synchronous في HTTP request — يستغرق 30+ ثانية | تحويل لـ queued job مع storage + notification |
| P8 | `BeneficiariesExport.php` | مفيش حد أقصى للصفوف — يصدر كل البيانات دفعة واحدة | `->limit(5000)` أو `Excel::queue(...)` |

---

## المرحلة 4 — تكملة الـ UI/UX
**الأولوية: متوسطة-عالية**
**التقدير: 3-4 أيام**

### 4.1 Quick Wins (ساعة أو أقل لكل منها)

| # | التحسين | الملفات |
|---|---------|--------|
| U1 | إضافة `navigationSort` صحيح — UserResource وReportResource عندهم sort=3 | `UserResource.php`, تأكيد ترتيب كل الـ Resources |
| U2 | إضافة `.emptyStateHeading()` و `.emptyStateDescription()` لكل الجداول | كل `*Table.php` |
| U3 | إضافة `.helperText()` للحقول الغامضة (الهاتف، WhatsApp، الكود) | `BeneficiaryForm.php`, `UserForm.php` |
| U4 | إضافة `.copyable()` على حقول الهاتف والكود في جداول القوائم | `BeneficiariesTable.php`, `UsersTable.php` |
| U5 | إضافة placeholder examples واضحة | كل الـ Forms |

### 4.2 تحسينات التصفح والفلترة

| # | التحسين | الملفات |
|---|---------|--------|
| U6 | إضافة `.searchable()` على كل الـ Select fields الكبيرة | `BeneficiaryForm.php`, `VisitForm.php` |
| U7 | Bulk actions: "تعيين خادم" للمستفيدين | `BeneficiariesTable.php` |
| U8 | Bulk actions: "تحديد كمزور" للزيارات المجدولة | `ScheduledVisitsTable.php` |
| U9 | Column summaries (إجمالي العدد) | `BeneficiariesTable.php`, `VisitsTable.php` |

### 4.3 تحسينات الواجهة الكاملة

| # | التحسين | الوصف | التقدير |
|---|---------|-------|---------|
| U10 | صفحة تفصيلية للمستفيد | دمج كل المعلومات + تاريخ الزيارات + الملف الطبي في مكان واحد جميل | يوم |
| U11 | Dashboard مخصص لكل role | super_admin: موافقات معلقة، service_leader: إحصائيات الخدمة | يومان |
| U12 | Calendar view للزيارات المجدولة | عرض تقويمي | يومان |
| U13 | BeneficiaryForm كـ Wizard على الموبايل | 4 خطوات: أساسية، عنوان، طبية، تعيين | يوم |

---

## المرحلة 5 — ميزات ناقصة للاكتمال
**الأولوية: عالية — بعض الميزات المذكورة في المتطلبات غير مكتملة**
**التقدير: 3-5 أيام**

### 5.1 نظام الموافقة على الخدام الجدد
المتطلب الأصلي: الخادم المسجل ذاتياً يكون `is_active = false` — يحتاج **واجهة موافقة** لم تُبنَ بعد

| المهمة | الوصف |
|--------|-------|
| 5.1.1 | Widget في Dashboard يعرض الخدام المعلقين الموافقة |
| 5.1.2 | Action في `UserResource` لـ "موافقة" و"رفض" الخدام الجدد |
| 5.1.3 | إشعار للخادم عند الموافقة أو الرفض |
| 5.1.4 | تنظيف الحسابات المرفوضة (scheduled cleanup) |

### 5.2 إدارة الحالات الحرجة
الـ widget موجود لكن **لا يوجد escalation flow كامل**

| المهمة | الوصف |
|--------|-------|
| 5.2.1 | صفحة مخصصة للحالات الحرجة مع timeline |
| 5.2.2 | Notification تلقائي للـ service_leader عند مرور X ساعة بدون حل |
| 5.2.3 | تقرير الحالات الحرجة (PDF) |

### 5.3 تقرير "حالة الأسرة الخدمية" الشامل
مذكور في `ReportController` لكن غير كامل

| المهمة | الوصف |
|--------|-------|
| 5.3.1 | تقرير شامل بكل مخدومي الأسرة مع آخر زيارة |
| 5.3.2 | مؤشرات أداء الخادم (عدد الزيارات، الحالات الحرجة) |

### 5.4 نظام الـ Offline للخادم
`offline-queue.js` موجود لكن يحتاج **backend endpoint** للـ sync

| المهمة | الوصف |
|--------|-------|
| 5.4.1 | Controller لاستقبال الزيارات المحفوظة offline |
| 5.4.2 | Conflict resolution عند وجود تعارضات |
| 5.4.3 | UI feedback للخادم (عدد الزيارات المحفوظة offline) |

---

## المرحلة 6 — إعداد بيئة الإنتاج (Production Setup)
**الأولوية: حيوية للنشر**
**التقدير: 1-2 أيام**

### 6.1 ملف .env.production
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=ministry_system
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

CACHE_STORE=redis          # بدل file
QUEUE_CONNECTION=database
REDIS_HOST=...
REDIS_PASSWORD=...

BROADCAST_CONNECTION=pusher  # لو WebSockets مطلوبة
PUSHER_APP_ID=...
# أو: BROADCAST_CONNECTION=log لو real-time مش مطلوب

FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
FIREBASE_PROJECT_ID=...
VITE_FIREBASE_API_KEY=...
# باقي متغيرات Firebase

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_FROM_ADDRESS=noreply@your-domain.com

LOG_CHANNEL=daily
LOG_LEVEL=error
```

### 6.2 الـ Server Requirements
- PHP 8.2+ مع extensions: `sodium`, `curl`, `json`, `mbstring`, `xml`, `zip`, `gd`
- MySQL 8.0+ أو PostgreSQL 14+
- Redis 6+ (للـ cache والـ sessions)
- Queue worker (Supervisor أو systemd)
- Nginx/Apache مع HTTPS (SSL certificate)
- Cron job لـ Laravel scheduler

### 6.3 أوامر النشر
```bash
# 1. رفع الكود
git pull origin main

# 2. تثبيت التبعيات
composer install --optimize-autoloader --no-dev

# 3. بناء الـ assets
npm ci && npm run build

# 4. إعداد قاعدة البيانات
php artisan migrate --force

# 5. تحسين الأداء
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize

# 6. إعداد التخزين
php artisan storage:link

# 7. إعادة تشغيل Queue
php artisan queue:restart
```

### 6.4 إعداد Supervisor لـ Queue Worker
```ini
[program:ministry-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

### 6.5 Cron Job
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## المرحلة 7 — الاختبارات والجودة
**الأولوية: لازمة قبل النشر**
**التقدير: 2 أيام**

### 7.1 اختبارات ناقصة (من specs)
Tasks 10-12 في `servant-self-registration` غير مكتملة

| الملف المقترح | ما يختبره |
|--------------|---------|
| `RegistrationLinkServicePropertyTest.php` | Token uniqueness، reuse idempotence، expiry |
| `RegistrationServicePropertyTest.php` | Complete account creation، duplicate rejection |
| `RegistrationIntegrationTest.php` | Full flow: access → fill → submit → login |

### 7.2 اختبارات مطلوبة للميزات الجديدة
- اختبار واجهة الموافقة على الخدام
- اختبار نظام الـ offline sync
- اختبار escalation الحالات الحرجة

### 7.3 اختبار الأداء
```bash
# تشغيل كل الـ tests
composer test

# فحص الـ code style
./vendor/bin/pint --test

# فحص N+1 queries
# (موجود في Feature tests تلقائياً)
```

---

## المرحلة 8 — التوثيق والإعداد النهائي
**الأولوية: متوسطة**
**التقدير: 1 يوم**

### 8.1 ملفات التوثيق المطلوبة
- [ ] `DEPLOYMENT.md` — خطوات النشر التفصيلية
- [ ] `RUNBOOK.md` — الإجراءات التشغيلية (backup، restore، monitoring)
- [ ] `USER_GUIDE.md` — دليل المستخدم لكل role

### 8.2 إعداد المشروع للمستخدم الأول
| المهمة | الوصف |
|--------|-------|
| Seeder للـ super_admin | `DatabaseSeeder` يُنشئ حساب admin افتراضي |
| Seeder للبيانات التجريبية | مجموعة خدمية + 5 مخدومين + زيارتين (للـ demo) |
| `php artisan app:create-admin` | أمر مخصص لإنشاء أول admin بسهولة |

---

## ملخص الأولويات والتسلسل المقترح

```
الأسبوع 1: المرحلة 1 + 2 + 6.1-6.3   (Critical fixes + Security + Production env)
الأسبوع 2: المرحلة 3 + 4.1           (Performance + Quick wins)
الأسبوع 3: المرحلة 4.2-4.3 + 5.1-5.2 (UX improvements + Pending features)
الأسبوع 4: المرحلة 5.3-5.4 + 7 + 8   (Remaining features + Tests + Docs)
```

---

## مقياس الجاهزية للنشر

| المعيار | الحالة الحالية | المستهدف |
|---------|--------------|---------|
| Critical Bugs | ❌ 3-4 bugs | ✅ 0 bugs |
| Security Issues | ⚠️ 5 issues | ✅ 0 issues |
| Performance | ⚠️ قابل للتحسين | ✅ محسّن |
| Test Coverage | ✅ جيد | ✅ ممتاز |
| Production Config | ⚠️ ناقص | ✅ مكتمل |
| Documentation | ⚠️ جزئي | ✅ كامل |
| Pending Features | ⚠️ 2 ميزات ناقصة | ✅ مكتملة |

**التقدير الإجمالي: 3-4 أسابيع للجاهزية الكاملة**
