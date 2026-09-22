# Ministry System Production Runbook

## Release gates

A release is eligible only when all of these pass on the exact commit being deployed:

```bash
composer validate --strict
composer audit --locked --no-interaction
composer install --no-interaction --prefer-dist
npm ci
npm audit --audit-level=high
npm run build
./vendor/bin/pint --test
./vendor/bin/phpunit --fail-on-phpunit-deprecation
php artisan view:cache
```

Do not deploy with uncommitted production changes, a missing `APP_KEY`, or a
database backup that has not been restored in a rehearsal.

## Required production services

- PHP 8.4 with `gd`, `intl`, `mbstring`, PDO, and the selected database driver.
- MySQL 8+ or PostgreSQL with a dedicated least-privilege application account.
- HTTPS with the web root pointed only to `public/`.
- A supervised queue worker: `php artisan queue:work --tries=3 --timeout=90`.
- Scheduler cron every minute: `php artisan schedule:run`.
- Persistent storage for `storage/app`; never place medical files on ephemeral disk.
- External uptime monitoring for Laravel's `/up` health endpoint.

Start from `.env.production.example`. Store `.env`, Firebase credentials, and
backup credentials outside Git. Keep `APP_DEBUG=false`, encrypted sessions,
secure cookies, and real SMTP/broadcast providers.

## Backup before every migration

Back up all three recovery assets:

1. The database, using a transaction-consistent dump.
2. `storage/app`, including private medical files.
3. The current `.env` and especially `APP_KEY`, in an encrypted secret store.

Example MySQL database backup (prompts for the password):

```bash
mkdir -p backups
mysqldump --single-transaction --routines --triggers --hex-blob \
  -h DB_HOST -P DB_PORT -u DB_USERNAME -p DB_DATABASE \
  | gzip > "backups/ministry-$(date +%Y%m%d-%H%M%S).sql.gz"
```

Create a separate encrypted archive of `storage/app`. Verify both archives are
non-empty, copy them off-server, and rehearse restoration in staging. Losing
`APP_KEY` makes encrypted personal and medical fields unrecoverable.

## Deployment

Deploy an immutable release directory or a clean checkout. After backups are
verified:

```bash
MINISTRY_BACKUP_CONFIRMED=yes ./deploy.sh
```

The script refuses production deployment when `APP_ENV`, `APP_DEBUG`,
`APP_KEY`, or backup confirmation is unsafe. If a command fails after
maintenance mode begins, the application intentionally remains down for
operator review.

## Post-deployment verification

Within five minutes:

1. Confirm `GET /up` returns HTTP 200 over HTTPS.
2. Sign in with one test account for each role.
3. Verify cross-group records are not visible to servants or family leaders.
4. Create and close a test visit, then verify its audit entry.
5. Download an authorized medical file and confirm an unauthorized account gets 403.
6. Confirm the queue has no growing backlog and the scheduler has run.
7. Verify push notification delivery on one Android device and one desktop browser.
8. Check application logs for new errors, failed jobs, and repeated 4xx/5xx responses.

## Rollback

If code fails but migrations are backward-compatible, point the release symlink
back to the previous release, run its cache commands, restart queue workers, and
bring the app up.

If a migration changed data incompatibly:

1. Keep the application in maintenance mode.
2. Stop queue workers and scheduler.
3. Restore the pre-release database dump and matching `storage/app` archive.
4. Restore the same `APP_KEY` and production secrets.
5. Activate the previous code release, rebuild caches, restart workers, and run
   the post-deployment verification list.

Never run a destructive down-migration against the only production copy.

## Monitoring and recovery targets

- Alert on `/up` failure, HTTP 5xx spikes, queue failures, disk usage, and backup age.
- Review `failed_jobs` daily and application errors immediately.
- Retain daily backups for 14 days and monthly backups for 12 months, encrypted off-site.
- Initial targets: RPO 24 hours and RTO 4 hours. Tighten them after the first
  successful restore rehearsal.
- Run a restore rehearsal quarterly and record duration, missing steps, and owner.
