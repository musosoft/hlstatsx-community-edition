# Getting started

## Run the example stack

Install Docker Engine and the Docker Compose plugin, clone this repository, create a local app key, then start the example services and apply the DB96→DB97 Laravel migrations:

```sh
export LARAVEL_APP_KEY="base64:$(openssl rand -base64 32 | tr -d '\n')"
export LARAVEL_DB_USER=hlstatsxce
export LARAVEL_DB_PASSWORD=hlstatsxce
docker compose -f docker-compose.example.yml up --build -d
docker compose -f docker-compose.example.yml exec laravel php artisan migrate --force --isolated
```

Read the [example Compose file](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/docker-compose.example.yml) and its environment settings before exposing services or changing the defaults. It contains development credentials, including game-server and database values. Replace them before internet exposure; keep RCON, proxy, and database credentials private.

The Laravel web image uses PHP 8.5.12. The standalone heatmap image uses the same PHP runtime with GD and MySQL extensions.
The example exposes Laravel on `127.0.0.1:8081` by default; set `LARAVEL_HOST_PORT` to choose another local port.

The Compose files target MySQL 26.10 with a separately named `db-volume-mysql2610` volume. DB96 remains the fresh-install base; Laravel migrations advance it to DB97. Never attach an existing database volume to a different MySQL major version without a tested migration plan.

The development Compose file keeps Laravel in the optional `laravel` profile; the example Compose stack starts it by default. Requests return 503 until the database reaches DB97. Run the Laravel migration command on a disposable database before browsing. No legacy PHP updater is included.

The example starts a CS 1.6 server, database, Laravel app, Perl daemon, awards scheduler, and heatmap generator. `src/sql/install.sql` initializes a new database volume with the official Curated Edition DB96 base only. For a fresh Laravel install, seed that base into an empty database, then run Laravel migrations. Do **not** use `src/laravel/database/install.sql` (the old upstream DB91 base), the web installer, or the upstream in-app updater. Never import `install.sql` over existing data.

For an existing official DB96 database, first back it up and test restoring the backup. Configure Laravel's database connection with a dedicated user holding the required DDL and data-write grants, including access to its migration repository; the default read-only account cannot migrate. Then run from the repository root:

```sh
cd src/laravel
php artisan migrate --force --isolated
php artisan migrate:status
```

Verify all migrations are applied and query `SELECT value FROM hlstats_Options WHERE keyname = 'dbversion';` to confirm `97`. MySQL/MariaDB DDL is not transactionally reversible. Other forks are best-effort only and require independent assessment. See the [Laravel README](../../src/laravel/README.md) for the database contract, production root, and update command.

The daemon listens on UDP port `27500`. External servers must send logs there. CS2 uses StartersClan's `source-udp-forwarder`; follow the commented CS2 example and set its proxy key and registered server address consistently. The optional CS:S smoke-test profile does not include the curated SourceMod plugin and does not test ATB integration. Follow the Compose file comments for `CSS_RCON_PASSWORD`.

## Configure HLstatsX

1. Open the Laravel service at the address configured in Compose.
2. Set the daemon proxy key in the admin settings and the matching forwarder configuration.
3. Enable the game you want to track, then add its server and RCON settings.
4. Send a test log line and confirm the daemon records it before opening public access.

The exact ports, service names, and supported environment variables live in the Compose and configuration files. The [upstream integration review](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/docs/curated-edition-upstream-review.md) explains the current DB96 upgrade and compatibility limits.

Administrators can select one of the six built-in themes from the Laravel theme manager. Visitor-selected light/dark preferences from the removed PHP frontend are not currently available.

Keep the persistent database volume when stopping the stack. `docker compose down -v` removes it and its data.

## Development checks

Run the repository's test runner from the project root:

```sh
./test/test.sh dev 1
```

The test runner uses an isolated Compose project name, but still creates test containers and database volumes. Use a disposable checkout and read [`test/test.sh`](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/test/test.sh) before running it. For focused checks, see the [test scripts](https://github.com/musosoft/hlstatsx-community-edition/tree/dev/test) and the [contribution guide](Contributing.md).
