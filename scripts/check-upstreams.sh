#!/usr/bin/env bash
set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
manifest="$repo_root/docs/upstream-sources.json"

command -v jq >/dev/null || { echo "check-upstreams: jq is required" >&2; exit 2; }
command -v gh >/dev/null || { echo "check-upstreams: GitHub CLI (gh) is required" >&2; exit 2; }

while IFS=$'\t' read -r slug repo branch checked_sha strategy; do
	echo "== $repo ($branch; $strategy) =="
	if ! comparison="$(gh api "repos/$repo/compare/$checked_sha...$branch" 2>/dev/null)"; then
		echo "  GitHub comparison failed; check repository access, pin, and API rate limits" >&2
		continue
	fi
	status="$(jq -r '.status // "unknown"' <<<"$comparison")"
	if [[ "$status" != "ahead" && "$status" != "identical" ]]; then
		echo "  comparison status is $status; possible history rewrite or divergent history, inspect manually"
		continue
	fi
	count="$(jq -r '.ahead_by // 0' <<<"$comparison")"
	if [[ "$count" == 0 ]]; then
		echo "  no new commits"
	else
		echo "  $count new commit(s) since reviewed pin:"
		jq -r '.commits[]? | "    " + .sha[0:12] + " " + (.commit.message | split("\n")[0])' <<<"$comparison"
		if (( count > 250 )); then
			echo "  GitHub comparison listing may be capped at 250 commits; inspect the full range separately."
		fi
	fi
done < <(jq -r '.sources[] | [.slug,.repo,.branch,.checked_sha,.strategy] | @tsv' "$manifest")
