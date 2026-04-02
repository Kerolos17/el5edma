# Implementation Plan: Servant Self-Registration

## Overview

تنفيذ نظام التسجيل الذاتي للخدام الذي يسمح للخدام الجدد بالتسجيل في النظام من خلال رابط عام فريد لكل مجموعة خدمة. يتضمن التنفيذ: توليد روابط التسجيل، نموذج تسجيل عام، إنشاء حسابات تلقائياً، إشعارات للقادة، وحماية أمنية شاملة.

## Tasks

- [ ] 1. إعداد البنية التحتية للقاعدة والنماذج
  - [x] 1.1 إنشاء migration لإضافة حقول registration_token إلى جدول service_groups
    - إضافة حقل `registration_token` (string, 64 chars, unique, nullable)
    - إضافة حقل `registration_token_generated_at` (timestamp, nullable)
    - إضافة index على `registration_token` للبحث السريع
    - _Requirements: 1.1, 10.1, 10.2_
  
  - [x] 1.2 إنشاء migration لإضافة action type جديد إلى جدول audit_logs
    - تعديل enum للـ action column لإضافة 'servant_self_registered'
    - _Requirements: 8.1, 8.5_
  
  - [x] 1.3 تحديث ServiceGroup model لإضافة الحقول والعلاقات الجديدة
    - إضافة `registration_token` و `registration_token_generated_at` إلى fillable
    - إضافة cast لـ `registration_token_generated_at` كـ datetime
    - إضافة method `hasActiveRegistrationToken()`
    - إضافة method `getSelfRegisteredServantsCount()`
    - _Requirements: 1.1, 1.5, 7.5_
  
  - [x] 1.4 تحديث User model لإضافة methods التسجيل الذاتي
    - إضافة static method `generateUniquePersonalCode()`
    - إضافة static method `createFromSelfRegistration()`
    - _Requirements: 4.1, 4.7_
  
  - [x] 1.5 تحديث AuditLog model لإضافة method تسجيل التسجيل الذاتي
    - إضافة static method `logSelfRegistration()`
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 2. إنشاء Services لمنطق الأعمال
  - [x] 2.1 إنشاء RegistrationLinkService
    - تنفيذ method `getOrCreateToken()` لتوليد أو استرجاع token
    - تنفيذ method `regenerateToken()` لإعادة توليد token
    - تنفيذ method `validateToken()` للتحقق من صحة token
    - تنفيذ method `generateRegistrationUrl()` لتوليد الرابط الكامل
    - استخدام `Str::random(64)` لتوليد tokens آمنة
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 1.6, 2.4, 10.1, 10.2, 10.3_
  
  - [x] 2.2 إنشاء RegistrationService
    - تنفيذ method `register()` لمعالجة التسجيل وإنشاء الحساب
    - تنفيذ method `checkDuplicates()` للتحقق من عدم التكرار
    - تنفيذ method `notifyLeaders()` لإرسال الإشعارات
    - تنفيذ method `logRegistration()` لتسجيل العملية في audit log
    - استخدام database transactions لضمان atomicity
    - _Requirements: 3.1-3.7, 4.1-4.7, 5.1-5.5, 8.1-8.5, 9.1-9.3_
  
  - [x] 2.3 تسجيل Services في AppServiceProvider
    - تسجيل RegistrationLinkService كـ singleton
    - تسجيل RegistrationService كـ singleton
    - _Requirements: جميع المتطلبات_

- [ ] 3. إنشاء Controller والـ Routes
  - [x] 3.1 إنشاء RegistrationController
    - تنفيذ method `show()` لعرض نموذج التسجيل
    - تنفيذ method `store()` لمعالجة طلب التسجيل
    - إضافة validation rules لجميع الحقول
    - إضافة error handling شامل
    - _Requirements: 2.1-2.6, 3.1-3.7, 4.1-4.7, 6.1-6.2_
  
  - [x] 3.2 إضافة routes للتسجيل العام
    - إضافة GET route `/register/{token}` لعرض النموذج
    - إضافة POST route `/register/{token}` لمعالجة التسجيل
    - تطبيق middleware `guest` على الـ routes
    - تطبيق middleware `throttle:5,60` على POST route
    - _Requirements: 2.1, 3.1, 10.4, 10.5_

