# تكافؤ Filament وWeb App

> **Status:** In progress
> **Created:** 2026-09-27
> **Last updated:** 2026-09-27
> **Current phase:** Phase 1 — inventory and parity contracts
> **Owner:** Kerolos + Codex
> _Lifecycle: see `docs/plans/README.md`. Update this file before every phase transition._

## Context

لوحة Filament هي المرجع الوظيفي الأكثر استقرارًا حاليًا، بينما `/app` هي الواجهة الرسمية لكل الأدوار. الهدف هو أن تقدم `/app` نفس قواعد العمل والبيانات والصلاحيات والإجراءات والنتائج، مع تجربة responsive مناسبة للموبايل، ومن دون نسخ منطق الدومين داخل كل واجهة.

## Success measures

- نفس المستخدم يرى نفس نطاق البيانات في Filament و`/app` والتقارير.
- نفس الإدخال ينتج نفس validation والحفظ والـ audit والـ side effects.
- كل إجراء موجود في المرجع وله قيمة للمستخدم متاح في `/app` حسب الصلاحية.
- أرقام Dashboard والتقارير متطابقة عند نفس الدور والفترة.
- لا تعيش قواعد العمل داخل Filament pages أو Livewire traits؛ كلاهما يستدعي طبقة مشتركة.
- كل شريحة لها parity tests، ثم CI، merge، ونشر واختبار حي.

## Functional parity matrix

| Surface | Filament reference | `/app` target | Main parity work |
| --- | --- | --- | --- |
| Dashboard | period stats, visits/calls chart, critical, birthdays, unvisited | role dashboard | shared queries, period filter, trends, identical critical definition |
| Beneficiaries | rich form, 9 filters, view/edit/delete, WhatsApp, PDF | list/profile/modal | field parity, filters, actions, scoped delete and exports |
| Visits | type/status filters, critical resolve, CRUD | list/profile/modal | shared write service, resolve flow, exact validation and audit |
| Scheduled visits | CRUD, status filter, assignments | list/modal/record flow | shared assignment rules, lifecycle actions, reminders |
| Prayer requests | CRUD, answered/closed actions | list/modal | lifecycle parity, status actions, dates and audit |
| Medical files | create/view/delete/download | list/modal/download | shared validation, privacy scope, file lifecycle |
| Users | CRUD, approve servant, generate code, role filters | list/modal | activation/code actions, role/group invariants, safe deletes |
| Service groups | CRUD and counts | list/profile/modal | leader assignment rules, counts, delete guards |
| Notifications | type filters, mark read/all | page and device controls | recipient scope, read parity, push status |
| Audit logs | filters and record details | list | filter/detail parity and safe redaction |
| Reports | PDF/Excel exports | reports page | shared report queries, exact role scope, export states |

## Phase 1 — Inventory and parity contracts · 1–2 days

**Goal:** turn the comparison into executable contracts before broad UI changes.

- [x] Inventory all Filament resources, pages, widgets, forms, tables, filters, and actions.
- [x] Inventory all `/app` routes, Livewire pages, traits, modals, and profile pages.
- [x] Map the nine shared resources and the dashboard/report surfaces.
- [ ] Record field, filter, action, state, permission, audit, notification, and export gaps per resource.
- [ ] Add role-level parity tests for the shared scopes.
- [ ] Define shared application-service boundaries and forbid new duplicated write rules.

**Exit criteria:** every user-visible Filament behavior is marked as matched, intentionally different, or missing, and the first parity tests run in CI.

## Phase 2 — Shared Dashboard and reporting queries · 1–2 days

**Goal:** make both dashboards and exports calculate from the same role-aware source.

- [ ] Create a shared dashboard query/service layer.
- [ ] Centralize period bounds: week, month, year, and previous period.
- [ ] Centralize beneficiary and visit scopes for every role.
- [ ] Align home visits, calls, unresolved critical cases, birthdays, and unvisited lists.
- [ ] Add period selection and trends to `/app`.
- [ ] Make Filament widgets consume the shared service.
- [ ] Add cross-surface count and chart parity tests.

**Exit criteria:** the same actor and period produce identical numbers and chart data in both dashboards.

## Phase 3 — Beneficiaries and service groups · 2–3 days

