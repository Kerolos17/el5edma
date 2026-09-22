# خطة توازن التطبيق وتطوير UI UX بالموبايل أولًا

> **الحالة:** In progress
> **تاريخ الإنشاء:** 2026-07-22
> **آخر تحديث:** 2026-08-25
> **المرحلة الحالية:** مسار ترقية التصميم - المرحلة 0 (baseline and evidence)، مع بقاء البنود المعمارية غير الحاجبة متتبعة في المرحلة 2
> **المالك:** Kerol + Codex
> _دورة الحياة: عند بدء التنفيذ نعيد تسمية الملف إلى `[In Progress]`، ونحدّث سجل التقدم قبل الانتقال بين المراحل، ثم نعيد تسميته إلى `[Done]` وننقله إلى `Archive/` عند الاكتمال._

## السياق

> **Spec التنفيذي المعتمد:** `specs/001-complete-design-upgrade/` — يحتوي المتطلبات، خطة التنفيذ، baseline، عقد التصميم، ومهام vertical slices. هذه الـroadmap تتتبع الحالة فقط ولا تكرر تفاصيل الـSpec.

اتفقنا أن مجلد `app` هو الأساس الذي سنبني عليه كل شيء. خريطة Graphify الحالية أظهرت أن مراكز الثقل في النظام حول:

- `User`
- `Beneficiary`
- `ServiceGroup`
- `Visit`
- `MedicalFile`
- `AuditLog`
- `Reports`
- `Notification`
- `WebAppScope`

النظام له سطحان رئيسيان:

- Web App: للقادة والإدارة، لإدارة مجموعات الخدمة، المستخدمين، المخدومين، التقارير، الزيارات، الإشعارات، والمتابعة التشغيلية.
- Servant App: للخدام في الميدان، والموبايل هو الجهاز الأساسي لهم أثناء الزيارات والتنقل والعمل أحيانًا بدون اتصال مستقر.

هذه الخطة تجمع بين ثلاثة أهداف:

- تثبيت النظام داخليًا حتى يكون آمنًا وجاهزًا للاستخدام الحقيقي.
- تطوير واجهة وتجربة الاستخدام بالكامل بشكل احترافي، responsive، وmobile-first.
- تخفيف الضغط عن العقد المركزية في الكود بنقل القواعد المتكررة إلى Services وPolicies وScopes ومكونات UI مشتركة.

## قراءة التصميم

نقرأ المنتج كالتالي: نظام عربي أولًا لإدارة خدمة كنسية، يخدم خدام وقادة، ويحتاج لغة تصميم هادئة، محترفة، إنسانية، وعملية. الأساس ليس landing page ولا dashboard ثقيل، بل product/admin UI واضح للموبايل والديسكتوب.

مؤشرات التصميم:

- التباين البصري: 4/10. هادئ وواثق، بدون تجريب زائد.
- الحركة: 2/10. حركة فقط للتغذية الراجعة، التحميل، الانتقال، وتوضيح الحالة.
- الكثافة: 6/10 لسطح الإدارة، و4/10 لسطح الخدام على الموبايل.

## قواعد غير قابلة للتفاوض

- الموبايل أولًا في كل مسارات الخدام.
- الديسكتوب فعال وسريع للقادة والإدارة.
- العربية وRTL هما الأساس، والإنجليزية مدعومة بدون كسر التصميم.
- أي عنصر تفاعلي على الموبايل لا يقل عن 48px.
- لا توجد Cards داخل Cards.
- لا توجد حركة للزينة فقط.
- لا توجد أزرار غير مقروءة، أو فورمات مزدحمة، أو أفعال مهمة مخفية.
- كل عملية مهمة لها حالات loading وsuccess وerror وempty حيث يلزم.
- الصلاحيات تعيش في Policies وScopes، وليس داخل الواجهات بشكل عشوائي.
- التقارير والـ dashboards والـ exports تستخدم نفس قواعد الفلترة والصلاحيات.

## المرحلة 0 - تدقيق البداية وحواجز الأمان - نصف يوم إلى يوم

**الهدف:** تثبيت نقطة البداية ومعرفة ما يمكن تغييره بأمان.

