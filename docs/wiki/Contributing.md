# Contributing and upstream updates

Contributions from every HLstatsX fork are welcome. The project keeps source attribution and integration decisions in version control so maintainers can bring future upstream fixes forward.

## Check upstreams

Run from a clean checkout:

```sh
scripts/check-upstreams.sh
```

The script compares the pinned refs in [`docs/upstream-sources.json`](https://github.com/musosoft/hlstatsx-community-edition/blob/dev/docs/upstream-sources.json) with their current upstream branches. New commits are review candidates, not automatic merges. Record an updated pin only after reviewing the range.

The project keeps `main` as the release baseline and `dev` as the integration branch. Use short-lived branches and pull requests into `dev`; promote tested releases to `main` through a pull request. Do not create permanent local branches for every fork.

## Review and integrate

For each change, record the repository, full commit SHA, affected behavior, license, schema/protocol impact, and decision. The source manifest records the known common merge base for shared-history code forks. Compare both sides from that point before deciding whether a reviewed range can merge as a PR. Do not merge an entire divergent fork by default. Use a separate checkout for Laravel, HLstatsZ, Windmill, or another unrelated repository; adapt behavior rather than merging unrelated histories.

For a shared-history source, fetch and compare without creating a local branch:

```sh
git fetch --no-tags https://github.com/OWNER/REPO.git BRANCH
git merge-base origin/dev FETCH_HEAD
git log --left-right --cherry-pick --oneline origin/dev...FETCH_HEAD
```

Merge a reviewed coherent range through a PR when you want its history intact. Cherry-pick isolated fixes with `-x`, then cite the original source SHA in the review. Never force-push shared branches or use a fake merge to suppress old source commits.

Keep changes small enough to test. Run the relevant PHP, Perl, plugin, database migration, Compose, and browser checks. Preserve custom settings and data, make migrations retryable, and include rollback instructions for changes that alter storage or transport. Update the feature matrix and source review with the implementation.

Open a pull request against `dev`. Do not force-push shared branches. Keep `main` as the release baseline and do not deploy or modify production databases from a development change.
