# Modernization roadmap

The goal is one maintained HLstatsX release that carries the strongest compatible community work, not a bundle of unrelated fork histories. Modernization must retain the stats data, supported game servers, plugins, and deployment behavior that existing operators rely on.

## Frontend and backend

Royal Multi Gamers' Laravel frontend now serves the site root. Keep the Perl daemon and its protocol, SourceMod integrations, and DB96 install input intact. Future frontend changes should use the existing Laravel tests and deployment helper; do not reintroduce the removed PHP frontend.

Use other forks' database and daemon changes as references only. Keep the Laravel web data boundary and Perl daemon protocol explicit; evaluate schema or transport changes with migration/replay tests before adoption.

## Daemon and game integrations

Compare StartersClan's daemon with NiDE, lovasatt, SyntX34, and SnipeZilla's daemon work. Keep protocol changes isolated from the application rewrite. For each candidate, identify packet formats, authentication, reconnect behavior, and game/plugin assumptions; add replay or integration tests before replacing the current path. Preserve CS:S ATB gates and SourceMod APIs across the work.

## Themes and interface

Continue improving the Laravel layouts and accessibility while preserving admin, in-game, and game-specific pages. Treat themes as lower priority than route, security, database, and daemon compatibility.

## Release gates

1. Keep the DB96 release usable while modernization proceeds on an isolated branch.
2. Require reversible migrations and a tested upgrade/rollback on a disposable copy of a representative database.
3. Compare public pages, admin writes, daemon logging, CS2 forwarding, and SourceMod behavior against the current release.
4. Ship a new architecture only after operators can migrate, verify, and roll back without losing data or configuration.
