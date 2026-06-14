# Dockge Deployment (Reusable Template)

This repository includes a generic Dockge stack template that is safe to clone for multiple HLstatsX:CE instances.

## Files

- `compose.dockge.example.yaml`: stack template
- `.env.example`: environment template

## Quick start

1. Clone this repository on your Docker host.
2. Create a new stack directory in your Dockge stacks folder.
3. Copy `compose.dockge.example.yaml` to that stack directory as `compose.yaml`.
4. Copy `.env.example` to the same stack directory as `.env`.
5. Update `.env` with unique values, especially:
   - `MYSQL_ROOT_PASSWORD`
   - `MYSQL_PASSWORD`
   - `HLX_DB_PASS`
   - `DB_VOLUME_NAME`
   - `HLX_WEB_PORT`
   - `HLX_UDP_PORT`
   - `HLX_PMA_PORT`
   - `HLX_AWARDS_CRON`
   - `HLX_AWARDS_MAX_LAG_DAYS`
   - `HLX_AWARDS_GRACE_MINUTES`
   - `HLX_SOURCE_DIR` (absolute path to this repository clone)
6. Start stack in Dockge.

## Multi-instance pattern

For each additional game server/stats instance:

1. Copy the stack directory to a new name.
2. Set unique values in that stack `.env`:
   - `DB_VOLUME_NAME`
   - `HLX_WEB_PORT`
   - `HLX_UDP_PORT`
   - `HLX_PMA_PORT`
   - DB credentials
3. Open only the new UDP ingest port from the specific game server IP.
4. Keep web/phpMyAdmin bindings on `127.0.0.1` and publish externally via reverse proxy.
5. Keep the `awards` service enabled; the live daemon does not generate daily awards by itself.

## Security notes

- Do not commit `.env` files.
- Keep secrets only in `.env`.
- Put HTTP/HTTPS behind reverse proxy + WAF/CDN if internet-facing.
- Restrict daemon UDP ingest by source IP in firewall rules.
- Keep the DB `command` SQL mode in the template (`NO_ENGINE_SUBSTITUTION`) to avoid legacy HLX query failures on strict MySQL defaults.
- The `awards` service runs `hlstats-awards.pl` on `HLX_AWARDS_CRON` and is required for daily awards and ribbon maintenance.
- The `awards` healthcheck also verifies `hlstats_Options.awards_d_date` freshness, with `HLX_AWARDS_MAX_LAG_DAYS=2` as a hard cap and `HLX_AWARDS_GRACE_MINUTES=90` for the post-schedule grace window.

## GeoIP updates

- `daemon` and `awards` mount `${HLX_SOURCE_DIR}/src/scripts/GeoLiteCity` read-only, so GeoIP database refreshes do not require rebuilding images.
- Keep `GeoLite2-City.mmdb` and `GeoLiteCity.dat` out of git; they are ignored.
- To refresh MaxMind GeoLite2 City, set `MAXMIND_LICENSE_KEY` in the shell, pass it as the first argument, or create a root-only `/root/.maxmind.env` containing `MAXMIND_LICENSE_KEY=...`.
- Run `src/scripts/GeoLiteCity/install_binary.sh` from the repository or directly by path, then recreate/restart the `daemon` service so its cached GeoIP reader opens the new file.
