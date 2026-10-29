# Troubleshooting

Use the current container logs and configuration first. Several linked community posts describe older, manually installed releases; their symptoms may still help, but their package versions and commands are obsolete.

## No player or server statistics

1. Check service health with `docker compose ps` and inspect daemon output with `docker compose logs --tail=200 daemon`.
2. Confirm that the game server sends logs to the daemon's reachable address on UDP `27500` and that firewalls allow the traffic.
3. Compare the source address and port seen by the daemon with the server registered in the HLstatsX admin panel.
4. Confirm the daemon is connected to the expected database and that the server is enabled for the right game.

CS2 does not use the example's direct UDP log command. Route its HTTP log output through StartersClan's `source-udp-forwarder`; check the forwarder's logs, registered server IP/port, and shared proxy key.

## Web or admin errors

- Check the web and database logs with `docker compose logs --tail=200 web db`.
- Verify `DB_ADDR`, `DB_NAME`, `DB_USER`, and `DB_PASS` match the database service.
- For an existing database, back it up and use the admin updater. `install.sql` seeds a new database volume; it is not an upgrade script.
- If an admin form appears to do nothing, check the session, permissions, CSRF token, and web logs before retrying the operation.

## Awards and heatmaps are stale

Inspect the `awards` and `heatmaps` service logs. Awards run on a daily schedule in the example. Heatmap output shares the games volume with the Laravel service; confirm that volume is mounted and writable by the container.

## Historical references

- [NomisCZ installation guide](https://github.com/NomisCZ/hlstatsx-community-edition-wiki/blob/master/Installation.md) and [requirements](https://github.com/NomisCZ/hlstatsx-community-edition-wiki/blob/master/Requirements.md) explain the database, web, and daemon split. The PHP 7 and old Windows Perl instructions do not apply to this release.
- [Archived HLstatsX wiki home](https://web.archive.org/web/20190716134909/https://bitbucket.org/Maverick_of_UC/hlstatsx-community-edition/wiki/Home) points to installation, daemon, developer, and community support material.
- [AlliedMods: installed but 0/0 players](https://forums.alliedmods.net/archive/index.php/t-209080.html), [stats not showing](https://forums.alliedmods.net/archive/index.php/t-317556.html), and [FAQ](https://forums.alliedmods.net/archive/index.php/t-201556.html) cover historical symptoms. Treat them as diagnostic context, not current deployment instructions.