- [x] تشغيل `php artisan test` وتسجيل الوضع الحالي.
- [x] تشغيل `npm run build` وتسجيل وضع بناء الواجهة.
- [x] مراجعة `.env.example`، الـ queue، الـ cache، Firebase، PWA، storage link، ومتطلبات قاعدة البيانات.
- [ ] تحديث Graphify بعد أي تغييرات جوهرية داخل `app`.
- [x] إنشاء مصفوفة صلاحيات حسب الدور والمورد.
- [x] إنشاء قائمة بكل الشاشات: Web App، Servant App، Filament، التسجيل، التقارير، وPWA prompts.
- [x] تحديد المسارات التي يجب عدم تغيير route names الخاصة بها.
- [x] اعتماد مقاسات الاختبار: 360، 390، 430، 768، 1024، 1366، 1536.

**معيار الخروج:** نعرف حالة الاختبارات والبناء، المسارات الحرجة، مصفوفة الصلاحيات، وكل الشاشات المطلوب إعادة تصميمها.

## المرحلة 1 - إصلاحات الجاهزية الحرجة - يوم إلى يومين

**الهدف:** إصلاح ما قد يكسر الاستخدام الحقيقي قبل فتح تغييرات UI كبيرة.

- [ ] إصلاح أخطاء body في إشعارات scheduled visit reminders وunvisited alerts.
- [ ] إصلاح scope متغيرات إشعارات أعياد الميلاد.
- [ ] إصلاح اسم route في روابط التسجيل.
- [ ] استبدال `User::all()` في الإشعارات باستعلامات scoped وchunked للمستخدمين النشطين.
- [ ] ضمان أن قراءة وتحديث الإشعارات محصورة بالمستخدم الحالي.
- [x] إضافة access gates ناقصة لـ prayer requests وscheduled visits.
- [ ] إصلاح `VisitPolicy` عند عدم تحميل علاقة `beneficiary`.
- [ ] استبدال فحوصات التقارير اليدوية بـ Policies أو Gates.
- [ ] منع تسجيل query bindings الحساسة.

**معيار الخروج:** أوامر الإشعارات، روابط التسجيل، كتابة الإشعارات، والوصول للتقارير تعمل بدون كسر أو تسريب صلاحيات.

## المرحلة 2 - توازن الدومين وتنظيف المعمارية - يومين إلى أربعة

**الهدف:** تخفيف الضغط عن أكبر God Nodes وجعل السلوك متسقًا.

- [ ] توحيد فحوصات الأدوار عبر `UserRole` وPolicies و`WebAppScope`.
- [ ] استخراج قواعد ربط المخدومين بالخدام ومجموعات الخدمة في خدمة أو query scope مشترك.
- [ ] استخراج خيارات `ServiceGroup` المتكررة إلى مصدر واحد.
- [ ] توحيد سلوك Filament وLivewire في users وbeneficiaries وvisits وscheduled visits وmedical files.
- [ ] جعل Observers مسؤولة عن side effects فقط، وليس قرارات العمل الأساسية.
- [ ] توحيد audit logging لعمليات create وupdate وdelete وassignment وvisit وmedical file وnotification.
- [ ] مراجعة exports والتقارير للتأكد أنها role-aware.
- [ ] إضافة أو تأكيد indexes للجداول كثيرة الاستخدام: audit logs، visits، notifications، وdashboard queries.

**معيار الخروج:** نفس قواعد الدور والملكية تنتج نفس النتائج في Web App وServant App وFilament والتقارير والـ exports.

## المرحلة 3 - تأسيس UI UX بالموبايل أولًا - يومين إلى ثلاثة

**الهدف:** بناء أساس تصميم ثابت قبل إعادة تصميم الشاشات واحدة واحدة.

- [ ] تدقيق `resources/css/design-system.css` و`web-app.css` و`servant.css` لاكتشاف التكرار.
- [x] توحيد tokens للألوان، المسافات، الحواف، الظلال، الخطوط، وحالات العناصر في الأساس المشترك والشريحة الأولى.
- [x] الحفاظ على Warm Home palette مع تقليل الألوان العشوائية في الشريحة الأولى.
- [ ] تعريف مكونات موبايل أساسية: page shell، bottom nav، top header، section header، action row، stacked list، card list، filter chips، empty state، skeleton، toast، bottom sheet.
- [ ] تعريف مكونات ديسكتوب أساسية: sidebar، topbar، page header، filters row، data panel، stats strip، split detail layout.
- [ ] استبدال modals على الموبايل بـ bottom sheets عند المهام القصيرة.
- [ ] ضمان safe-area padding و`100dvh` في العناصر الثابتة.
- [ ] توحيد حالات focus وdisabled وloading وactive وerror.

