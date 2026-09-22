# Phase 0 Research: Design Baseline

**Captured:** 2026-08-25

## Existing visual authority

- Product mode: `Operate`.
- Durable identity: «البيت الدافئ» في `DESIGN.md`.
- Confirmed evolution: «البيت الدافئ المتزن»؛ preserve and deepen, do not replace.
- Servant: mobile-first, warm teal/gold accent, field workflow.
- Web App: desktop-first, calm blue accent, decision workflow.
- Light is the default physical scene; dark is an explicit user preference.

## Source baseline

| File | Lines | Bytes | Hex occurrences | Gradients | Shadows | Animation uses | Backdrop uses |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| `resources/css/design-system.css` | 162 | 4,884 | 67 | 0 | 0 | 0 | 0 |
| `resources/css/web-app.css` | 3,040 | 67,787 | 143 | 14 | 16 | 8 | 8 |
| `resources/css/servant.css` | 1,781 | 46,660 | 159 | 34 | 28 | 11 | 16 |
| `resources/css/filament/admin/theme.css` | 613 | 18,551 | 50 | 2 | 9 | 0 | 0 |

Additional evidence:

- 34 Livewire view files.
- 15 shared Blade components.
- 376 `.app-*` selector definitions in `web-app.css`.
- 136 `.s-*`, `.dashboard-*`, or `.servant-*` selector definitions in `servant.css`.
- 49 inline-style occurrences in Livewire/shared component views.
- 20 custom scrollbar rule occurrences despite the incumbent design guidance preferring native affordances.
- 4 reduced-motion rule occurrences across the CSS tree; coverage must be mapped to every animated component.

## Material findings

1. The design system exists, but component files still contain many direct colors, shadows, gradients, and radii; tokens are not the actual authority yet.
2. Web App has become a large monolithic stylesheet and risks selector drift and repeated component variants.
3. Servant UI carries more decorative gradients, blur, organic shapes, and animation than the approved calm Operate mode warrants.
4. Dark mode can follow system preference on first load, conflicting with the approved light-default scene.
5. Shared patterns exist twice (`ui` and `web-app`) and must be reconciled by behavior, not renamed mechanically.
6. Filament, Web App, registration, and Servant use related but visibly different component grammars.
7. The correct first vertical slice remains servant dashboard → beneficiary → visit wizard because it exercises navigation, cards/lists, forms, sensitive context, feedback, and mobile constraints.
8. The Servant notification backdrop and panel lacked their closed-state `display: none` rules. They rendered as permanent fixed overlays even without `.is-open`, obscuring the dashboard and corrupting full-page captures. The Web App variant already had the correct state model. This is a confirmed P0 defect, not a screenshot-only artifact.

## Risks and controls

- Preserve the dirty worktree and edit only in-scope files.
- No production database in visual QA; use an isolated seeded SQLite database.
- Do not change route names, Livewire event contracts, or permission scopes during visual foundation work.
- Batch visual verification at desktop and mobile, fix once, confirm once.
- Run Impeccable detector only after a slice is finished, never during concept selection.

## Visual evidence matrix

All captures use the isolated seeded QA database at `storage/framework/ui-qa-20260825.sqlite`; no production or Hostinger data was changed.

| Surface | Target viewport | Evidence |
| --- | --- | --- |
| Login | Desktop | `output/playwright/design-baseline/login-desktop.png` |
| Servant dashboard | 390x844 | `servant-dashboard-mobile-clean.png` |
| Servant beneficiary list | 390x844 | `servant-beneficiaries-mobile.png` |
| Servant beneficiary detail | 390x844 | `servant-beneficiary-detail-mobile.png` |
| Servant visit wizard | 390x844 | `servant-visit-wizard-mobile.png` |
| Web App dashboard | 390x844 light/dark | `web-dashboard-mobile-light.png`, `web-dashboard-mobile-clean.png` |
| Web App dashboard | 1440x900 light | `web-dashboard-desktop-light.png` |
| Filament dashboard | 1440x900 | `filament-dashboard-desktop.png` |

Normal viewport captures are authoritative. Full-page captures are retained only as diagnostic evidence because fixed sheets and notification overlays are repeated or composited incorrectly by full-page stitching.

## P0 state map

| Surface | Loading | Empty | Error/recovery | Success | Permission | Offline | Overflow/compact |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Login | submit busy label | n/a | field/status feedback | role redirect | role-specific redirect | retry copy required | six OTP cells fit 390px |
| Servant dashboard | skeleton cards | scheduled-visits empty state | global toast | visit-saved refresh | owned counts | queue/install surfaces | bottom nav + safe area |
| Beneficiary list | skeleton rows | search/filter empty | global toast | live filtering | owned/service-group query | cached-shell only | horizontal chips; no page-x overflow |
| Beneficiary detail | page loading | optional sections omitted | guarded record/403 | visit event | owned beneficiary only | read-only cached-shell intent | action group wraps at 390px |
| Visit wizard | Livewire busy/disabled save | beneficiary search empty | inline validation + recoverable step | toast + close | server-side ownership recheck | explicit offline queue | bottom sheet scroll/keyboard risk |
| Web App dashboard | dashboard skeletons | recent/attention empty variants | toast/global error | refreshed metrics | role-scoped cards/actions | shell fallback | mobile cards and fixed nav |
| Filament | lazy widget skeletons | widget/table empties | Filament notifications | standard action notices | resource `canAccess` gates | unsupported | desktop sidebar + table scroll |

## Baseline accessibility and runtime findings

- Primary routes expose named landmarks and most icon-only actions have accessible names.
- The closed visit wizard keeps its root `role="dialog"` in the DOM; focus containment, initial focus, return focus, and hidden accessibility-tree behavior require explicit verification during T016.
- Mobile tap targets are generally generous, but the Web App topbar becomes a dense horizontal row at 390px.
- Mixed Arabic/English enum values are visible (`home_visit`, `poor`, English month abbreviations); localization is incomplete.
- The Servant dashboard uses a large expressive display face and decorative blur/gradients that reduce operational hierarchy.
- Filament emits repeated Alpine errors: `groupIsCollapsed(label)` calls `.includes()` on `null`. The error repeats for each collapsible navigation group and coincides with blank/lazy dashboard regions.
- Filament's repeated notification ticker competes with the page title and controls and is the dominant visual failure in the supplied screenshot.
- No production data was written; the visit wizard was opened but not submitted.

## Resolution ledger

- **Resolved:** Servant notifications now use explicit closed/open CSS states; production build and 390px verification pass.
- **Resolved:** Filament topbar now mounts `NotificationsBellWidget` instead of the unstyled Servant bell, removing the repeated notification wall.
- **Resolved:** A nonce-safe `collapsedGroups` initializer prevents Filament's Alpine sidebar store from receiving `null`; the repeated `.includes()` console failures disappeared after reload.
- **In progress:** Shared semantic tokens, calm flat stat cards, shared empty/heading/avatar primitives, light defaults, and the Servant dashboard composition.
- **Pending:** Filament lazy-widget loading quality, complete servant beneficiary/wizard polish, full cross-surface dark tokens, and final accessibility automation.
