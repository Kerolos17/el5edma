# Project Structure

## Top-Level Layout

```
app/                    # Application code
database/               # Migrations, seeders, factories
resources/              # Views, CSS, JS, lang files
routes/web.php          # All routes (no api.php)
tests/                  # PHPUnit tests
.kiro/specs/            # Feature specs
```

## App Directory

```
app/
├── Console/Commands/       # Artisan commands (reminders, notifications)
├── DTOs/                   # Data Transfer Objects (e.g. MulticastResult)
├── Exports/                # Maatwebsite Excel export classes
├── Filament/
│   ├── Pages/              # Custom Filament pages (Dashboard, Reports, Auth/Login)
│   ├── Resources/          # One folder per resource (see Resource Structure below)
│   └── Widgets/            # Dashboard & topbar widgets
├── Http/
│   ├── Controllers/        # Thin controllers (reports, file access, FCM token, locale)
│   └── Middleware/         # SetLocale
├── Jobs/                   # Queued jobs (SendFcmNotificationJob)
├── Livewire/               # Livewire components (NotificationsBell, StatsOverviewWidget)
├── Models/                 # Eloquent models (11 models)
├── Observers/              # Model observers (audit logging, side effects)
├── Policies/               # Laravel authorization policies (one per model)
├── Providers/              # AppServiceProvider, AuthServiceProvider, AdminPanelProvider
└── Services/               # Business logic services
    ├── CacheService.php
    ├── EagerLoadingService.php
    ├── PushNotificationService.php
    ├── QueryMonitoringService.php
    └── ReportService.php
```

## Filament Resource Structure

Each resource follows this consistent pattern:

```
app/Filament/Resources/{ResourceName}/
├── {ResourceName}Resource.php   # Main resource class
├── Pages/
│   ├── List{ResourceName}.php
│   ├── Create{ResourceName}.php
│   ├── Edit{ResourceName}.php
│   └── View{ResourceName}.php
├── Schemas/
│   ├── {ResourceName}Form.php       # Form schema builder
│   └── {ResourceName}Infolist.php   # View/infolist schema builder
└── Tables/
    └── {ResourceName}Table.php      # Table configuration
```

The main `Resource.php` delegates to these classes via `form()`, `infolist()`, and `table()` methods.

## Models

| Model | Key Relations |
|-------|--------------|
| `User` | belongsTo ServiceGroup; hasMany Beneficiary (assigned), Visit, MinistryNotification |
| `Beneficiary` | belongsTo ServiceGroup, User (servant, createdBy); hasMany Visit, Medication, MedicalFile, PrayerRequest, ScheduledVisit |
| `ServiceGroup` | hasMany User, Beneficiary |
| `Visit` | belongsTo Beneficiary, User; belongsToMany User (servants via visit_servants) |
| `MinistryNotification` | belongsTo User |
| `AuditLog` | standalone audit trail |

## Localization

```
resources/lang/
├── ar/     # Arabic (primary)
└── en/     # English (fallback)
```

Translation keys used via `__('section.key')`. User locale stored on `users.locale` column.

## Tests

```
tests/
├── Unit/                   # Unit & property-based tests
│   ├── Jobs/
│   └── *PropertyTest.php   # Property-based tests (naming convention)
└── Feature/                # Feature/integration tests
```

## Key Conventions

- **Authorization**: Always use Laravel Policies — never inline role checks in controllers. Filament resources call `Auth::user()->can(...)` delegating to policies.
- **Role scoping**: `getEloquentQuery()` in resources applies `service_group_id` scoping for `family_leader` and `servant` roles.
- **Notifications**: Always insert to `ministry_notifications` table (bulk insert) AND dispatch `SendFcmNotificationJob` for push delivery.
- **Chunking**: Use `chunkById(100, ...)` for bulk operations in commands — never load all records at once.
- **Observers**: Registered in `AppServiceProvider::boot()`. Used for audit logging and side effects (not business logic).
- **Services**: Business logic lives in `app/Services/`. Controllers and commands stay thin.
- **DTOs**: Use DTOs (e.g. `MulticastResult`) to return structured data from services.
- **Eager loading**: Use `EagerLoadingService` to define relationship sets — prevents N+1 in list views.
- **Comments**: Code comments are written in Arabic (matching the domain language of the project).