**معيار الخروج:** أي شاشة جديدة أو معدلة تستخدم نظام تصميم واضح بدل اختراع styles جديدة في كل مكان.

## المرحلة 4 - إعادة تصميم تجربة الخدام على الموبايل - أربعة إلى سبعة أيام

**الهدف:** جعل تطبيق الخدام ممتازًا على الموبايل لأنه أهم سطح ميداني.

- [x] إعادة تصميم dashboard الخادم حول: عمل اليوم، الزيارات القادمة، الحالات العاجلة، والأفعال السريعة.
- [x] إعادة تصميم قائمة المخدومين للبحث السريع، الفلاتر، والدخول السريع للبروفايل.
- [x] إعادة تصميم صفحة تفاصيل المخدوم كمركز مهام: بيانات، آخر زيارات، ملاحظات طبية، طلبات صلاة، وأفعال.
- [x] إعادة تصميم create visit wizard بخطوات أخف، تحقق أوضح، دعم draft/offline، ومراجعة نهائية.
- [ ] إعادة تصميم scheduled visits بتجميع حسب التاريخ، status chips، وإجراءات cancel/complete واضحة.
- [ ] إعادة تصميم prayer requests بإضافة سريعة وسياق المخدوم.
- [ ] إعادة تصميم medical files مع إشارات خصوصية واضحة وأقسام مقروءة.
- [ ] إعادة تصميم notifications bell/page بتجميع غير المقروء وسلوك mark-read واضح.
- [ ] التأكد أن PWA install prompt لا يغطي bottom nav ولا يعطل العمل.
- [ ] اختبار كل شاشات الخدام على 360px و390px.

**معيار الخروج:** الخادم يستطيع تنفيذ الدورة الميدانية كاملة على الموبايل: يفتح dashboard، يجد مخدومًا، يسجل زيارة، يراجع السياق الطبي والروحي، يستقبل إشعارات، ويتعامل مع ضعف الاتصال.

## المرحلة 5 - إعادة تصميم Web App للإدارة والقادة - أربعة إلى سبعة أيام

**الهدف:** جعل سطح الإدارة هادئًا وفعالًا على الديسكتوب، وقابلًا للاستخدام على التابلت والموبايل.

- [ ] إعادة تصميم app shell: sidebar، topbar، drawer، mobile bottom nav، language/theme controls.
- [ ] إعادة تصميم dashboard حسب الدور بدل stats عامة.
- [ ] إعادة تصميم beneficiaries page ببحث وفلاتر وكثافة مناسبة وbulk actions وmobile card fallback.
- [ ] إعادة تصميم beneficiary profile كسجل تشغيلي منظم.
- [ ] إعادة تصميم users page مع UX آمن لتعيين الأدوار ومجموعات الخدمة.
- [ ] إعادة تصميم service groups pages بسياق الأعضاء والمخدومين والزيارات.
- [ ] إعادة تصميم visits وscheduled visits حول المراجعة، الإسناد، الحالة، والحالات الحرجة.
- [ ] إعادة تصميم medical files ببطاقات تحترم الخصوصية والصلاحيات.
- [ ] إعادة تصميم prayer requests بمراحل lifecycle واضحة.
- [ ] إعادة تصميم audit logs للبحث والمراجعة والتحقيق.
- [ ] إعادة تصميم reports page بفلاتر واضحة، حالات export، وتجهيز async export.

**معيار الخروج:** كل صفحة رئيسية في Web App لها responsive behavior واضح، primary actions سليمة، حالات empty/loading/error، وضوابط role-safe.

## المرحلة 6 - مواءمة Filament - يومين إلى أربعة

**الهدف:** ألا يشعر Filament كأنه منتج منفصل أو باب خلفي للصلاحيات.

