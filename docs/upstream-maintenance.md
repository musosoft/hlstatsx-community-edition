# Upstream maintenance and curation workflow

This file, [`upstream-sources.json`](upstream-sources.json), and the [upstream feature matrix](upstream-feature-matrix.md) are the durable curation memory. They are committed with the product so a new session or machine does not depend on shell history, temporary clones, or a particular agent context.

## Source policy

- The active product is the GPL-2.0 Laravel frontend plus the retained Perl daemon, SourceMod integrations, and Curated Edition database assets. The legacy PHP frontend is removed from active source and deployment; Git history remains available for reference. Preserve daemon protocol, CSS-only ATB behavior, plugins, image data, and local improvements.
- `upstream-sources.json` pins the last-checked branch tip for every source and records branch, license, import strategy, and known constraints. A newer commit is a review candidate, not an automatic merge request. A checked tip is not a claim that every historical feature or commit has been exhaustively reviewed.
- The repo's canonical historical upstream and GPL community forks can share history; confirm with `git merge-base` before using Git commit operations. Royal Laravel, HLstatsZ web, HLSTATS-2, and Windmill have separate histories/architectures. Royal's full GPL-2.0 Laravel application is maintained as a separate `src/laravel` component pinned to its exact SHA; never merge its history. Laravel migrations own the official DB96→DB97 transition. DB96 seed files and legacy history remain for existing installations; the old PHP updater is not part of the active product.
- Respect licensing: review repository metadata and file-level notices before importing code or assets into this GPL-2.0-or-later project. Missing GitHub license metadata is not decisive when relevant source files and project documentation carry an explicit license; preserve notices and audit bundled dependencies/assets separately. Repositories with no applicable license grant remain research/design references.
- `upstream-sources.json` records the shared `merge_base_sha` for code forks where GitHub confirms common history. Those forks are still divergent: use the common base to review commit ranges, not as permission to merge an entire branch. Royal Laravel and visual/protocol references have no compatible root-tree merge path.

## Check for upstream movement

From a clean checkout, run:

```sh
scripts/check-upstreams.sh
```

The script uses GitHub's compare API to print commits after each pinned checked SHA. It does not clone/fetch large asset repositories, touch the worktree, change remotes, stage files, merge, cherry-pick, or advance pins. If history diverged, the pin is unavailable, or API access/rate limits block a comparison, it reports that for manual investigation. It needs `gh` (authenticated for reliable rate limits), `jq`, and network access.

## GitHub branch and merge model

- Keep only `main` (release baseline) and `dev` (curated integration) as long-lived branches. Use short-lived feature branches and pull requests into `dev`; promote a tested release from `dev` to `main` through a pull request.
- The `upstream` Git remote tracks StartersClan. The other shared-history fork URLs and their last-reviewed common bases are in the manifest. Do not add a permanent local branch for every fork.
- Before using commits from a shared-history source, fetch its candidate ref and compare it with `dev` from the common base:

  ```sh
  git fetch --no-tags https://github.com/OWNER/REPO.git BRANCH
  git merge-base origin/dev FETCH_HEAD
  git log --left-right --cherry-pick --oneline origin/dev...FETCH_HEAD
  ```

- If every commit in a coherent range is wanted, use a reviewed PR that preserves the source commit graph. If only isolated changes are wanted, cherry-pick them with `-x` and record their original SHAs in the PR and review record. Never mark an unreviewed divergent branch as merged just to make future Git operations look clean.
- For unrelated sources, keep source versions pinned. Royal Laravel is the explicit case where the complete application is maintained as a separate component with narrow local patches and exact upstream provenance; other unrelated sources remain feature references unless separately approved.
- The scheduled Upstream Watch checks source pins and opens a review issue. It does not update pins or merge code automatically.

## Container runtime maintenance

