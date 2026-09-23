# Firebase Cloud Messaging (FCM) — Setup & Operations

Real browser push notifications: Laravel → FCM → Service Worker → browser,
even with the tab in the background.

```text
Browser (getToken + VAPID)
   │  POST /fcm-token {fcm_token, platform, device_label} (auth + CSRF + throttle)
   ▼
push_devices table (one row per browser, token_hash unique)
   │
   │  Commands / Services → SendFcmNotificationJob (queued, exponential backoff)
   ▼
PushNotificationService (kreait/firebase-php, HTTP v1, no legacy server keys)
   │  invalid tokens purged from push_devices + legacy users.fcm_token
   ▼
Firebase Cloud Messaging → /sw.js (background) / onMessage (foreground)
   ▼
Bell refresh via Livewire `fcmMessageReceived` (no duplicate tones)
```

## Architecture (what exists, don't duplicate)

| Layer | Implementation |
|---|---|
| Token table | `push_devices` (`user_id` FK cascade, `token_hash` unique, `token` encrypted, `platform`, `device_label`, `last_seen_at`) + transitional `users.fcm_token` |
| Token endpoint | `POST /fcm-token` → `FcmTokenController@store` (auth, `throttle:10,1`, `updateOrCreate` by hash, cross-user reassign, session binding) |
| Sender | `PushNotificationService::{sendToUser,sendToMultiple,sendMulticast,sendBatch}` |
| Queue | `SendFcmNotificationJob` (tries 3, backoff 60s→5m→15m + jitter, `failed()` logs) |
| Frontend | `resources/js/push-notifications.js` shared by **all** bundles (admin, web-app, servant) |
| Permission UX | Explicit `[data-push-enable]` buttons only (bell dropdowns). Never auto-prompted. Silent re-sync when already granted |
| SW | `public/sw.js` (v8, background display + safe same-origin click URLs) |
| Test command | `php artisan pwa:test-push {uid_or_email?} {--token=} {--title=} {--body=}` |
| Producers | `reminders:birthdays`, `reminders:unvisited`, `SendScheduledVisitReminders`, registration flow, retry command |

## Environment variables

Backend (`.env`):

```env
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
FIREBASE_PROJECT=
FIREBASE_PROJECT_ID=
```

Frontend (Vite, public-safe only):

```env
VITE_FIREBASE_API_KEY=
VITE_FIREBASE_AUTH_DOMAIN=
VITE_FIREBASE_PROJECT_ID=
VITE_FIREBASE_STORAGE_BUCKET=
VITE_FIREBASE_MESSAGING_SENDER_ID=
VITE_FIREBASE_APP_ID=
VITE_FIREBASE_MEASUREMENT_ID=
VITE_FIREBASE_VAPID_KEY=
```

## Firebase Console — where to get each value

1. **Web App config** (`VITE_FIREBASE_*` except VAPID): Firebase Console →
   Project settings → General → *Your apps* → Web app → *SDK setup and
   configuration* (use the `firebaseConfig` values).
2. **VAPID public key** (`VITE_FIREBASE_VAPID_KEY`): Project settings →
   *Cloud Messaging* → *Web Push certificates* → generate/copy the key pair.
3. **Service account JSON** (`FIREBASE_CREDENTIALS` file): Project settings →
   *Service accounts* → *Generate new private key*. Save it as
   `storage/app/firebase-credentials.json` on the server. **Never commit it,
   never put it in `public/`, never expose it to JS** (gitignored already).

## Local development

```bash
composer install
npm install
npm run dev          # Vite (servant.css/js, web-app.css/js, app.js)
php artisan serve    # or: composer dev
php artisan queue:work   # database queue; FCM jobs need a worker
php artisan migrate --seed
```

Login as servant (`servant@ministry.local` / code `4444` after seeders).

## Testing (manual, with a real browser)

1. Login over **HTTPS or localhost** (push requires a secure context).
2. Open the bell dropdown → **Enable push notifications** → accept the browser prompt.
3. Verify registration: `push_devices` has a row for your user
   (`select id,user_id,platform,last_seen_at from push_devices;`).
4. Foreground: keep tab open, run
   `php artisan pwa:test-push servant@ministry.local` → bell refreshes, no double tone.
5. Background: minimize/switch tab, re-run → OS notification appears.
6. Click behavior: notification opens/focuses an app-owned URL only
   (foreign URLs fall back to `/app/dashboard`).
7. Blocked state: deny permission → button shows the blocked label, no re-prompt.
8. Direct token test (no user needed):
   `php artisan pwa:test-push --token="FCM_TOKEN" --title="Hi"`.
9. Stale cleanup: revoke in `chrome://settings/content/notifications` or use an
   old token → next send purges it (`handleInvalidTokens`), subsequent
   `sendToUser` skips it silently.

## Production

- **HTTPS is mandatory** (service workers + VAPID + secure cookies).
- Run a queue worker (`queue:work --sleep=3 --tries=3`) plus the scheduler cron
  (`schedule:run` every minute) for reminder commands.
- `storage/app/firebase-credentials.json`: `chmod 600`, owned by the PHP user,
  deployed out-of-band (never in git, never in `public/`).
- `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`.
- Old browsers: feature-detected (`Notification`/`serviceWorker`/`isSupported`);
  the enable button shows *unavailable*, nothing crashes.
- Do not log full tokens anywhere (only counts / masked prefixes).

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| No token received (console warning) | Wrong/missing `VITE_FIREBASE_VAPID_KEY`, or permission denied |
| `401` on `/fcm-token` | Session expired — re-login; route needs `web`+`auth` |
| Duplicate rows | Impossible by schema (`token_hash` unique); check `updateOrCreate` path |
| SW serves stale app after deploy | `sw.js` version bump + one controlled reload (built into `pwa-head`) |
| Legacy `firebase-messaging-sw.js` workers | Auto-unregistered by `cleanupLegacyFirebaseWorker` on next sync |
| `pwa:test-push` enum error | Fixed: command no longer writes `ministry_notifications` rows |
| Battery of scheduled duplicates | Birthday dedupe via `dedupe_key`; unvisited uses `reminder_sent_at` |

## Security notes

- Private key / `client_email` never leave the server (`Kreait` factory only).
- Token endpoint derives the user from `auth()->user()` — a `user_id` in the
  request body is ignored; cross-user rows are reassigned + legacy cleared.
- Notification click URLs are same-origin validated (`safeInternalPath`).
- `MinistryPushNotifications` JS API: `{ enable(), sync(), permission() }`.