- [ ] مواءمة theme tokens الخاصة بـ Filament مع design system.
- [ ] إضافة empty states وhelper text وplaceholders وcopyable fields.
- [ ] تسهيل الفورمات الكبيرة عبر tabs أو sections أو wizards عند الحاجة.
- [ ] إصلاح تعارض navigation sort.
- [ ] إضافة searchable selects في القوائم ذات العناصر الكثيرة.
- [ ] إضافة bulk actions آمنة ومفيدة.
- [ ] إضافة `canAccess()` وpolicy checks حيث تنقص.
- [ ] التأكد أن تقارير وexports الخاصة بـ Filament تستخدم نفس قواعد Web App.

**معيار الخروج:** Filament يبقى قويًا، لكنه لا يكسر قواعد الصلاحيات ولا يشعر كأنه خارج النظام.

## المرحلة 7 - موثوقية التقارير والـ Dashboard والـ Exports - يومين إلى أربعة

**الهدف:** جعل الأرقام موثوقة والـ exports جاهزة للإنتاج.

- [ ] مركزية منطق التقارير في `ReportService` أو query objects مخصصة.
- [ ] إضافة caching للـ dashboard widgets بمفاتيح role-aware.
- [ ] نقل PDF وExcel الثقيلة إلى queued jobs عند الحاجة.
- [ ] إضافة حدود تصدير أو async export للبيانات الكبيرة.
- [ ] إضافة حالات تقدم واكتمال للـ export مع إشعار.
- [ ] مقارنة أرقام dashboard مع التقارير لكل دور.
- [ ] إضافة tests للوصول للتقارير وبيانات exports scoped.

**معيار الخروج:** التقارير والـ dashboards والـ exports متفقة، ولا تسبب بطئًا عند حجم بيانات واقعي.

## المرحلة 8 - تقوية الإشعارات وOffline وPWA - يومين إلى أربعة

**الهدف:** جعل الاستخدام الميداني موثوقًا في ظروف حقيقية.

- [ ] التحقق من Firebase config وتسجيل tokens.
- [ ] اختبار إشعارات الزيارات الحرجة، الترحيب، التذكيرات، أعياد الميلاد، والتنبيهات غير المزارة.
- [ ] إضافة retry-safe dispatch حيث يلزم.
- [ ] تحسين رسائل offline queue وحالات التعارض.
- [ ] جعل فشل offline sync واضحًا وقابلًا للإصلاح.
- [ ] التحقق من manifest والأيقونات وservice worker وinstall prompts.
- [ ] اختبار Android Chrome وiOS Safari يدويًا.

**معيار الخروج:** الإشعارات والعمل بدون اتصال واضحان وقابلان للتعافي ولا يربكان الخدام.

## المرحلة 9 - Accessibility وRTL وResponsive QA - يومين إلى ثلاثة

**الهدف:** ضمان أن التصميم يعمل مع الناس الحقيقيين على الأجهزة الحقيقية.

- [ ] اختبار keyboard navigation وfocus states.
- [ ] مراجعة screen reader labels للأزرار الأيقونية والتنقل والفورمات والأفعال الخطرة.
- [ ] التحقق من RTL ومحاذاة الأيقونات.
- [ ] التحقق من الإنجليزية بدون كسر layout.
- [ ] فحص contrast للأزرار والحقول والـ badges والنصوص والحالات disabled.
- [ ] التأكد من tap targets لا تقل عن 48px.
- [ ] التأكد من عدم قص أو تداخل النصوص في الموبايل.
- [ ] التأكد من أن modals وsheets وdrawers وbottom nav لا تتداخل مع safe areas.
- [ ] إضافة reduced-motion support للحركات.

**معيار الخروج:** النظام قابل للاستخدام على كل المقاسات المستهدفة ولا يعتمد على ظروف ديسكتوب مثالية.

## المرحلة 10 - الاختبارات وجاهزية الإصدار - يومين إلى أربعة

**الهدف:** الإطلاق بثقة.

- [ ] إضافة feature tests لمسارات الصلاحيات الحرجة.
- [ ] إضافة Livewire tests لمسارات الخدام على الموبايل.
- [ ] إضافة tests للتقارير والـ exports والإشعارات والتسجيل والوصول للملفات.
- [ ] إضافة visual smoke checks للشاشات الأساسية على الموبايل والديسكتوب.
- [ ] تشغيل `php artisan test`.
- [ ] تشغيل `npm run build`.
- [ ] تشغيل Pint formatting.
- [ ] مراجعة `.env.production` والـ queue worker والـ scheduler والـ cache والـ storage والـ deployment notes.
- [ ] تجهيز release checklist وknown limitations.