- [ ] 4. إنشاء Views للتسجيل العام
  - [x] 4.1 إنشاء Blade template لنموذج التسجيل
    - إنشاء `resources/views/registration/form.blade.php`
    - عرض اسم مجموعة الخدمة
    - إضافة حقول: name, email, phone, password, password_confirmation
    - إضافة CSRF token
    - إضافة رابط لصفحة تسجيل الدخول
    - تصميم responsive باستخدام Tailwind CSS
    - _Requirements: 2.1, 2.2, 2.3, 2.5, 2.6, 10.6_
  
  - [x] 4.2 إنشاء Blade template لـ modal رابط التسجيل في Filament
    - إنشاء `resources/views/filament/modals/registration-link.blade.php`
    - عرض الرابط الكامل في input field
    - إضافة زر نسخ الرابط مع JavaScript
    - عرض عدد الخدام المسجلين
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 5. Checkpoint - التحقق من البنية الأساسية
  - تشغيل migrations والتأكد من نجاحها
  - التأكد من عدم وجود أخطاء syntax في Services والـ Controller
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. تكامل Filament لإدارة روابط التسجيل
  - [x] 6.1 إضافة actions لإدارة روابط التسجيل في ServiceGroupResource
    - إضافة action `registration_link` لعرض الرابط في modal
    - إضافة action `regenerate_token` لإعادة توليد token
    - تطبيق authorization باستخدام policy
    - إضافة notifications للنجاح/الفشل
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.6_
  
  - [x] 6.2 تحديث ServiceGroupPolicy لإضافة authorization
    - إضافة method `manageRegistrationLink()`
    - السماح لـ super_admin و service_leader بالوصول لجميع المجموعات
    - السماح لـ family_leader بالوصول لمجموعته فقط
    - منع servant من الوصول
    - _Requirements: 7.6_

- [x] 7. إضافة ملفات الترجمة
  - [x] 7.1 إنشاء ملف الترجمة العربي للتسجيل
    - إنشاء `resources/lang/ar/registration.php`
    - إضافة جميع النصوص: العناوين، الحقول، الأخطاء، الرسائل
    - _Requirements: 2.6, 3.7, 6.1, 9.3, 9.4_
  
  - [x] 7.2 إنشاء ملف الترجمة الإنجليزي للتسجيل
    - إنشاء `resources/lang/en/registration.php`
    - ترجمة جميع النصوص من العربي
    - _Requirements: 2.6_
  
  - [x] 7.3 إضافة نصوص إدارة روابط التسجيل في ملفات service_groups
    - إضافة keys في `resources/lang/ar/service_groups.php`
    - إضافة keys في `resources/lang/en/service_groups.php`
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [x] 8. تنفيذ نظام الإشعارات
  - [x] 8.1 تحديث RegistrationService لإنشاء إشعارات للقادة
    - تحديد قادة المجموعة (leader_id و service_leader_id)
    - إنشاء سجلات في جدول ministry_notifications (bulk insert)
    - dispatch SendFcmNotificationJob للقادة الذين لديهم FCM tokens
    - معالجة الأخطاء بشكل graceful (عدم فشل التسجيل إذا فشلت الإشعارات)
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 9. Checkpoint - التحقق من التكامل الكامل
  - اختبار flow التسجيل الكامل يدوياً
  - التحقق من إنشاء الحسابات بشكل صحيح
  - التحقق من إرسال الإشعارات
  - التحقق من تسجيل audit logs
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. كتابة Unit Tests
  - [ ]* 10.1 كتابة tests لـ RegistrationLinkService
    - Test token generation uniqueness
    - Test token reuse for existing service group
    - Test token regeneration invalidates old token
    - Test URL generation includes token
    - _Requirements: 1.1, 1.2, 1.5, 1.6_
  
  - [ ]* 10.2 كتابة tests لـ RegistrationService
    - Test valid registration creates user account
    - Test user has correct role and attributes
    - Test duplicate email/phone rejection
    - Test notification creation
    - Test audit log creation
    - _Requirements: 3.1-3.7, 4.1-4.7, 5.1-5.5, 8.1-8.5, 9.1-9.3_
  
  - [ ]* 10.3 كتابة tests لـ RegistrationController
    - Test show() displays form with valid token
    - Test show() rejects invalid token
    - Test store() creates account with valid data
    - Test store() rejects invalid data
    - Test validation error messages
    - _Requirements: 2.1-2.6, 3.1-3.7_
  
  - [ ]* 10.4 كتابة tests لـ ServiceGroupPolicy
    - Test super_admin can access all groups
    - Test service_leader can access all groups
    - Test family_leader can only access own group
    - Test servant cannot access registration links
    - _Requirements: 7.6_
  
  - [ ]* 10.5 كتابة tests للـ Models
    - Test ServiceGroup::hasActiveRegistrationToken()
    - Test ServiceGroup::getSelfRegisteredServantsCount()
    - Test User::generateUniquePersonalCode()
    - Test User::createFromSelfRegistration()
    - Test AuditLog::logSelfRegistration()
    - _Requirements: 1.5, 4.1-4.7, 7.5, 8.1-8.5_

