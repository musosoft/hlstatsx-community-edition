#!/usr/bin/env bash
set -euo pipefail

if [[ ${1:-} != --apply-live ]]; then
  echo "Usage: $0 --apply-live" >&2
  exit 2
fi

repo=$(git rev-parse --show-toplevel)
branch=$(git -C "$repo" branch --show-current)
if [[ $branch != dev ]]; then
  echo "Refusing live deploy from branch '$branch'; expected dev." >&2
  exit 2
fi

host=ubuntu@130.61.89.164
identity=/home/muso/.ssh/netrunner.key
commit=$(git -C "$repo" rev-parse --short=12 HEAD)
release_id="$(date -u +%Y%m%dT%H%M%SZ)-$commit"
image="hlstatsx-laravel:$release_id"
work=$(mktemp -d /tmp/opencode/hlstatsx-live.XXXXXX)
container=''
cleanup() {
  if [[ -n $container ]]; then docker rm -f "$container" >/dev/null 2>&1 || true; fi
  rm -rf -- "$work"
}
trap cleanup EXIT

if [[ -n $(git -C "$repo" status --porcelain) ]]; then
  echo "Building current dev worktree (including uncommitted changes) at $commit."
fi

docker build --pull=false -t "$image" "$repo/src/laravel"
container=$(docker create "$image")
mkdir -p "$work/bundle"
docker cp "$container:/app" "$work/bundle/app"
cp -a "$repo/src/scripts" "$work/bundle/scripts"
cp "$repo/deploy/apache/hlstatsx-laravel.conf" "$work/bundle/hlstatsx-laravel.conf"
cp "$repo/deploy/systemd/hlstats.service.d/override.conf" "$work/bundle/hlstats.service-override.conf"
cp "$repo/deploy/activate-laravel-root.sh" "$work/bundle/activate-laravel-root.sh"

if [[ -e "$work/bundle/app/.env" ]]; then
  echo "Build artifact unexpectedly contains an .env file; refusing deploy." >&2
  exit 2
fi
tar -C "$work/bundle" -czf "$work/$release_id.tar.gz" app scripts \
  hlstatsx-laravel.conf hlstats.service-override.conf
sha=$(sha256sum "$work/$release_id.tar.gz" | cut -d ' ' -f1)

scp -q -i "$identity" -o BatchMode=yes -o ConnectTimeout=8 \
  "$work/$release_id.tar.gz" "$host:/tmp/hlstatsx-$release_id.tar.gz"
scp -q -i "$identity" -o BatchMode=yes -o ConnectTimeout=8 \
  "$work/bundle/activate-laravel-root.sh" "$host:/tmp/activate-laravel-root-$release_id.sh"
ssh -i "$identity" -o BatchMode=yes -o ConnectTimeout=8 "$host" \
  "sudo -n install -o root -g root -m 0750 /tmp/activate-laravel-root-$release_id.sh /home/ubuntu/pull.sh && sudo -n /home/ubuntu/pull.sh /tmp/hlstatsx-$release_id.tar.gz $sha $release_id"

"$repo/tools/smoke-laravel-routes.sh" https://stats.lamateam.eu

echo "Laravel root deployment passed origin and public route smoke checks ($release_id)."