**معيار الخروج:** التطبيق يبني بنجاح، الاختبارات تمر أو الأعطال المعلومة موثقة، ومتطلبات النشر واضحة.

## ترتيب الأولوية

1. إصلاحات الجاهزية والصلاحيات الحرجة.
2. تأسيس design system ومكونات الموبايل.
3. إعادة تصميم Servant mobile.
4. إعادة تصميم Web App.
5. مواءمة Filament.
6. تقوية التقارير والـ exports.
7. تقوية الإشعارات وoffline وPWA.
8. Accessibility وrelease QA.

## أول شريحة تنفيذ

نبدأ بشريحة صغيرة لكنها عالية التأثير:

- [x] إنشاء permission matrix حول `UserRole` و`WebAppScope`.
- [ ] إصلاح أخطاء الإشعارات والتسجيل الحرجة.
- [x] تدقيق servant mobile shell وbottom nav وdashboard وcreate visit wizard.
- [x] إنشاء مكونات مشتركة للموبايل: page header، action card، empty state، skeleton، bottom sheet.
- [x] إعادة تصميم servant dashboard كأول دليل بصري على الاتجاه الجديد.

هذه الشريحة تعطينا أمانًا واتجاهًا ونتيجة UI ملموسة بدون فتح كل النظام مرة واحدة.

## قرارات مفتوحة

- [ ] هل mobile bottom nav في Web App يظل 4 مسارات فقط، أم يصبح role-aware مع More drawer؟
- [ ] هل medical files للخادم تكون tab رئيسية، أم داخل beneficiary detail، أم الاثنين؟
- [ ] هل reports export يكون synchronous للبيانات الصغيرة وqueued للبيانات الكبيرة؟
- [ ] هل ندعم dark mode بالكامل الآن، أم نؤجله حتى يثبت light theme؟
- [ ] هل يبقى Filament متاحًا للقادة، أم يصبح لـ super admins فقط بعد اكتمال Web App؟

## سجل التقدم