- Keep Laravel, heatmap PHP, Perl, and Alpine base images on explicit maintained version tags in `src/laravel/Dockerfile`, `Dockerfile.heatmaps`, `Dockerfile.daemon`, and Compose support services. The example and development Compose files must stay aligned where they share a build/runtime dependency.
- Dependabot checks Docker image references and GitHub Actions weekly. Compose/CI use versioned MySQL 26.10, with 9.7.4 as a tested alternative. Historical DB96 updater checks are documented; current tests exercise Laravel routes, awards, and heatmaps on a migrated disposable database. The Laravel image builds on PHP 8.5.12 and fails closed until DB97. The live production database is already DB97. PHP extension version changes require compatibility checks; do not assume Dependabot tracks arbitrary installer URLs.
- Integration tests run under the dedicated Compose project `hlstatsx-community-edition-test` by default (override with `HLX_TEST_PROJECT_NAME`), so a failed test cannot stop the operator's normal stack.
- MySQL 26.10 is the selected versioned Compose/CI image; MySQL 9.7.4 LTS is a tested alternative. DB96 remains the official install input and Laravel migrations own DB96→DB97. Historical database-updater checks remain in the project record, while active integration tests cover Laravel routes, the daemon, awards, and heatmaps. Runtime upgrades must not silently imply a database migration.

## Review and integrate

1. Start from a clean status and confirm the target branch, remotes, and upstream branch tips. Never mix unrelated worktree edits into an upstream integration.
2. For common-history code forks, compare from the recorded merge base and inspect each commit and its tests. Use a PR for a coherent reviewed range when its full behavior is wanted; cherry-pick isolated commits only when the original SHA is recorded in the PR and review log. For unrelated sources, use a temporary clone or isolated worktree; adapt behavior into a named boundary rather than merging root trees.
3. Record each candidate in `curated-edition-upstream-review.md`: source URL + full commit SHA, affected local files, behavior/schema/protocol impact, license decision, accept/reject/defer reason, and verification.
4. Keep each coherent feature or fix in its own Conventional Commit. Include the upstream commit SHA in the commit body and update the curation record in the same change. Never import release/version or migration chains wholesale.
5. Prove the local invariants before merging: preserve data and custom settings; make schema changes retryable and reversible; keep CSS-only ATB out of other games; test daemon/proxy packet compatibility; compile relevant SourceMod plugins; validate PHP/Perl and Compose paths.
6. After inspecting a new range and recording accept/reject/defer decisions, update only that source's `checked_sha` and review note. Do not advance the pin just because the branch moved. A rejected/deferred range still counts as reviewed only if the reason and SHA range are documented.
7. Push feature branches to `origin`, open/review a PR, and merge through the normal branch workflow. `main` is the release baseline; `dev` is the curated integration line. Do not force-push shared branches or push directly to either branch as a shortcut. The weekly Upstream Watch workflow reports new commits and opens one review issue; it never merges automatically.

## Current curation checkpoint

The branch layout is `main` for the release baseline and `dev` for curated integration. `dev` descends from the former baseline commit `4b2d7c4f9ccd085da1c207ed831d8740c1ba3c81` and includes curation commits beginning at `97b367c`. DB96 is the shared legacy schema base; Laravel migrations target DB97 for the new frontend. Archived tags preserve the old owner-work branch tips from the October 2026 cleanup. Source decisions and verification gaps are recorded in [`curated-edition-upstream-review.md`](curated-edition-upstream-review.md).

The Laravel frontend is live at the stats domain root; the old `/laravel` URL redirects to `/`. The activation helper verifies origin routes, legacy URL redirects, HTTPS admin redirects, secure session cookies, and static game assets before it removes known PHP frontend files. Public route checks run after activation; Cloudflare blocks are failures, not success. Future updates are built locally and transferred by `tools/remote-pull.sh`; the server keeps its protected `.env`, persistent images, and daemon configuration. The separate Perl daemon and CSS-only ATB protocol remain active. DB compatibility is guaranteed only for this repository's official DB96 migration input; other forks are best-effort.
