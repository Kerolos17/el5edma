# Ministry System — Comprehensive Improvement Report

> Generated: 2026-04-17 | Stack: Laravel 12 + Filament 4 + PHP 8.4

---

## PHASE 1: Performance

### CRITICAL

| # | المشكلة | الملف | السطر | الحل |
|---|---------|-------|-------|------|
| P1 | `User::all()` بدون حدود — full table scan وإنشاء صف notification لكل مستخدم | `app/Services/InternalNotificationService.php` | 20 | استخدم `User::where('is_active', true)->select('id','fcm_token','locale')->chunkById(200, ...)` |

### HIGH

| # | المشكلة | الملف | السطر | الحل |
|---|---------|-------|-------|------|
| P2 | N+1: `UserResource` بيعرض `serviceGroup.name` بدون eager loading | `app/Filament/Resources/Users/UserResource.php` | 55-65 | أضف `->with(['serviceGroup'])` في `getEloquentQuery()` |
| P3 | `VisitsChartWidget` بيشغل aggregate queries ثقيلة بدون caching | `app/Filament/Widgets/VisitsChartWidget.php` | 23-43 | اضيف `Cache::remember("dashboard:chart:{$user->id}:{$period}", 300, ...)` |
| P4 | `NotificationsBellWidget::loadNotifications` بيعمل query منفصل للـ count | `app/Filament/Widgets/NotificationsBellWidget.php` | 26-44 | استخدم `Cache::remember('notifications_unread_'.Auth::id(), 60, ...)` |
| P5 | `audit_logs` مفيهاش index على `user_id` و`created_at` — الجدول بيكبر بكل عملية | `database/migrations/2026_03_15_*_create_audit_logs_table.php` | 14, 24 | أضف migration: `$table->index('user_id'); $table->index('created_at');` |
| P6 | PDF generation synchronous في الـ HTTP request — ممكن يستغرق 30 ثانية | `app/Services/ReportService.php` | كل الملف | حول لـ queued job يخزن النتيجة في Storage ويبعت notification |
| P7 | `BeneficiariesExport` مفيهاش حد أقصى للصفوف — ممكن يصدر كل البيانات | `app/Exports/BeneficiariesExport.php` | 20-31 | أضف `->limit(2000)` أو استخدم `Excel::queue(...)` |

### MEDIUM

| # | المشكلة | الملف | السطر | الحل |
|---|---------|-------|-------|------|
| P8 | `whereJsonContains` على `audit_logs.new_values` — full table scan بدون index | `app/Models/ServiceGroup.php` | 70-76 | أضف column `service_group_id` في `audit_logs` أو counter على `service_groups` |
| P9 | `whereHas` بدل join في VisitResource, VisitsChartWidget, StatsOverviewWidget | متعدد | متعدد | استبدل بـ `->join('beneficiaries', ...)` مع الـ index الموجود |
| P10 | `CacheService` مش بتبطل الـ per-user scoped cache keys عند التعديل | `app/Services/CacheService.php` | 120-131 | استخدم Redis cache tags أو أضف explicit key invalidation |

### LOW

| # | المشكلة | الملف | السطر | الحل |
|---|---------|-------|-------|------|
| P11 | `CACHE_STORE=file` غير مناسب في production مع concurrent users | `.env.example` | — | غير لـ `CACHE_STORE=redis` في production (predis موجود) |
| P12 | `BirthdayWidget` بيشتغل بدون caching على كل dashboard render | `app/Filament/Widgets/BirthdayWidget.php` | 38-68 | `Cache::remember("birthdays:{$user->id}", 3600, ...)` |

---

## PHASE 2: Code Quality & Security

### CRITICAL — يجب إصلاحها قبل أي deployment

| # | المشكلة | الملف | السطر | الحل |
|---|---------|-------|-------|------|
| C1 | **BUG**: `$body` = نفس قيمة `$title` — كل push notifications بتوصل بنص غلط | `app/Console/Commands/SendScheduledVisitReminders.php` | 82 | غير `$body = __('notifications.visit_reminder_body')` |
| C2 | **BUG**: نفس المشكلة في UnvisitedAlerts | `app/Console/Commands/SendUnvisitedAlerts.php` | 117 | غير `$body = __('notifications.unvisited_alert_body')` |
| C3 | **BUG**: `$title` و`$body` خارج scope الـ foreach — ممكن تبعت notifications فاضية | `app/Console/Commands/SendBirthdayReminders.php` | 133-135 | حدد `$title` و`$body` قبل الـ loop بـ default locale |

