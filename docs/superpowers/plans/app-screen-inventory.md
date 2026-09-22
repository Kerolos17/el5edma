# App Screen Inventory

This inventory defines the UI/UX redesign surface. Mobile servant screens are the first design priority.

## Servant App

| Route | Component | View | Priority | Mobile Notes |
| --- | --- | --- | --- | --- |
| `/servant/dashboard` | `App\Livewire\Servant\Dashboard` | `resources/views/livewire/servant/dashboard.blade.php` | P0 | First redesign target. Must center today's work and urgent actions. |
| `/servant/beneficiaries` | `BeneficiaryList` | `beneficiary-list.blade.php` | P0 | Thumb-friendly search, filter chips, quick profile entry. |
| `/servant/beneficiaries/{beneficiary}` | `BeneficiaryDetail` | `beneficiary-detail.blade.php` | P0 | Task hub for visit, medical, prayer, history. |
| `/servant/visits` | `VisitList` | `visit-list.blade.php` | P1 | Timeline/list with filters and critical cases. |
| `/servant/scheduled-visits` | `ScheduledVisitList` | `scheduled-visit-list.blade.php` | P0 | Date grouping and clear complete/cancel flows. |
| `/servant/prayer-requests` | `PrayerRequestList` | `prayer-request-list.blade.php` | P1 | Fast creation with beneficiary context. |
| `/servant/medical-files` | `MedicalFileList` | `medical-file-list.blade.php` | P1 | Privacy-safe layout and readable sections. |
| `/servant/profile` | `Profile` | `profile.blade.php` | P2 | Account and app settings. |
| Persistent wizard | `CreateVisitWizard` | `create-visit-wizard.blade.php` | P0 | Must become the best mobile flow in the product. |

## Web App

| Route | Component | View | Priority | Responsive Notes |
| --- | --- | --- | --- | --- |
| `/app/dashboard` | `WebApp\Dashboard` | `web-app/dashboard.blade.php` | P0 | Role-specific dashboard, tablet safe. |
| `/app/beneficiaries` | `BeneficiariesPage` | `beneficiaries-page.blade.php` | P0 | Filters, bulk actions, mobile card fallback. |
| `/app/beneficiary/{beneficiary}` | `BeneficiaryProfilePage` | `beneficiary-profile-page.blade.php` | P0 | Structured operational record. |
| `/app/visits` | `VisitsPage` | `visits-page.blade.php` | P1 | Review and status management. |
| `/app/visit/{visit}` | `VisitProfilePage` | `visit-profile-page.blade.php` | P1 | Visit detail and audit context. |
| `/app/scheduled-visits` | `ScheduledVisitsPage` | `scheduled-visits-page.blade.php` | P1 | Assignment and completion status. |
| `/app/prayer-requests` | `PrayerRequestsPage` | `prayer-requests-page.blade.php` | P2 | Lifecycle view. |
| `/app/medical-files` | `MedicalFilesPage` | `medical-files-page.blade.php` | P1 | Privacy and scoped access clarity. |
| `/app/reports` | `ReportsPage` | `reports-page.blade.php` | P1 | Export states and async readiness. |
| `/app/users` | `UsersPage` | `users-page.blade.php` | P1 | Safer role assignment UX. |
| `/app/service-groups` | `ServiceGroupsPage` | `service-groups-page.blade.php` | P1 | Group operational overview. |
| `/app/service-group/{serviceGroup}` | `ServiceGroupProfilePage` | `service-group-profile-page.blade.php` | P1 | Members, beneficiaries, visits. |
| `/app/notifications` | `NotificationsPage` | `notifications-page.blade.php` | P1 | Unread grouping and mark-read behavior. |
| `/app/audit-logs` | `AuditLogsPage` | `audit-logs-page.blade.php` | P2 | Investigation/scanning layout. |
| `/app/profile` | `ProfilePage` | `profile-page.blade.php` | P2 | Account settings. |

## Shared Layouts And Components

| Surface | File | Role |
| --- | --- | --- |
| Web App layout | `resources/views/web-app/layouts/app.blade.php` | Sidebar, main content, mobile nav, drawer, PWA prompt. |
| Servant layout | `resources/views/servant/layouts/app.blade.php` | Mobile-first shell, desktop sidebar, bottom nav, toast, wizard. |
| Servant bottom nav | `resources/views/components/servant/bottom-nav.blade.php` | Core mobile navigation plus visit FAB. |
| Web App header | `resources/views/components/web-app/header.blade.php` | Topbar and user controls. |
| Web App modal | `resources/views/components/web-app/modal.blade.php` | Candidate for mobile bottom sheet behavior. |
| Empty states | `resources/views/components/ui/empty-state.blade.php`, `resources/views/components/web-app/empty-state.blade.php` | Standard empty state language. |
| Skeleton | `resources/views/components/web-app/table-skeleton.blade.php` | Loading baseline. |

## First UI Slice

- [ ] Redesign servant dashboard.
- [ ] Add shared servant mobile primitives only where the dashboard needs them.
- [ ] Verify 360px and 390px layouts.
- [ ] Keep the current route and Livewire component contract stable.

