# Upstream maintenance and curation workflow

This file and [`upstream-sources.json`](upstream-sources.json) are the durable curation memory. They are committed with the product so a new session or machine does not depend on shell history, temporary clones, or a particular agent context.

## Source policy

- The product base is the GPL-2.0 legacy PHP/Perl project. Keep its daemon, DB96 schema, themes, CSS-only ATB behavior, plugin APIs, deployment defaults, and local improvements unless a reviewed change proves compatibility.
- `upstream-sources.json` pins the last-checked branch tip for every source and records branch, license, import strategy, and known constraints. A newer commit is a review candidate, not an automatic merge request. A checked tip is not a claim that every historical feature or commit has been exhaustively reviewed.
- The repo's canonical legacy upstream and GPL community forks can share history; confirm with `git merge-base` before using Git commit operations. Laravel, HLstatsZ web, HLSTATS-2, and Windmill are separate architectures/repositories: inspect as read-only sources and adapt principles/features, never merge their histories.
- Respect licensing: Apache-2.0 and GPL-2-only are not treated as directly interchangeable here; repositories with no declared license are research/design references only. Recheck licenses before copying any individual file or asset.

## Check for upstream movement

From a clean checkout, run:

```sh
scripts/check-upstreams.sh
```

The script uses GitHub's compare API to print commits after each pinned checked SHA. It does not clone/fetch large asset repositories, touch the worktree, change remotes, stage files, merge, cherry-pick, or advance pins. If history diverged, the pin is unavailable, or API access/rate limits block a comparison, it reports that for manual investigation. It needs `gh` (authenticated for reliable rate limits), `jq`, and network access.

## Review and integrate

1. Start from a clean status and confirm the target branch, remotes, and upstream branch tips. Never mix unrelated worktree edits into an upstream integration.
2. For common-history code forks, compare from the merge base and inspect each commit and its tests. For unrelated sources, use a temporary clone or isolated worktree for inspection; adapt only the smallest compatible behavior into the legacy architecture.
3. Record each candidate in `curated-edition-upstream-review.md`: source URL + full commit SHA, affected local files, behavior/schema/protocol impact, license decision, accept/reject/defer reason, and verification.
4. Keep each coherent feature or fix in its own Conventional Commit. Include the upstream commit SHA in the commit body and update the curation record in the same change. Never import release/version or migration chains wholesale.
5. Prove the local invariants before merging: preserve data and custom settings; make schema changes retryable and reversible; keep CSS-only ATB out of other games; test daemon/proxy packet compatibility; compile relevant SourceMod plugins; validate PHP/Perl and Compose paths.
6. After inspecting a new range and recording accept/reject/defer decisions, update only that source's `checked_sha` and review note. Do not advance the pin just because the branch moved. A rejected/deferred range still counts as reviewed only if the reason and SHA range are documented.
7. Push feature branches to `origin`, open/review a PR, and merge through the normal protected branch workflow. Do not force-push shared branches or push directly to `dev`/`master` as a shortcut.

## Current curation checkpoint

The curated work is on `community/upstream-security-20261025`, based on local canonical commit `4b2d7c4f9ccd085da1c207ed831d8740c1ba3c81` (also `origin/dev` at the last check). The first curation checkpoint is commit `97b367c` (`feat: curate community fixes for 1.13.0-CE`). The retained local ATB chain through `b757551` predates this branch. DB96 is the current curated schema ceiling. The specific source decisions and pending verification gaps are recorded in [`curated-edition-upstream-review.md`](curated-edition-upstream-review.md).

At this checkpoint, OSM/Leaflet, sitemap, opt-in CS:S log testing, responsive themes, security/PHP compatibility work, DB96 options, selected daemon/ATB changes, and a read-only map-marker JSON endpoint are committed in `97b367c` plus subsequent feature commits. The CS:S image does not contain the curated plugin. Laravel's account, editable-page, and remaining API features remain separate candidates; do not interpret this checkpoint as full feature parity or Laravel production readiness.