### HIGH

| # | المشكلة | الملف | السطر | الحل |
|---|---------|-------|-------|------|
| C4 | `VisitPolicy::view()` يعمل null dereference على `$visit->beneficiary` إذا مش loaded | `app/Policies/VisitPolicy.php` | 34, 58 | أضف `$visit->loadMissing('beneficiary')` + null check |
| C5 | `ReportController` بيعمل manual role checks بدل ما يستخدم الـ Policies | `app/Http/Controllers/ReportController.php` | 44-65 | استبدل بـ `Gate::authorize('view', $beneficiary)` |
| C6 | `PrayerRequestResource` مفيهاش `canAccess()` — أي authenticated user يقدر يدخله | `app/Filament/Resources/PrayerRequests/PrayerRequestResource.php` | — | أضف `canAccess()` يتفوق على `Auth::user()->can('viewAny', PrayerRequest::class)` |
| C7 | `ScheduledVisitResource` مفيهاش `canAccess()` — نفس المشكلة | `app/Filament/Resources/ScheduledVisits/ScheduledVisitResource.php` | — | أضف `canAccess()` |
| C8 | `notifyUser()` بيحط PHP array مباشرة في `data` field بدل JSON | `app/Services/InternalNotificationService.php` | 93 | غير لـ `'data' => json_encode($data)` |
| C9 | Route name غلط `register.show` بدل `registration.show` — `RouteNotFoundException` | `app/Services/RegistrationLinkService.php` | 84 | غير لـ `route('registration.show', ...)` |
| C10 | Registration validation rules مكررة inline بدل `FormRequest` | `app/Http/Controllers/RegistrationController.php` | 71-92, 159-183 | استخرج `StoreRegistrationRequest` |

### MEDIUM

| # | المشكلة | الملف | السطر | الحل |
|---|---------|-------|-------|------|
| C11 | `UserForm` بيعرض كل الـ roles لكل المستخدمين — FamilyLeader يقدر يختار SuperAdmin | `app/Filament/Resources/Users/Schemas/UserForm.php` | 63-71 | فلتر الـ options حسب role المستخدم الحالي |
| C12 | `QueryMonitoringService` بيـlog الـ bindings كاملة — ممكن يكشف PII | `app/Services/QueryMonitoringService.php` | 31-36 | استبدل `'bindings' => $query->bindings` بـ `count($query->bindings)` |
| C13 | `RegistrationService::notifyLeaders()` بتبلع exceptions في production | `app/Services/RegistrationService.php` | 130-140 | أرسل للـ error tracker (Sentry) بدل الـ silent catch |
| C14 | `AppServiceProvider` بيعمل `Auth::check()` في `boot()` على كل request | `app/Providers/AppServiceProvider.php` | 44-49 | انقل locale setup لـ SetLocale middleware |
| C15 | MySQL-specific SQL في `VisitsChartWidget` — بيكسر SQLite tests | `app/Filament/Widgets/VisitsChartWidget.php` | 96, 140 | استخدم driver detection مثل `SendBirthdayReminders` |
| C16 | `BeneficiaryForm.php` أكبر من الـ guideline (370 سطر) | `app/Filament/Resources/Beneficiaries/Schemas/BeneficiaryForm.php` | — | قسم كل Tab لـ class منفصل |

### LOW

| # | المشكلة | الملف | الحل |
|---|---------|-------|------|
| C17 | `VisitPolicy` بيسمح للـ Servant يشوف visits زملاءه في نفس الـ group | `app/Policies/VisitPolicy.php` | قرر إذا كان هذا مقصود وثقه |
| C18 | FQCNs بدل `use` imports في RegistrationController | `app/Http/Controllers/RegistrationController.php` | أضف `use App\Models\ServiceGroup;` |

---

## PHASE 3: UX & User Friendliness

### Quick Wins — أقل من ساعة لكل منها

