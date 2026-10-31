# HLstatsX: Curated Edition

HLstatsX community work is spread across many forks. This project gives that work a shared home: it starts with StartersClan's CS2-capable Docker base, then reviews fixes and features from the wider community and keeps compatible improvements together.

## Current state

- Release line: **1.13.0-CE**. Official DB96 installations migrate to **DB97** for the Laravel frontend; the live database is DB97.
- Shipped architecture: Laravel frontend at the site root, Perl 5.44 daemon, MySQL, and existing game/plugin interfaces. The prior PHP frontend remains only in Git history.
- Current curation includes selected security and compatibility fixes, OpenStreetMap pages, Laravel's built-in theme manager, and retained local CS:S Auto Team Balance work.
- The daemon remains the existing Perl implementation. Runtime maintenance is complete, but broader SnipeZilla/NiDE protocol modernization is still a separate, compatibility-tested task.

## Pages

- [Getting started](Getting-Started.md): run the example stack and find setup guidance.
- [Upstream sources](Upstream-Sources.md): what each fork contributes and how we handle it.
- [Modernization roadmap](Modernization-Roadmap.md): frontend, daemon, themes, and compatibility gates.
- [Contributing and upstream updates](Contributing.md): check, review, test, and bring changes forward.
- [Troubleshooting](Troubleshooting.md): trace web, daemon, game log, and database issues.

The repository's [feature matrix](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/docs/upstream-feature-matrix.md) and [review record](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/docs/curated-edition-upstream-review.md) contain commit-level decisions. A source appearing in those lists does not mean every feature has been merged.
