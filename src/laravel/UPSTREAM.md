# Laravel upstream provenance

- Repository: <https://github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel>
- Branch: `main`
- Imported commit: `5e43cbfa167bb9ed8d21b4feaf69858fc2227d0c`
- License: GPL-2.0 (see the upstream `LICENSE` file)
- Import policy: retain the complete upstream application and make narrow, documented integration patches. Refresh only after reviewing the pinned source range and updating `docs/upstream-sources.json`.

## Curated Edition adaptations

- Add a request readiness gate requiring DB97; Laravel migrations own the official DB96→DB97 transition. DB96 SQL and legacy Git history remain as the upgrade input and reference; the PHP frontend/updater is not part of the deployed product.
- Keep database/session/cache/queue defaults conservative for local development and production.
- Remove the web installer and in-app updater routes; require operators to run the reviewed Laravel migrations explicitly.
- Make upstream migrations retry-aware after partial MySQL/MariaDB DDL, preserve existing administrator accounts without requiring or creating the stock `admin` username, and add a final fail-closed DB97 schema/version migration. MySQL/MariaDB DDL recovery requires backup/restore; the final migration refuses rollback.
- Preserve webapp-version options with insert-if-absent semantics until finalization rather than overwriting local values on each historical migration.
- Verify inherited admin passwords with the legacy RIPEMD-128 algorithm and modern Laravel hashes. Keep `hlstats_Users` canonical and mirror identical modern hashes to `hlstats_Admins`; revalidate effective access from the canonical row on each request.
- Serialize Laravel account mutations and login reconciliation with a server-wide advisory lock, fail closed on contention, and protect the stock `admin` if present plus the last full administrator. Preserve upstream behavior elsewhere.
- Retain the upstream public feature set and add the prior preview's bounded seven-day trend and skill-gain summaries to the upstream game page. Upstream already provides skill-history views, player search, OSM maps, sitemap, and broad statistics routes.
- Remove automatic migrations from the Composer setup scripts so setup cannot silently modify an operator database.

This record identifies intentional drift. Fresh-install migration smoke tests pass on disposable MySQL 9.7.4 and MariaDB 10.11, including migration repository bookkeeping and seeded-admin hash preservation. Focused account tests pass on SQLite and MariaDB, including injected partial writes, retries, lock contention, role revalidation, and password compatibility. Production runs DB97 at the domain root; deployment verification is recorded in the upstream review and root-daemon progress notes.
