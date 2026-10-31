# Upstream sources

HLstatsX has grown through community forks that solve different problems. This project tracks their work by source and commit, then chooses an integration path that fits the product's license, schema, runtime, and existing server interfaces.

| Project | What it contributes | Curation approach |
| --- | --- | --- |
| [StartersClan HLstatsX-CE](https://github.com/startersclan/hlstatsx-community-edition) | Starting application, CS2-capable Docker setup, and community fixes | Canonical legacy base. Merge compatible updates after checking shared history and protecting local changes. |
| [NiDE-gg](https://github.com/NiDE-gg/hlstatsx-community-edition) | Admin security, signed logs, PHP compatibility, query and CS2 work | Select compatible fixes; review DB97+ migrations separately. |
| [Royal Multi Gamers Laravel](https://github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel) | Laravel frontend, OSM maps, APIs, account and admin features | Active Laravel frontend at the site root. The reviewed DB96→DB97 migration and local compatibility/security changes are maintained separately from upstream history. |
| [A1mDev](https://github.com/A1mDev/hlstatsx-community-edition) | PDO/repository architecture and targeted application fixes | Use as a backend modernization reference; avoid a partial second database stack. |
| [lovasatt](https://github.com/lovasatt/hlstatsx-community-edition) | CS2, heatmaps, daemon and database changes | Review targeted fixes; stage schema and transport changes with data-preservation tests. |
| [SyntX34](https://github.com/SyntX34/hlstatsx-community-edition) | CS2 and SwiftlyS2 integrations | Adapt only where game transport and plugin interfaces match. |
| [SnipeZilla HLSTATS-2](https://github.com/SnipeZilla/HLSTATS-2) | Alternative daemon and Source 2 logging | Protocol reference only. README/core Perl headers state GPL-2.0-or-later; preserve notices and audit bundled assets if code is considered. No wholesale daemon replacement is integrated. |
| [SnipeZilla HLstatsZ web](https://github.com/SnipeZilla/hlstatsz-web) | Modern frontend, charts, integrations, and ten selectable/seasonal styles: Default, Dark, Light, Dark OLED, Matrix, Dust2, Strike, Fortress, Halloween, and Noel | Design reference. Its Apache-2.0 license and separate architecture require a compatibility review before direct code import. HLstatsZ themes are not integrated; active Laravel themes are administrator-selected globally. |
| [DNA-styx Windmill theme](https://github.com/DNA-styx/HLStatsX-Windmill-Theme) | Responsive dashboard, light/dark palettes, maps, and voice widgets | Design reference; no tracked project license was found. Keep implementations independent and preserve current Laravel/game behavior. The Windmill dashboard is not integrated. |

The current source pins, merge bases, and license notes live in [`upstream-sources.json`](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/docs/upstream-sources.json). The [feature matrix](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/docs/upstream-feature-matrix.md) records what the current code covers, while the [review record](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/docs/curated-edition-upstream-review.md) records specific decisions. Update those records when an integration lands or a source changes materially.

## Themes

The active Laravel frontend ships six built-in themes through its theme manager. HLstatsZ web and Windmill remain design references; their theme packages are not imported. Direct reuse requires a compatible license review and must preserve Laravel route, accessibility, and game-page behavior. The current work does not claim full visual parity with either project.
