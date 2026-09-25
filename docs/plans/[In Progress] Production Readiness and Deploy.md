# Production Readiness & Deploy — Ministry System

> **Status:** In progress
> **Created:** 2026-09-24
> **Last updated:** 2026-09-24
> **Current phase:** Phase 5 — بروفة النشر (بانتظار بيانات cPanel من المالك)
> **Owner:** Kerolos
> _Lifecycle: rename to `[In Progress]` on start, update the Progress log before each
> phase transition, `[Done]` + move to `Archive/` when complete._

## Context

تقرير الجاهزية (2026-09-24) أظهر أن المشروع جاهز للنشر بنسبة عالية لكن تبقّت ثغرات
قبل الإطلاق: 6 اختبارات فاشلة في عقد التصميم، أسرار مكشوفة محلياً، غياب إجبار HTTPS
وTrustProxies، CSP مخفَّف بـ unsafe-eval، صور المخدومين على القرص العام، مخلفات تطوير
ضمن المستودع، وإصدار firebase غير موسوم. الخطة تُنفَّذ بالترتيب لأن كل مرحلة تفتح
البوابة للتي بعدها: إصلاح الكود أولاً حتى يمر الـ commit النهائي على بوابات
`docs/PRODUCTION_RUNBOOK.md`، ثم التأمين، ثم التنظيف، ثم البروفة، ثم النشر الفعلي.

المرجع الإلزامي لكل المراحل: `docs/PRODUCTION_RUNBOOK.md` و`DEPLOYMENT.md`.

---

## Phase 1 — إصلاح عقد التصميم (6 اختبارات)  ·  2–3 أيام

**Goal:** `php artisan test` يمر 658/658 على الـ commit الذي سينشر.

- [x] تشغيل `tests/Unit/DesignSystemContractTest.php` وحصر الفشل الستة بدقة:
  1. `shared semantic tokens are defined` — توكنات الألوان/الخطوط لا تطابق العقد
  2. `shared components do not use inline gradients`
  3. `web app defaults to light without following system theme`
  4. `filament initializes sidebar groups and defaults to light`
  5. `servant primary slice uses the shared surface contract`
  6. `servant secondary surfaces keep the calm accessible contract`
- [x] تحديد التوجيه لكل فشل: تقرير الجاهزية أثبت أن عناصر العقد غير منفَّذة في الكود
      إطلاقاً (لا في CSS ولا في الواجهات) — الكود هو المخالف وفقاً لقرار المالك.
- [x] تطبيق الإصلاحات:
      - `design-system.css`: إضافة 12 توكن دلالي (`--surface-*`, `--text-*`,
        `--border-subtle`, `--focus-ring`, `--motion-normal`, `--ease-out`,
        `--tap-target`) + قيم dark + أصناف `stat-card--*` و`section-header__badge--*`
        و`avatar__*` بدل التدرجات inline.
      - `servant.css`: أصناف `skeleton-card/row/chip`, `search-input__*`,
        `servant-drawer*`, `medical-file-icon`, `profile-card__noise`, `logout-btn`,
        `display-font`, `accent-font`.
      - مكوّنات `ui/stat-card`, `ui/section-header`, `ui/avatar` أعيدت كتابتها
        بالأصناف الدلالية (التدرجات أصبحت أسطحاً مسطّحة هادئة).
      - `web-app/layouts/app.blade.php`: الوضع الفاتح افتراضياً بتجاهل تفضيل النظام.
      - `AdminPanelProvider`: `defaultThemeMode(ThemeMode::Light)` (Filament\Enums\ThemeMode).
      - `notifications-bell-topbar.blade.php`: رندر `NotificationsBellWidget::class`
        بدل `@livewire('notifications-bell')`.
      - واجهات servant: إزالة كل `style=` و`reveal-card` و`card-lift` من dashboard,
        beneficiary-list, visit-list, scheduled-visit-list, prayer-request-list,
        medical-file-list, profile (+h1 sr-only), beneficiary-detail (+hero h1
        وحرفيتي `<h2>`), wizard (+`min-h-12`), header (trapFocus + `w-12 h-12`).
      - `notifications-bell.blade.php`: `role="dialog"`, `inert`, `data-label-mute/unmute`.
      - `notifications.js`: `getFocusableElements`, تركيز/استعادة عبر
        `__notificationReturnFocus`, مزامنة تسمية زمن الكتم عبر `dataset`.
- [x] تشغيل السويت كامل: `php artisan test` → **658 passed (7918 assertions)**.
- [x] `npm run build` ناجح + `pint --test` ناجح (229 ملف).

**Exit criteria:** ✅ متحققة — السويت أخضر + بناء Vite ناجح؛ لم يُعدَّل أي اختبار (العقد كما هو).

## Phase 2 — تدوير الأسرار وإصلاح الـ env  ·  يوم واحد

