# Implementation Plan: Complete Product Design Upgrade

**Branch**: `current-worktree` | **Date**: 2026-08-25 | **Spec**: [spec.md](spec.md)

## Summary

توحيد النظام البصري ثم إعادة بناء الرحلات حسب الأولوية: Servant mobile أولًا، Web App desktop ثانيًا، ثم النماذج وFilament وPWA/hardening. التنفيذ يحافظ على Laravel/Livewire routes والصلاحيات، ويستخدم vertical slices قابلة للاختبار بدل إعادة كتابة شاملة دفعة واحدة.

## Technical Context

**Language/Version**: PHP 8.4، JavaScript ES modules، Blade  
**Primary Dependencies**: Laravel 12، Livewire 3، Filament 4، Tailwind CSS 4، Alpine.js، Vite 8  
**Storage**: قواعد البيانات الحالية دون migrations جديدة؛ SQLite معزولة للـQA  
**Testing**: PHPUnit 11، Pint، Vite build، Playwright CLI، Impeccable detector/reviewer  
**Target Platform**: Responsive Web + installable PWA؛ Android Chrome وiOS Safari  
**Project Type**: Laravel monolith مع واجهات Livewire وFilament  
**Performance Goals**: لا console errors؛ لا layout shift مادي؛ الحفاظ على query counts؛ عدم تضخيم build بلا تبرير  
**Constraints**: Arabic-first RTL، English LTR، WCAG AAA target، 48px touch targets، offline-aware، صلاحيات role-scoped  
**Scale/Scope**: 34 Livewire views، 15 shared Blade components، أربعة أدوار، ثلاثة أسطح رئيسية

## Constitution Check

ملف `.specify/memory/constitution.md` ما زال template، ولا توجد ملفات `AGENTS.md` أو `STANDARDS.md` أو `memory.md` أو `tasks.md` أو `queue.md` في جذر هذا المشروع يمكن اشتقاق دستور معتمد منها. لذلك تعمل المبادرة مؤقتًا وفق المصادر المعتمدة القائمة:

- طلب المالك واتجاهه الموافق عليه في 2026-08-25.
- `PRODUCT.md` للمنتج والجمهور وإمكانية الوصول.
- `DESIGN.md` لهوية «البيت الدافئ» والـanti-patterns.
- `docs/superpowers/plans/app-permission-matrix.md` لحماية scope الأدوار.
- بوابات CI والاختبارات الحالية كشرط عدم رجوع.

**Gate result**: PASS for planning and Phase 0 audit. Seeding the formal constitution remains a documented repository setup task and must not invent principles without an approved source.

## Project Structure

```text
resources/css/
├── design-system.css
├── servant.css
├── web-app.css
└── filament/admin/theme.css

resources/views/
├── components/{ui,servant,web-app}/
├── livewire/servant/
├── livewire/web-app/
├── servant/layouts/
├── web-app/layouts/
└── filament/

resources/js/
├── servant.js
├── web-app.js
└── registration-form.js

tests/
├── Feature/
└── Unit/

specs/001-complete-design-upgrade/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
├── contracts/design-contract.md
└── tasks.md
```

**Structure Decision**: نطوّر البنية القائمة دون frontend منفصل أو framework جديد. `design-system.css` هو مصدر tokens، والمكونات المشتركة تعيش في `resources/views/components`، وتظل ملفات كل surface مسؤولة عن composition فقط.

## Delivery Phases

1. Baseline and evidence.
2. Shared design foundation and shells.
3. Servant mobile vertical journey.
4. Web App leader journey.
5. Forms, auth, registration, and sensitive states.
6. Filament alignment.
7. PWA, accessibility, i18n, and performance hardening.
8. Bounded visual review, regression gates, staging pilot.

## Complexity Tracking

| Decision | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Two surface accents inside one design system | الخادم والإدارة لهما سياقان مختلفان لكن يجب أن يبدوا منتجًا واحدًا | لون واحد حرفي يجعل أحد السطحين إما ميدانيًا أكثر من اللازم أو إداريًا باردًا |
| Incremental vertical slices | worktree كبير ومستخدم فعليًا، والرحلات والصلاحيات يجب أن تظل قابلة للاختبار | إعادة كتابة CSS والشاشات دفعة واحدة تجعل العيوب غير قابلة للعزل |