**Goal:** establish shared write patterns on the two central domain resources.

- [ ] Extract beneficiary create/update validation and assignment rules.
- [ ] Match important fields and dependent options in the `/app` form.
- [ ] Add Filament-equivalent filters, WhatsApp actions, PDF action, and delete guards.
- [ ] Extract service-group create/update and leader assignment rules.
- [ ] Align group counts, profiles, and record visibility.
- [ ] Add create/update/delete/scope parity tests for all roles.

**Exit criteria:** beneficiary and service-group workflows have identical stored results and authorization outcomes.

## Phase 4 — Visits and scheduled visits · 2–3 days

**Goal:** unify the ministry’s main operational workflow.

- [ ] Extract visit create/update and critical-resolution services.
- [ ] Align visit types, statuses, required fields, and critical transitions.
- [ ] Extract scheduled-visit assignment and lifecycle services.
- [ ] Align create/edit/cancel/complete behavior and reminder eligibility.
- [ ] Preserve immediate push alerts and deep links.
- [ ] Add lifecycle, notification, audit, and parity tests.

**Exit criteria:** a visit operation behaves identically from Filament and `/app`, including notifications and audit records.

## Phase 5 — Prayer requests and medical files · 1–2 days

**Goal:** align sensitive pastoral and medical workflows.

- [ ] Extract prayer request create/update/status transitions.
- [ ] Add answered and closed actions to `/app` with matching authorization.
- [ ] Extract medical-file validation, storage, download, and deletion rules.
- [ ] Match privacy scope and safe file cleanup.
- [ ] Add status, access, upload, download, and deletion parity tests.

**Exit criteria:** lifecycle and privacy rules are identical on both surfaces.

## Phase 6 — Users, permissions, and audit · 2–3 days

**Goal:** make account administration safe and consistent.

- [ ] Extract user create/update, approval, and personal-code generation services.
- [ ] Match role/service-group validation and delete guards.
- [ ] Align filters and profile details.
- [ ] Centralize audit event creation and sensitive-value redaction.
- [ ] Match audit filters and detail view in `/app`.
- [ ] Add a complete role/action permission matrix test suite.

**Exit criteria:** no UI can bypass the same user-management or audit rules.

## Phase 7 — Notifications, reports, and exports · 2–3 days

**Goal:** finish parity for system-wide outputs and background behavior.

- [ ] Centralize notification creation, recipient resolution, push dispatch policy, and deduplication.
- [ ] Align notification filters and read actions.
- [ ] Centralize report queries and reuse them for screen/PDF/Excel.
- [ ] Match report availability to policies and scopes.
- [ ] Add export content and notification delivery tests.

**Exit criteria:** notifications and exported data reflect the same scoped records shown in both UIs.

## Phase 8 — UI completion and release verification · 2–3 days

**Goal:** ship the functional parity work as a coherent product.

- [ ] Standardize list filters, forms, errors, loading, empty states, confirmations, and success feedback.
- [ ] Verify RTL/English and 360, 390, 768, 1024, and 1366 widths.
- [ ] Run complete PHP, JS, MySQL migration, Pint, and production build checks.
- [ ] Run role-based smoke tests on production.
- [ ] Update operational docs and the parent UI/UX roadmap.

**Exit criteria:** all parity checks pass, production smoke tests pass, and remaining intentional differences are documented.

## Delivery rules

- One vertical slice per PR; no large unreviewable rewrite.
- Every slice follows: baseline test → shared service/query → adapt Filament → adapt `/app` → parity tests → CI → merge → deploy → live smoke test.
- Existing routes and mobile navigation remain stable unless a tested migration requires a change.
- Database changes must be additive and reversible where production data permits.
- `setup-maintenance-remote.sh` is an unrelated local file and remains untouched.

## Open decisions

- Whether service leaders should retain direct Filament access after full `/app` parity.
- Whether destructive bulk actions should be exposed in `/app` or remain admin-only.
- Whether large exports move to queued delivery after measuring production volume.

## Progress log

- 2026-09-27 — Plan created and moved directly to In progress after owner approval. Resource/page inventory completed; duplicate domain logic between Filament and Livewire identified as the main architectural cause of drift. Dashboard selected as the first shared-service slice.
