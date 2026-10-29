# HLstatsX: Curated Edition

HLstatsX community work is spread across many forks. This project gives that work a shared home: it starts with StartersClan's CS2-capable Docker base, then reviews fixes and features from the wider community and keeps compatible improvements together.

## Current state

- Release line: **1.13.0-CE / DB96**.
- Shipped architecture: Laravel frontend, Perl daemon, MySQL, and existing game/plugin interfaces. The prior PHP frontend remains only in Git history.
- Current curation includes selected security and compatibility fixes, DB96-safe changes, map features, responsive themes, and retained local CS:S Auto Team Balance work.
- A Laravel frontend and a modernized daemon are goals under evaluation. They are not replacements in the current release.

## Pages

- [Getting started](Getting-Started.md): run the example stack and find setup guidance.
- [Upstream sources](Upstream-Sources.md): what each fork contributes and how we handle it.
- [Modernization roadmap](Modernization-Roadmap.md): frontend, daemon, themes, and compatibility gates.
- [Contributing and upstream updates](Contributing.md): check, review, test, and bring changes forward.
- [Troubleshooting](Troubleshooting.md): trace web, daemon, game log, and database issues.

The repository's [feature matrix](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/docs/upstream-feature-matrix.md) and [review record](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/docs/curated-edition-upstream-review.md) contain commit-level decisions. A source appearing in those lists does not mean every feature has been merged.