**Goal:** لا سر حقيقي مكشوف، وبيئة إنتاج تبدأ بمفاتيح جديدة.

- [x] حذف `.env.backup` نهائياً من القرص (2026-09-24).
- [ ] **(مالك)** تدوير بيانات Mailtrap SMTP من لوحة Mailtrap ثم تحديث `.env` المحلي.
- [ ] **(مالك)** تقييد مفتاح Firebase Web (HTTP referrers على النطاق النهائي) من
      Google Cloud Console، ومراجعة VAPID.
- [x] **تحذير حرج موثّق:** لا تُدوَّر `APP_KEY` على قاعدة بيانات حالية — الإنتاج
      يبدأ بمفتاح جديد على قاعدة بيانات جديدة.
- [x] `.env.production.example` يغطي متطلبات الـ runbook (SESSION_ENCRYPT/SECURE already true).

**Exit criteria:** `.env.backup` محذوف ✓؛ تدوير المالك المتبقي خارجي (Mailtrap/Firebase).

## Phase 3 — تعديلات الأمان في الكود  ·  2–3 أيام

**Goal:** إغلاق ثغرات التهيئة المكتشفة في تقرير الجاهزية.

- [x] **TrustProxies + إجبار HTTPS:** `TRUSTED_PROXIES` عبر `bootstrap/app.php`
      (idempotent؛ `*` مدعوم لـ cPanel)، و`FORCE_HTTPS`/إنتاج عبر
      `AppServiceProvider::boot` → `URL::forceScheme('https')`.
      موثّق في `.env.example` و`.env.production.example` (الإنتاج: `FORCE_HTTPS=true`, `TRUSTED_PROXIES=*`).
- [x] **تدقيق `.htaccess`:** `public/.htaccess` يحوّل HTTP→HTTPS (مع دعم
      X-Forwarded-Proto للبروكسي).
- [x] **تشديد CSP:** script-src أصبح `nonce + 'unsafe-inline' + 'self'` — أُزيلت
      `'unsafe-eval'` و`https:` المفتوحة. (`unsafe-inline` عديم التأثير عند وجود
      nonce في متصفحات CSP2+ — يُحتفظ به كـ fallback للقديم.)
- [x] **صور المخدومين للقرص الخاص:** مسار جديد `GET /beneficiary-photos/{beneficiary}`
      (`FileAccessController::showPhoto`) بتخويل policy + throttle + Cache-Control
      private؛ الرفع في `ManagesBeneficiaries` و`BeneficiaryForm` انتقل للقرص
      private؛ العرض (Accessor `photo_url`، Filament Table/Infolist/GlobalSearch)
      يمرّ على المسار المخوّل؛ `ReportService` يقرأ من private. الصور الحالية
      مؤقتة فلا migration بيانات (قرار المالك). الاختبارات المتأثرة حُدّثت
      (BeneficiaryModelTest، ResourceActionsTest، ReportControllerTest) — تحديث
      سلوكي موثّق لا تجاوز عقد.
- [x] السويت كامل بعد التعديلات: **658 passed (7918 assertions)** + pint PASS
      (422 ملف) + composer validate/audit + npm audit نظيفة.

**Exit criteria:** ✅ متحققة (التحقق النهائي على HTTPS الفعلي ضمن Phase 5).

## Phase 4 — تنظيف المستودع والاعتماديات  ·  نصف يوم

**Goal:** حزمة نشر نظيفة وقابلة لإعادة البناء.

- [x] حذف `hot.html` و`plan.html` من المستودع (و`server.php`).
- [x] تثبيت `kreait/firebase-php` على إصدار موسوم في `composer.lock`:
      **8.5.0** dist موسوم بدل zipball (مع google/gax ومتعلقاتها).
- [x] مراجعة `.gitignore` — يغطي `.env*` الحقيقية، `firebase-credentials.json`،
      `.phpunit.result.cache`، `/output/`.
- [x] `composer validate --strict` ✓ + `composer audit --locked` ✓ (لا ثغرات)
      + `npm audit --audit-level=high` ✓ (0 vulnerabilities).

**Exit criteria:** ✅ متحققة.

## Phase 5 — بروفة النشر (Rehearsal)  ·  1–2 يوم

**Goal:** نشر كامل على بيئة مطابقة للإنتاج (staging/VM/حساب cPanel مؤقت) بلا مفاجآت.

- [ ] سحب الـ commit النهائي (أو checkout نظيف) على بيئة البروفة.
- [ ] تنفيذ جميع بوابات `docs/PRODUCTION_RUNBOOK.md` على الـ commit نفسه:
      `composer validate/audit`, `npm ci && npm run build`, `pint --test`,
      `phpunit --fail-on-phpunit-deprecation`, `view:cache`.
