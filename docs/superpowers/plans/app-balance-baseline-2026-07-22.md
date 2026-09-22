# Baseline - App Balance Roadmap - 2026-07-22

## Runtime Checks

### Laravel tests

Command attempted:

```powershell
php artisan test
```

Result:

- Blocked: `php` was not available on PATH.

Command attempted with XAMPP PHP:

```powershell
C:\xampp\php\php.exe artisan test
```

Result:

- Blocked by PHP version mismatch.
- Installed CLI runtime: PHP 8.2.12.
- Composer platform requirement: PHP >= 8.4.0.

Action needed:

- Install or expose PHP 8.4+ in the execution environment before Laravel tests can run.

### Frontend build

Command attempted:

```powershell
pnpm run build
```

Result:

- Initial fallback `pnpm` run tried to perform a non-interactive install and aborted.

Command completed:

```powershell
$env:PATH="C:\Users\kerol\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin;$env:PATH"
node_modules\.bin\vite.cmd build
```

Result:

- Passed.
- Vite transformed 79 modules and generated production assets under `public/build/`.

## Phase 1 Readiness Scan

Already fixed or currently safe:

- `RegistrationLinkService::generateRegistrationUrl()` uses `route('registration.show', ...)`.
- `InternalNotificationService::notifyAll()` uses active scoped `chunkById(200)` instead of `User::all()`.
- `VisitPolicy::view()` and `update()` call `loadMissing('beneficiary')` and guard null beneficiaries.
- `PrayerRequestResource::canAccess()` delegates to `viewAny` policy.
- `ScheduledVisitResource::canAccess()` delegates to `viewAny` policy.
- `QueryMonitoringService` logs binding count instead of raw bindings.
- `NotificationsBell::markRead()` scopes by current `user_id`.

Needs follow-up verification once PHP 8.4 is available:

- Notification command behavior and localized FCM body text.
- Report authorization behavior across all roles.
- Policy coverage for every Web App and Filament resource.
- Database indexes and large export behavior.

## Environment And App Operations

### `.env.example`

- `APP_LOCALE=ar` and `APP_FALLBACK_LOCALE=en`, matching the Arabic-first product direction.
- `QUEUE_CONNECTION=database`, so production needs a running queue worker and migrated jobs tables.
- `CACHE_STORE=file`, while `config/cache.php` defaults to `database` if env is absent. Production should explicitly choose `redis` or `database`; `file` is not ideal for concurrent users.
- Firebase backend and web variables are present but empty by default.
- `FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json`, so deployment must provide this file securely outside git.

### Scheduler

Configured scheduled commands:

- `reminders:birthdays` daily at 08:00.
- `reminders:scheduled-visits` daily at 09:00.
- `reminders:unvisited` weekly Friday at 10:00.
- `notifications:cleanup` weekly Friday at 00:00.
- `notifications:retry-critical` every five minutes.

Production needs:

- Laravel scheduler running every minute.
- Queue worker running continuously.
- Database queue and failed jobs tables migrated.

### PWA

- `public/manifest.json` exists.
- `public/sw.js` exists.
- Manifest is Arabic/RTL and starts at `/app/dashboard`.
- Existing shortcuts point to Web App routes, not servant routes. This is acceptable for now, but the mobile-first servant redesign should decide whether servant shortcuts are needed.

## Notes

- `graphify-out/` and `app/graphify-out/` are untracked generated analysis outputs from earlier Graphify work.
- `git status` also reports a warning reading `C:\Users\kerol/.config/git/ignore`; this does not affect the repository files.
