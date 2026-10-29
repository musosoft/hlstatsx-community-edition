# Curated Edition Laravel frontend

This is the complete Royal-Multi-Gamers Laravel frontend imported from the pinned upstream source in [`UPSTREAM.md`](UPSTREAM.md), with narrow Curated Edition integration patches. Laravel serves the production site from the web root. The Perl daemon and SourceMod integrations remain independent; the old PHP frontend is no longer part of the active tree.

## Database contract

Laravel migrations are the modern DB96-to-DB97 upgrade path for official Curated Edition databases. There is no legacy PHP updater 97 to add or run. Do not use the upstream web installer or upstream in-app updater. Laravel requests fail closed unless `hlstats_Options.dbversion` is at least `HLSTATS_REQUIRED_DB_VERSION` (97 by default).

Back up an existing official DB96 database and test restoring it before upgrading. Configure the Laravel database connection with a dedicated account holding the required schema DDL and data-write grants, including access to Laravel's migration repository; the default read-only account is insufficient. From `src/laravel`, run:

```sh
php artisan migrate --force --isolated
php artisan migrate:status
```

The CLI guard rejects unsupported schema versions before migration DDL and blocks destructive `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback`, and `migrate:install` commands. Only forward `migrate` and diagnostic `migrate:status` are supported for the legacy database.

Verify that all migrations are applied and that the following query returns `97`:

```sql
SELECT value FROM hlstats_Options WHERE keyname = 'dbversion';
```

For a fresh install, seed an empty database with the official Curated Edition DB96 base in this repository's `src/sql/install.sql`, then run the same Laravel migrations and verification. Do **not** use `src/laravel/database/install.sql`: it is the old upstream DB91 base. Do not use the web installer or import either SQL file over an existing database.

MySQL/MariaDB DDL is not transactionally reversible; migration rollback is not a recovery plan. The live database is already at DB97. For a new installation, validate the DB96-to-DB97 migration on disposable data before using it.

## Host deployment and future updates

Production serves the built Laravel `/app/public` tree at `https://stats.lamateam.eu/`; writable storage and `.env` stay under `/opt/hlstatsx-laravel/shared`. Apache redirects the old `/laravel` prefix and preserves the ACME challenge path. To deploy a future source update from the `dev` worktree, run `bash tools/remote-pull.sh --apply-live`. It builds the Laravel artifact locally, transfers it with a SHA-256 check, activates the release on the host, preserves image data and daemon configuration, and checks origin routes and redirects. The runtime `.env` is never packaged or printed. The helper also runs public route checks; Cloudflare errors are reported as failures.

The protected runtime-account setup helper is `tools/create-laravel-runtime-account.php`; it reads the existing protected Laravel environment and never prints credentials. Keep `TRUSTED_PROXIES` as an explicit reverse-proxy allowlist and `SESSION_SECURE_COOKIE=true`; do not trust `*` when the origin is publicly reachable. For a future schema migration, provide root-only `/opt/hlstatsx-laravel/shared/.env.migration.credentials` with the DDL connection values, then run `sudo php tools/prepare-laravel-migration-env.php` to create a separate protected `.env.migration`; remove both files after migration succeeds.

The deployment helper does not kill UDP port owners; when daemon scripts change, systemd stops HLstats through `run_hlstats`' graceful SIGINT path. It preserves `hlstats.conf` and verifies daemon status after a restart.

Systemd is the sole production lifecycle owner for the Perl daemon. Do not schedule `run_hlstats start` or `run_hlstats restart` directly from cron: that can orphan a UDP listener, stale the PID file, and cause repeated systemd bind failures. Route any scheduled restart through `systemctl restart hlstats.service`. After repair/deploy, verify the unit is active, `run_hlstats status 27500` reports running, and UDP 27500 belongs to the unit's main PID.

`tools/smoke-laravel-routes.sh` checks representative public, API, sitemap, and admin-login routes without displaying page contents.

Existing administrator accounts are preserved; migration does not create or require a stock `admin` username. `hlstats_Users` remains canonical; `hlstats_Admins` is a Laravel login shadow. Laravel verifies canonical passwords before login, heals modern shadows before creating sessions, and revalidates canonical access on every authenticated request (100+ superadmin, 80+ moderator, lower denied). New passwords require at least 12 characters and at most 72 bytes.

Laravel account mutations and login reconciliation share a MySQL/MariaDB advisory lock, failing closed when unavailable. The protocol is `GET_LOCK('hlstatsx_admin_user_mutation', 5)`; hold the lock on the same physical connection across canonical checks and all writes, and `RELEASE_LOCK('hlstatsx_admin_user_mutation')` in a finally block. The lock is server-wide, not database-scoped. Password writes generate one bcrypt hash, write Users first, then mirror it exactly to Admins. Delete writes remove the shadow first. Failures are not transactionally rolled back: retry the operation, or sign in using the new canonical password to repair a failed shadow write. Laravel protects the stock `admin` identity if present and prevents deleting/demoting the last canonical user with `acclevel >= 100`.

Direct SQL or third-party writers do not participate in the Laravel advisory-lock protocol and remain outside this concurrency guarantee.

Focused regression checks require only installed Composer dependencies: `php tests/admin_accounts.php` uses in-memory SQLite. For advisory-lock and mixed-engine checks, set `ADMIN_TEST_MYSQL_HOST` to a disposable MySQL/MariaDB instance with empty root password and database `admin_accounts_test`, and explicitly set `ADMIN_TEST_MYSQL_ALLOW_RESET=1`; the harness verifies that database name, then drops/recreates only its two account tables. Never point it at production. Tests cover password formats/limits, stale shadows, policy protections, injected second-write failures/retries, and effective-role middleware.

This upgrade path targets only the official Curated Edition DB96 schema. Other forks may differ; their compatibility is best-effort and must be independently assessed.

## Local development

Use the repository's PHP 8.5 / Composer toolchain, then:

```sh
cd src/laravel
composer install
npm ci
cp .env.example .env
php artisan key:generate
npm run build
```

Configure the connection to an isolated database and follow the database procedure above to reach DB97. Never copy production credentials into `.env`. Defaults select a read-only DB username, file sessions/cache, and synchronous queues. Use the required DDL/write grants for migration, then a least-privilege runtime account. The Laravel admin panel requires separately provisioned write grants; its account synchronization tests pass, but do not expose/cut over the frontend until representative route/schema parity and migration failure-boundary checks pass.

## Provenance and local integrations

See [`UPSTREAM.md`](UPSTREAM.md) for the exact upstream commit and the narrow local adaptations. Do not copy upstream installer or migration instructions into deployment documentation. See the repository's [upstream maintenance policy](../../docs/upstream-maintenance.md) for intake and review rules.