- [ ] إعداد MySQL حقيقي + `.env` إنتاجي + `bash deploy.sh`.
- [ ] **نسخ احتياطي واستعادة:** تنفيذ خطوات النسخ في الـ runbook (mysqldump +
      archive لـ storage/app + حفظ APP_KEY مشفّراً) **واستعادة فعلياً** على قاعدة
      أخرى للتحقق منها.
- [ ] تشغيل `php artisan migrate --force` من النسخة الأولية والتحقق من الـ 3
      migrations الجديدة (push_devices، client_uuid، dedupe_key).
- [ ] التحقق الوظيفي على البروفة: تسجيل دخول بالأدوار الأربعة، لوحة الخادم،
      مزامنة offline، رفع/تنزيل ملف طبي، تصدير Excel/PDF، إشعار FCM تجريبي
      (`php artisan` أو أمر الاختبار الموثّق)، كرون `schedule:run` يدوياً،
      queue worker.
- [ ] مراقبة `/up` + سجلات `storage/logs` لمدة يوم على الأقل.

**Exit criteria:** كل البوابات خضراء على بيئة البروفة، والتحقق الوظيفي أعلاه ناجح
ومسجَّل في Progress log.

## Phase 6 — النشر الفعلي والتحقق  ·  نصف يوم + متابعة

**Goal:** إطلاق الإنتاج بلا مفاجآت وبتحقق موثّق.

- [ ] نافذة صيانة + نسخة احتياطية كاملة قبل أي خطوة (مطلوب بالـ runbook).
- [ ] تنفيذ خطوات `DEPLOYMENT.md`/`deploy.sh` على السيرفر الإنتاجي.
- [ ] إنشاء Cron للجدولة والطابور + رفع `firebase-credentials.json` خارج Git.
- [ ] **تغيير كلمات مرور وأكواد حسابات الـ seed فوراً** بعد أول دخول (DEPLOYMENT.md).
- [ ] قائمة تحقق ما بعد النشر: `/up`، دخول الأدوار الأربعة، HTTPS إجباري،
      ترويسات الأمان، FCM token من متصفح حقيقي، بريد SMTP خارج، schedule يعمل.
- [ ] مراجعة سجلات اليوم الأول + خطط التراجع (نسخة الإصدار السابق + dumps).

**Exit criteria:** النظام يعمل إنتاجياً بقائمة تحقق موقّعة في Progress log.

---

## Open decisions

1. **عقد التصميم:** ✅ محسوم (2026-09-24) — الكود هو المخالف، نصلح الكود ليطابق `DESIGN.md` والعقد.
2. **بيئة البروفة (Phase 5):** ✅ محسوم (2026-09-24) — حساب cPanel مؤقت مطابق للإنتاج (وضع المالك، المطابقة أفضل لاكتشاف قيود الاستضافة).
3. **صور المخدومين الحالية:** ✅ محسوم (2026-09-24) — كل الصور الحالية مؤقتة/تجريبية؛ التغيير للقرص الخاص مباشر بلا migration بيانات.

## Progress log

- 2026-09-24 — Plan created (Planned). مبني على تقرير الجاهزية + `docs/PRODUCTION_RUNBOOK.md`.
- 2026-09-24 — Status: In progress، بدء Phase 1. قرارات المالك: إصلاح الكود (لا العقد)، بروفة على cPanel مؤقت، الصور الحالية مؤقتة فلا حاجة لترحيل.
- 2026-09-24 — **Phase 1 ✅**: تنفيذ عقد التصميم في الكود (توكنات + أصناف دلالية + a11y + light default + Filament widget topbar). السويت 658/658، build ✓، pint ✓. لم يُعدَّل أي اختبار عقد.
- 2026-09-24 — **Phase 2 ✅ (جزئي)**: حذف `.env.backup`. تدوير Mailtrap/Firebase إجراءات خارجية متبقية على المالك.
- 2026-09-24 — **Phase 3 ✅**: TRUSTED_PROXIES + FORCE_HTTPS + تحويل HTTPS في htaccess + CSP بلا unsafe-eval/https: + صور المخدومين للقرص الخاص بمسار مخوّل. السويت 658/658 بعد التعديلات.
- 2026-09-24 — **Phase 4 ✅**: حذف hot/plan/server.php، firebase 8.5.0 موسوم، كل بوابات الاعتماديات خضراء. لا commits بعد — بانتظار قرار المالك.
- 2026-09-24 — **متبقٍ:** تدوير أسرار (مالك) → Phase 5 بروفة على cPanel (يحتاج بيانات الدخول) → Phase 6 نشر.
- 2026-09-24 — **Commits مسجّلة (main):** b67917c design contract / 6f2d1c3 security / 5663298 privacy photos / 8ac01f8 deps pin / ad5d5cc dev script fix (artisan serve بدل server.php). الشجرة نظيفة.
