# Tasks: Complete Product Design Upgrade

**Input**: `spec.md`, `plan.md`, `research.md`, and `contracts/design-contract.md`

## Phase 0: Baseline and Evidence

- [x] T001 Record approved direction and scope in `specs/001-complete-design-upgrade/spec.md`.
- [x] T002 Reuse and verify screen inventory in `docs/superpowers/plans/app-screen-inventory.md`.
- [x] T003 Capture CSS/component complexity metrics in `specs/001-complete-design-upgrade/research.md`.
- [x] T004 Capture valid baseline screenshots for login, servant dashboard, beneficiary list/detail, visit wizard, Web App dashboard, and Filament at target widths.
- [x] T005 Map loading, empty, error, success, permission, offline, and overflow states for every P0 screen.
- [x] T006 Record baseline accessibility and console findings without modifying production data.

**Checkpoint**: Evidence matrix is complete and the first slice has a locked surface brief.

## Phase 1: Shared Foundation

- [x] T007 Consolidate tokens in `resources/css/design-system.css` and add tests for required tokens.
- [ ] T008 Extract shared action, field, status, empty, loading, and feedback primitives under `resources/views/components/ui/`.
- [ ] T009 Split monolithic surface CSS into explicit foundation/component/composition layers without changing Vite entry contracts.
- [ ] T010 Remove non-functional decorative motion and complete reduced-motion coverage.
- [ ] T011 Make light theme the default and complete consistent dark-theme tokens.
- [ ] T012 Align auth/registration typography, controls, validation, and feedback with the shared foundation.

**Checkpoint**: Shared primitives render in UI preview and pass build, token, accessibility, RTL/LTR, light/dark checks.

## Phase 2: User Story 1 — Servant Mobile Journey (P1)

- [x] T013 [US1] Redesign servant shell, header, bottom navigation, and persistent visit action.
- [x] T014 [US1] Redesign servant dashboard around today, urgency, and one dominant next action.
- [x] T015 [US1] Redesign beneficiary search/list and beneficiary detail task hub.
- [x] T016 [US1] Redesign visit wizard with compact steps, inline validation, review, success, and recoverable failure.
- [ ] T017 [US1] Align scheduled visits, prayer requests, medical files, notifications, and profile.
- [ ] T018 [US1] Add/adjust focused Livewire and preservation tests for the complete servant journey.
- [ ] T019 [US1] Run bounded mobile/desktop visual review and close reviewer findings.

**Checkpoint**: The complete servant field journey is independently usable and verified at 360px and 390px.

## Phase 3: User Story 2 — Leadership Web App (P2)

- [ ] T020 [US2] Redesign Web App shell, responsive navigation, topbar, and role context.
- [ ] T021 [US2] Redesign role-aware dashboard and attention queue.
- [ ] T022 [US2] Redesign beneficiaries and beneficiary profile.
- [ ] T023 [US2] Redesign visits, schedules, service groups, and users.
- [ ] T024 [US2] Redesign medical, prayer, notification, audit, report, and export states.
- [ ] T025 [US2] Add/adjust role-scoped feature tests and responsive visual checks.
- [ ] T026 [US2] Run bounded desktop/tablet/mobile review and close reviewer findings.

**Checkpoint**: A leader reaches any critical scoped record within two steps and completes core management actions.

## Phase 4: User Stories 3–5 — Cross-Surface Completion

- [ ] T027 [US3] Finish login, registration, profile, language, theme, and session-expiry experiences.
- [ ] T028 [US4] Finish PWA install/update/offline/sync/recovery states.
- [ ] T029 [US4] Complete keyboard, screen-reader, zoom, contrast, reduced-motion, RTL/LTR, and overflow matrix.
- [ ] T030 [US5] Align Filament navigation, forms, tables, empty states, badges, and permission affordances.
- [ ] T031 Optimize CSS/JS delivery and document any justified size increase.
- [ ] T032 Run full PHPUnit, Pint, Vite build, dependency audits, browser console, and Impeccable detector.
- [ ] T033 Run final finish reviewer and document the built system in `DESIGN.md` and surface briefs.

**Checkpoint**: All acceptance criteria are evidenced and the release can enter staging pilot.

## Dependencies and Execution Order

- T004–T006 complete Phase 0 and block foundation edits.
- T007–T012 block screen-by-screen redesign.
- Servant tasks T013–T019 complete before Web App tasks T020–T026.
- T027–T033 close cross-surface gaps after both primary journeys are stable.
- Tests are written or updated before behavior-affecting implementation within each task.
- The roadmap progress log and this checklist are updated before every phase transition.