- [ ] 11. كتابة Property-Based Tests
  - [ ]* 11.1 كتابة property test لـ Token Generation Uniqueness and Security
    - **Property 1: Token Generation Uniqueness and Security**
    - **Validates: Requirements 1.1, 10.1, 10.2**
    - توليد 100 token عشوائي والتحقق من uniqueness وطول 64 حرف
  
  - [ ]* 11.2 كتابة property test لـ Token Reuse Idempotence
    - **Property 3: Token Reuse Idempotence**
    - **Validates: Requirements 1.5**
    - استدعاء getOrCreateToken عدة مرات والتحقق من إرجاع نفس token
  
  - [ ]* 11.3 كتابة property test لـ Token Regeneration Invalidation
    - **Property 4: Token Regeneration Invalidates Previous**
    - **Validates: Requirements 1.6, 7.4**
    - إعادة توليد token والتحقق من فشل validation للـ token القديم
  
  - [ ]* 11.4 كتابة property test لـ Email Uniqueness Validation
    - **Property 7: Email Uniqueness Validation**
    - **Validates: Requirements 3.3, 9.1**
    - محاولة التسجيل بـ 100 email موجود مسبقاً والتحقق من الرفض
  
  - [ ]* 11.5 كتابة property test لـ Phone Uniqueness Validation
    - **Property 9: Phone Uniqueness Validation**
    - **Validates: Requirements 3.5, 9.2**
    - محاولة التسجيل بـ 100 رقم هاتف موجود مسبقاً والتحقق من الرفض
  
  - [ ]* 11.6 كتابة property test لـ Complete Account Creation
    - **Property 12: Complete Account Creation**
    - **Validates: Requirements 4.1-4.6**
    - إنشاء 100 حساب عشوائي والتحقق من جميع الخصائص الصحيحة
  
  - [ ]* 11.7 كتابة property test لـ Personal Code Uniqueness
    - **Property 13: Personal Code Uniqueness**
    - **Validates: Requirements 4.7**
    - إنشاء 100 حساب والتحقق من uniqueness للـ personal_code
  
  - [ ]* 11.8 كتابة property test لـ Leader Notification Creation
    - **Property 14: Leader Notification Creation**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.5**
    - التسجيل 100 مرة والتحقق من إنشاء notifications للقادة
  
  - [ ]* 11.9 كتابة property test لـ Audit Log Creation
    - **Property 20: Comprehensive Audit Log Creation**
    - **Validates: Requirements 8.1-8.5**
    - التسجيل 100 مرة والتحقق من إنشاء audit logs صحيحة
  
  - [ ]* 11.10 كتابة property test لـ Rate Limiting Enforcement
    - **Property 22: Rate Limiting Enforcement**
    - **Validates: Requirements 10.4, 10.5**
    - محاولة 100 تسجيل سريع من نفس IP والتحقق من الحظر بعد 5 محاولات

- [ ] 12. كتابة Integration Tests
  - [ ]* 12.1 كتابة integration test للـ Full Registration Flow
    - Test complete flow: access link → fill form → submit → login
    - _Requirements: جميع المتطلبات_
  
  - [ ]* 12.2 كتابة integration test للـ Notification Flow
    - Test notification creation and FCM dispatch
    - _Requirements: 5.1-5.5_
  
  - [ ]* 12.3 كتابة integration test للـ Error Handling
    - Test various error scenarios and error messages
    - _Requirements: 2.4, 3.7, 9.3, 10.5_

- [x] 13. Final Checkpoint - التحقق النهائي
  - تشغيل جميع الـ tests والتأكد من نجاحها
  - مراجعة الكود للتأكد من اتباع conventions المشروع
  - التحقق من وجود comments عربية في الكود
  - التأكد من استخدام database transactions
  - التأكد من معالجة الأخطاء بشكل صحيح
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties across randomized inputs
- Unit tests validate specific examples and edge cases
- Integration tests validate end-to-end flows
- All code comments should be written in Arabic (matching project conventions)
- Use database transactions for atomic operations
- Follow existing project structure (Services in app/Services/, Controllers in app/Http/Controllers/)
- Use Filament actions pattern for admin panel integration
- Use Laravel validation rules for input validation
- Use Laravel policies for authorization
- Use bulk inserts for notifications to improve performance
- Dispatch FCM jobs to queue to avoid blocking registration response
