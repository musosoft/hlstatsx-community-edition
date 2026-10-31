# Modernization roadmap

The goal is one maintained HLstatsX release that carries the strongest compatible community work, not a bundle of unrelated fork histories. Modernization must retain the stats data, supported game servers, plugins, and deployment behavior that existing operators rely on.

## Frontend and backend

Royal Multi Gamers' Laravel frontend serves the site root. The supported path is official DB96 install data migrated to DB97 by the reviewed Laravel migrations. Keep the Perl daemon and its protocol, SourceMod integrations, and persistent game assets intact. Future frontend changes should use the existing Laravel tests and deployment helper; do not reintroduce the removed PHP frontend.

Use other forks' database and daemon changes as references unless a specific change passes review. Keep the Laravel web data boundary and Perl daemon protocol explicit; evaluate schema or transport changes with migration/replay tests before adoption. DB97+ schemas from other forks are not guaranteed upgrade inputs.

## Daemon and game integrations

The current daemon remains Perl; its container runtime is Perl 5.44, not a SnipeZilla daemon replacement. Compare StartersClan's daemon with NiDE, lovasatt, SyntX34, and SnipeZilla's work. Keep protocol changes isolated from the frontend. For each candidate, identify packet formats, authentication, reconnect behavior, and game/plugin assumptions; add replay or integration tests before changing the current path. Preserve CS:S ATB gates and SourceMod APIs across the work.

## Themes and interface

Continue improving the Laravel layouts and accessibility while preserving admin, in-game, and game-specific pages. Treat themes as lower priority than route, security, database, and daemon compatibility.

## Release gates

1. Keep the official DB96 install input and DB96→DB97 migration tests valid.
2. Run route/account/award/heatmap integration checks on the isolated Compose test project.
3. Compare public pages, admin writes, daemon logging, CS2 forwarding, and SourceMod behavior when those areas change.
4. Promote `dev` to `main` through the documented release workflow after the relevant checks pass; preserve operator data and protected runtime configuration.