| # | التحسين | الملف | التفاصيل |
|---|---------|-------|---------|
| U1 | أضف `.helperText()` للحقول الغامضة | BeneficiaryForm, VisitForm, UserForm | مثال: `->helperText('رقم هاتف مصري (01012345678)')` على حقل الهاتف |
| U2 | أضف empty state لكل الجداول | كل Resource Tables | `->emptyStateHeading(__('beneficiaries.empty'))` |
| U3 | أضف toast notification لكل عمليات الحفظ | BeneficiaryForm, VisitForm | `Notification::make()->success()->title(__('common.saved'))->send()` |
| U4 | `.copyable()` على حقول الهاتف | BeneficiariesTable | مثل ما هو على personal_code |
| U5 | أضف placeholder examples على حقول الكود | LoginForm, UserForm | `->placeholder('01012345678')` |
| U6 | إصلاح navigationSort conflict — Users وReports عندهم sort = 3 | UserResource, ReportResource | غير قيمة واحدة |

### Medium Effort — يوم لكل منها

| # | التحسين | الملف | التفاصيل |
|---|---------|-------|---------|
| U7 | `.searchable()` على كل الـ dropdowns اللي فيها أكتر من 10 عناصر | BeneficiaryForm, VisitForm | disability_type, assigned_servant |
| U8 | Bulk action: "تعيين خادم" للمستفيدين | BeneficiariesTable | `BulkAction::make('assign_servant')` |
| U9 | Bulk action: "تحديد كمزور" للزيارات المجدولة | ScheduledVisitsTable | `BulkAction::make('mark_visited')` |
| U10 | أضف column summaries على الجداول الرئيسية | BeneficiariesTable, VisitsTable | `Summarizers\Count::make()` |
| U11 | أضف "الزيارات الأخيرة" widget على صفحة المستفيد | ViewBeneficiary | RelationManager أو Infolist |
| U12 | أضف `resources/lang/ar/form_hints.php` لـ reusable helper text | resources/lang/ar/ | مركزة كل نصوص المساعدة |

### Large Improvements — أكتر من يوم

| # | التحسين | التفاصيل | الأولوية |
|---|---------|---------|---------|
| U13 | حول BeneficiaryForm لـ Wizard على الموبايل | 4 خطوات: بيانات أساسية، عنوان، بيانات طبية، تعيين | عالية |
| U14 | Dashboard مخصص لكل role | SuperAdmin: الموافقات المعلقة / ServiceLeader: إحصائيات الخدمة | متوسطة |
| U15 | Calendar view للزيارات المجدولة | عرض تقويمي مع drag-drop | متوسطة |
| U16 | صفحة تفصيلية للمستفيد | كل المعلومات + تاريخ الزيارات + الملف الطبي في مكان واحد | عالية |
| U17 | إضافة رابط من الجداول للـ Reports | زر "تصدير" داخل كل Resource Table | منخفضة |

---

## ملخص الأولويات

### أنجز الأسبوع القادم (Critical Bugs)

```
C1 — إصلاح FCM body في SendScheduledVisitReminders (سطر 82)
C2 — إصلاح FCM body في SendUnvisitedAlerts (سطر 117)
C3 — إصلاح undefined variable في SendBirthdayReminders (سطر 133)
C9 — إصلاح route name في RegistrationLinkService (سطر 84)
P1 — تحديد User::all() في InternalNotificationService (سطر 20)
```

### أنجز الشهر القادم (High Priority)

```
Performance:  P2, P3, P4, P5, P6, P7
Code Quality: C4, C5, C6, C7, C8, C10
UX:           U1, U2, U3, U4, U5, U6
```

### Backlog (Medium/Low)

```
Performance:  P8, P9, P10, P11, P12
Code Quality: C11, C12, C13, C14, C15, C16
UX:           U7–U17
```

---

## الإجراء الفوري المقترح

```bash
# 1. إصلاح الـ 3 critical bugs في الـ commands
# 2. إصلاح route name
# 3. تقييد User::all()
# 4. بعدين: add indexes, caching, canAccess()
```

> هذا التقرير لا يتضمن تغييرات في الكود — هو discovery فقط.
> رتب الـ issues في Backlog وابدأ بالـ Critical.