- 2026-07-22 - إنشاء الخطة بحالة Planned. الخطة مبنية على خريطة Graphify لمجلد `app`، وعلى `DESIGN.md` و`PRODUCT.md` و`IMPROVEMENTS.md`، وعلى بنية Laravel 12 وFilament 4 وLivewire وTailwind 4 وPWA والأسطح الحالية لـ Servant وWeb App.
- 2026-07-22 - بدء التنفيذ. تم نقل الخطة إلى In progress، والبدء بالمرحلة 0 لتسجيل baseline الاختبارات والبناء قبل إصلاحات المرحلة 1.
- 2026-07-22 - تسجيل baseline: اختبار Laravel محجوب لأن PHP المتاح 8.2.12 بينما Composer يتطلب PHP >= 8.4.0. بناء Vite نجح بعد تمرير Node المدمج في PATH. تم إنشاء `app-balance-baseline-2026-07-22.md` و`app-permission-matrix.md` و`app-screen-inventory.md`.
- 2026-07-22 - اكتمال مراجعة بيئة التشغيل الأساسية: `.env.example`، queue، cache، scheduler، Firebase placeholders، وPWA manifest/service worker. يلزم PHP 8.4+ قبل تشغيل اختبارات Laravel.
- 2026-07-22 - اكتمال مخرجات المرحلة 0: baseline، مصفوفة الصلاحيات، قائمة الشاشات، route preservation، ومقاسات الاختبار. بدء المرحلة 1 بإغلاق `canAccess()` على Filament resources الأساسية: Beneficiaries وVisits وMedicalFiles، مع تأكيد syntax لها.
- 2026-08-24 - استئناف التنفيذ الكامل كبوابة جاهزية للإطلاق. تم تثبيت PHP 8.4 للاختبارات المحلية، وإصلاح عوائق المراجعة الأولى: منع منح CSP nonce تلقائيًا لأي script، استعادة النصوص العربية التالفة في الزيارات وPWA، إعادة ربط زر واتساب في تطبيق الخدام، تقوية اختبار RTL، وتحويل CI إلى بناء Vite حقيقي. المرحلة الحالية تظل المرحلة 1 حتى اكتمال full test suite والفحص الأمني والصلاحيات.
- 2026-08-24 - اكتمال بوابة الجاهزية المحلية العميقة: تحديث حزم PHP وJavaScript وفحصها بدون ثغرات معلنة، إصلاح CSP/Livewire ولوحة الإدارة ومعالج الزيارة والترجمات، تقوية الوصول للملفات الخاصة وCI وملف النشر، وإضافة دليل تشغيل production وملف إعداد نموذجي آمن. نجحت الحزمة النهائية: 575 اختبارًا و7,648 assertion، وPint، وبناء Vite، وBlade cache. تم كذلك اختبار رحلة الخادم على الديسكتوب والموبايل وPWA/offline بقاعدة QA معزولة. ما يزال قرار Go للإنتاج مشروطًا بتوفير بيئة الاستضافة الفعلية وتنفيذ backup/restore rehearsal وتشغيل queue/scheduler والمراقبة وpilot محدود؛ وهذه إجراءات تشغيلية خارج النسخة المحلية وليست عيوب كود معلومة.
- 2026-08-25 - اعتمد المالك اتجاه «البيت الدافئ المتزن» وخيار التطوير المرحلي: foundation موحد، ثم رحلة الخادم Mobile-first، ثم Web App، ثم النماذج وFilament وPWA/hardening. تم إنشاء `specs/001-complete-design-upgrade/` وتسجيل baseline أولي: 5,596 سطرًا في ملفات CSS الأساسية، 512 selector تقريبًا عبر Web App/Servant، 419 استخدام لون مباشر، 50 تقريبًا inline style، وتضخم واضح في gradients/shadows/backdrop. بدأت مرحلة evidence قبل أي تعديل بصري جديد.
- 2026-08-25 - اكتملت مرحلة design evidence على قاعدة SQLite معزولة وبمقاسات 390x844 و1440x900. تم توثيق login، رحلة الخادم dashboard → beneficiary → visit wizard، Web App في light/dark، وFilament. أُصلح عيب P0 كان يجعل لوحة إشعارات الخادم ظاهرة دائمًا، ونجح Vite production build. كشف baseline أيضًا أخطاء Alpine متكررة في مجموعات Filament، ticker إشعارات يطغى على الصفحة، وتوطينًا ناقصًا لقيم enums. بدأت مرحلة shared foundation.
- 2026-08-25 - بدأت دفعة shared foundation: semantic surface/text/border/motion tokens، تقليل radius/elevation، تحويل stat/empty/heading/avatar المشتركة من inline gradients إلى classes موحدة، واعتماد light كافتراضي في Web App وFilament مع حفظ اختيار المستخدم. أُعيد تركيب لوحة الخادم بهدوء بصري وتعريب أنواع الزيارات. تم كذلك إصلاح مصدر انهيار Filament: تهيئة `collapsedGroups` تحت CSP nonce واستبدال مكوّن إشعارات الخادم بمكوّن Filament الصحيح. التحقق الحالي: 14 preservation/design tests (103 assertions)، 5 design/admin tests (25 assertions)، Blade cache، وVite production build ناجحة، مع اختفاء أخطاء sidebar والتكدس البصري بعد reload.
- 2026-08-25 - اعتُمدت أول شريحة تصميم بعد مراجعة Impeccable مستقلة: shell الخادم، dashboard، قائمة/تفاصيل المخدوم، ومعالج تسجيل الزيارة. أُغلقت ملاحظات التركيز بالكامل (دخول، حبس، Escape، واسترجاع)، ورفعت أهداف اللمس إلى 48px، وأضيف هيكل h1/h2، وحُولت تفاصيل المخدوم إلى semantic tokens متوافقة مع الثيم. نجحت مراجعة 360px و390px بلا overflow، و17 اختبارًا مع 125 assertion، وBlade cache، وVite production build، و`git diff --check`. تظل الشاشات الثانوية للخادم وWeb App وFilament/PWA مراحل لاحقة وليست محسوبة كمكتملة.
