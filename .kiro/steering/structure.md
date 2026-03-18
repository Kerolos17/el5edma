# Project Structure

## Top-Level Layout

Standard Laravel 12 project. Admin UI is entirely Filament-based — there are no traditional Blade views for the main app.

```
app/
  Console/Commands/     # Scheduled artisan commands
  Exports/              # Maatwebsite Excel export classes
  Filament/             # All admin UI (Filament 4)
    Pages/              # Custom pages (Dashboard, Reports, Auth/Login)
    Resources/          # One folder per resource (see below)
    Widgets/            # Dashboard widgets
  Http/
    Controllers/        # Minimal — only ReportController, LocaleController, CodeLoginController
  Livewire/             # Standalone Livewire components (if any)
  Models/               # Eloquent models
  Observers/            # Model observers (audit logging)
  Providers/            # AppServiceProvider — registers observers, sets locale
  Services/             # Business logic (e.g. ReportService)

database/
  migrations/           # Timestamped migration files
  factories/
  seeders/

lang/
  ar/                   # Arabic translations (default)
  en/                   # English translations

resources/
  css/                  # app.css + filament theme
  js/                   # app.js
  views/                # Blade views (mainly PDF report templates)

routes/
  web.php               # Minimal: redirect /, language switch, PDF report routes
  console.php           # Scheduled command definitions

tests/
  Feature/
  Unit/
```

## Filament Resource Convention

Each resource lives in its own folder under `app/Filament/Resources/{ResourceName}/` and is split into:

```
{ResourceName}/
  {ResourceName}Resource.php     # Main resource class (navigation, model binding, page registration)
  Pages/                         # Create, Edit, List, View page classes
  Schemas/                       # {Resource}Form.php and {Resource}Infolist.php
  Tables/                        # {Resource}Table.php
```

Form and table logic is extracted into dedicated schema/table classes with a static `configure()` method, keeping the resource class thin.

## Models

All models are in `app/Models/`. Key relationships:

- `Beneficiary` → belongs to `ServiceGroup`, belongs to `User` (assigned servant)
- `Visit` → belongs to `Beneficiary`, many-to-many `User` via `visit_servants`
- `ScheduledVisit` → belongs to `Beneficiary`
- `ServiceGroup` → has `leader` and `serviceLeader` (both `User`)
- `User` → belongs to `ServiceGroup`; has roles via Spatie Permission

## Observers

Every key model has an observer registered in `AppServiceProvider`. Observers write to `AuditLog` on `created`, `updated`, and `deleted` events.

## Localization

All user-facing strings use `__('file.key')`. Translation files are in `lang/ar/` and `lang/en/`. The active locale is set per-user (`users.locale` column) and applied in `AppServiceProvider::boot()`.

## Role-Based Data Scoping

Resources scope their Eloquent queries in `getEloquentQuery()` based on `Auth::user()->role`:

- `servant` / `family_leader` — scoped to their `service_group_id`
- `service_leader` / `super_admin` — full access

Action visibility (delete, bulk actions) is also gated inline using `Auth::user()->role` checks.
