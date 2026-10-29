# HLstatsX: Curated Edition

HLstatsX has many community forks. Each has useful fixes, features, and deployment work. This fork aims to bring the best compatible changes under one roof and keep them moving with upstream: starting from StartersClan's CS2-capable Docker base, then curating work from NiDE, Royal Multi Gamers, A1mDev, lovasatt, SyntX34, SnipeZilla, and other contributors.

The production site runs the pinned Laravel frontend at the root URL, with the Perl HLstats daemon and SourceMod integrations retained. The legacy PHP frontend is removed from the active source tree and production document root; its history and DB96 install base remain. Laravel owns the official DB96→DB97 migration path, and the live database is DB97. Deploy future source updates from `dev` with `bash tools/remote-pull.sh --apply-live`; it builds and transfers a release and checks root routes, legacy redirects, and session security.

The project is licensed under [GPL-2.0-or-later](LICENSE).

**Start here:** [Wiki pages](docs/wiki/Home.md) · [Upstream map](docs/wiki/Upstream-Sources.md) · [Current integration status](docs/upstream-feature-matrix.md) · [Curation record](docs/curated-edition-upstream-review.md)

To check for upstream movement, run `scripts/check-upstreams.sh`. For development, installation, release status, and contribution guidance, see the [wiki pages](docs/wiki/Home.md). Report issues in [GitHub Issues](https://github.com/musosoft/hlstatsx-community-edition/issues).
