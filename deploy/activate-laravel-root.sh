#!/usr/bin/env bash
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
  echo "Run through sudo." >&2
  exit 2
fi
if [[ $# -ne 3 ]]; then
  echo "Usage: pull.sh ARTIFACT SHA256 RELEASE_ID" >&2
  exit 2
fi

artifact=$1
expected_sha=$2
release_id=$3
root=/opt/hlstatsx-laravel
release="$root/releases/$release_id"
current="$root/current"
shared="$root/shared"
scripts=/opt/hlstatsx/scripts
webroot=/var/www/html
origin=https://stats.lamateam.eu
stage=''
cookie_file=''
backup_dir=''

cleanup() {
  [[ -z $cookie_file ]] || rm -f -- "$cookie_file"
  [[ -z $stage ]] || rm -rf -- "$stage"
  [[ -z $backup_dir ]] || rm -rf -- "$backup_dir"
  rm -f -- "$artifact"
}
trap cleanup EXIT

[[ $artifact == /tmp/hlstatsx-*.tar.gz ]] || { echo "Artifact must be under /tmp." >&2; exit 2; }
[[ $expected_sha =~ ^[a-f0-9]{64}$ ]] || { echo "Invalid artifact digest." >&2; exit 2; }
[[ $release_id =~ ^[A-Za-z0-9._-]+$ ]] || { echo "Invalid release id." >&2; exit 2; }
[[ -s $artifact ]] || { echo "Missing release artifact." >&2; exit 2; }
echo "$expected_sha  $artifact" | sha256sum --check --status || { echo "Artifact checksum mismatch." >&2; exit 2; }
[[ ! -e $release ]] || { echo "Release already exists: $release_id" >&2; exit 2; }
[[ -s $shared/.env ]] || { echo "Missing protected Laravel runtime environment." >&2; exit 2; }
[[ -d $webroot && ! -L $webroot ]] || { echo "Unexpected live web-root type." >&2; exit 2; }
[[ -d $scripts ]] || { echo "Daemon scripts directory is missing." >&2; exit 2; }

while IFS= read -r member; do
  case "$member" in
    /*|..|../*|*/../*|*/..)
      echo "Unsafe path in release archive." >&2
      exit 2
      ;;
  esac
done < <(tar -tzf "$artifact")

stage=$(mktemp -d "$root/releases/.incoming-$release_id.XXXXXX")
tar --no-same-owner --no-same-permissions -xzf "$artifact" -C "$stage"
app="$stage/app"
[[ -s $app/artisan && -s $app/vendor/autoload.php && -s $app/public/index.php && -s $app/public/build/manifest.json ]] || {
  echo "Release artifact is incomplete." >&2
  exit 2
}
[[ -d $stage/scripts && -s $stage/hlstatsx-laravel.conf && -s $stage/hlstats.service-override.conf ]] || {
  echo "Release deployment files are incomplete." >&2
  exit 2
}
php -r 'exit(version_compare(PHP_VERSION, "8.5.12", ">=") ? 0 : 1);' || {
  echo "Production PHP is below 8.5.12." >&2
  exit 2
}
php -m | grep -qi '^pdo_mysql$' || { echo "pdo_mysql is unavailable." >&2; exit 2; }
ln -s "$shared/.env" "$app/.env"

python3 - "$shared/.env" <<'PY'
from pathlib import Path
import re, sys

lines = Path(sys.argv[1]).read_text().splitlines()
values = {}
for line in lines:
    match = re.match(r"^([A-Z0-9_]+)=(.*)$", line)
    if match:
        values[match.group(1)] = match.group(2).strip().strip("'\"")
if values.get("SESSION_SECURE_COOKIE", "").lower() != "true":
    raise SystemExit("SESSION_SECURE_COOKIE must be true before root activation")
if not values.get("TRUSTED_PROXIES") or values["TRUSTED_PROXIES"] == "*":
    raise SystemExit("TRUSTED_PROXIES must be a non-empty explicit allowlist")
if sum(line.startswith("APP_URL=") for line in lines) != 1:
    raise SystemExit("Expected exactly one APP_URL in protected Laravel environment")
PY

(cd "$app" && php artisan route:list --json --no-ansi --no-interaction >/dev/null 2>&1) || {
  echo "Laravel route registration preflight failed." >&2
  exit 2
}

read -r old_daemon_digest new_daemon_digest < <(python3 - "$scripts" "$stage/scripts" <<'PY'
from hashlib import sha256
from pathlib import Path
import sys

live, source = map(Path, sys.argv[1:])
paths = sorted(
    path.relative_to(source)
    for path in source.rglob("*")
    if path.is_file() and path.name != "hlstats.conf"
)

def digest(root):
    result = sha256()
    for relative in paths:
        result.update(relative.as_posix().encode())
        result.update(b"\0")
        path = root / relative
        if not path.is_file():
            result.update(b"<missing>")
        else:
            with path.open("rb") as stream:
                for chunk in iter(lambda: stream.read(1024 * 1024), b""):
                    result.update(chunk)
        result.update(b"\0")
    return result.hexdigest()

print(digest(live), digest(source))
PY
)

# Keep generated game images and user-created image data outside release trees.
install -d -o root -g www-data -m 0750 "$shared/hlstatsimg"
rsync -a "$webroot/hlstatsimg/" "$shared/hlstatsimg/"
rsync -a --ignore-existing "$app/public/hlstatsimg/" "$shared/hlstatsimg/"
if [[ -f $webroot/favicon.ico && ! -e $app/public/favicon.ico ]]; then
  install -o root -g www-data -m 0644 "$webroot/favicon.ico" "$app/public/favicon.ico"
fi
rm -rf -- "$app/public/hlstatsimg"
ln -s "$shared/hlstatsimg" "$app/public/hlstatsimg"
[[ -d $webroot/.well-known/acme-challenge ]] || install -d -o root -g www-data -m 0755 "$webroot/.well-known/acme-challenge"

find "$app" -type d -exec chmod 0750 {} +
find "$app" -type f -exec chmod 0640 {} +
chmod 0750 "$app/artisan"
chown -R root:www-data "$app"
rm -rf -- "$app/storage"
ln -s "$shared/storage" "$app/storage"
install -d -o root -g www-data -m 0750 "$shared/storage/framework/cache/data" "$shared/storage/framework/sessions" "$shared/storage/framework/views" "$shared/storage/logs"

# Validate expected Apache edits before writing any live configuration.
backup_dir=$(mktemp -d /tmp/hlstatsx-apache.XXXXXX)
cp -a /etc/apache2/sites-enabled/default-ssl.conf "$backup_dir/default-ssl.conf"
cp -a /etc/apache2/sites-enabled/000-preserved-http.conf "$backup_dir/000-preserved-http.conf"
cp -a /etc/apache2/conf-available/hlstatsx-laravel.conf "$backup_dir/hlstatsx-laravel.conf"

python3 <<'PY'
from pathlib import Path

root = Path("/opt/hlstatsx-laravel/current/public")
changes = {
    Path("/etc/apache2/sites-enabled/default-ssl.conf"): [
        ("DocumentRoot /var/www/html", f"DocumentRoot {root}"),
        ("<Directory /var/www/html>", f"<Directory {root}>"),
    ],
    Path("/etc/apache2/sites-enabled/000-preserved-http.conf"): [
        ("DocumentRoot /var/www/html", f"DocumentRoot {root}"),
    ],
}
for path, pairs in changes.items():
    text = path.read_text()
    for old, new in pairs:
        if old in text:
            if text.count(old) != 1:
                raise SystemExit(f"Expected one {old!r} in {path}")
            text = text.replace(old, new)
        elif text.count(new) != 1:
            raise SystemExit(f"Expected one existing or target {new!r} in {path}")
    temporary = path.with_name(path.name + ".laravel-new")
    temporary.write_text(text)
    temporary.chmod(path.stat().st_mode & 0o777)
PY

install -o root -g root -m 0644 "$stage/hlstatsx-laravel.conf" /etc/apache2/conf-available/hlstatsx-laravel.conf
install -o root -g root -m 0644 /etc/apache2/sites-enabled/default-ssl.conf.laravel-new /etc/apache2/sites-enabled/default-ssl.conf
install -o root -g root -m 0644 /etc/apache2/sites-enabled/000-preserved-http.conf.laravel-new /etc/apache2/sites-enabled/000-preserved-http.conf
rm -f /etc/apache2/sites-enabled/*.laravel-new

if ! apachectl -t; then
  cp -a "$backup_dir/default-ssl.conf" /etc/apache2/sites-enabled/default-ssl.conf
  cp -a "$backup_dir/000-preserved-http.conf" /etc/apache2/sites-enabled/000-preserved-http.conf
  cp -a "$backup_dir/hlstatsx-laravel.conf" /etc/apache2/conf-available/hlstatsx-laravel.conf
  echo "Apache configuration preflight failed; prior configuration restored." >&2
  exit 2
fi

mv "$app" "$release"
ln -s "$release" "$root/current.next"
mv -Tf "$root/current.next" "$current"

python3 - "$shared/.env" <<'PY'
from pathlib import Path
import os, stat, sys

path = Path(sys.argv[1])
before = path.stat()
lines = path.read_text().splitlines(keepends=True)
matches = [i for i, line in enumerate(lines) if line.startswith("APP_URL=")]
if len(matches) != 1:
    raise SystemExit("Expected exactly one APP_URL in protected Laravel environment")
i = matches[0]
ending = "\n" if lines[i].endswith("\n") else ""
lines[i] = "APP_URL=https://stats.lamateam.eu" + ending
asset_matches = [i for i, line in enumerate(lines) if line.startswith("ASSET_URL=")]
if len(asset_matches) > 1:
    raise SystemExit("Expected at most one ASSET_URL in protected Laravel environment")
if asset_matches:
    i = asset_matches[0]
    ending = "\n" if lines[i].endswith("\n") else ""
    lines[i] = "ASSET_URL=https://stats.lamateam.eu" + ending
temporary = path.with_name(".env.laravel-new")
temporary.write_text("".join(lines))
os.chmod(temporary, stat.S_IMODE(before.st_mode))
os.chown(temporary, before.st_uid, before.st_gid)
os.replace(temporary, path)
PY

install -D -o root -g root -m 0644 "$stage/hlstats.service-override.conf" /etc/systemd/system/hlstats.service.d/override.conf
systemctl daemon-reload
systemctl reload apache2
systemctl is-active --quiet apache2

resolve=(--resolve stats.lamateam.eu:443:127.0.0.1)
for path in / /search /players /clans /servers /weapons /maps /api/v1/map/markers /sitemap.xml /robots.txt /admin/login /favicon.ico /hlstatsimg/flags/us.gif /hlstatsimg/games/cstrike/game.png; do
  code=$(curl --silent --show-error --insecure --max-time 15 "${resolve[@]}" -o /dev/null -w '%{http_code}' "$origin$path") || code=000
  [[ $code =~ ^[23][0-9][0-9]$ ]] || { echo "Root smoke failed for $path ($code)." >&2; exit 1; }
done

legacy=$(curl --silent --show-error --insecure --max-time 15 "${resolve[@]}" -o /dev/null -w '%{http_code}\t%{redirect_url}' "$origin/hlstats.php?mode=players")
[[ $legacy == $'301\thttps://stats.lamateam.eu/players' ]] || { echo "Legacy URL redirect failed." >&2; exit 1; }
old_prefix=$(curl --silent --show-error --insecure --max-time 15 "${resolve[@]}" -o /dev/null -w '%{http_code}\t%{redirect_url}' "$origin/laravel/")
[[ $old_prefix == $'301\thttps://stats.lamateam.eu/' ]] || { echo "Old /laravel prefix redirect failed." >&2; exit 1; }
admin_redirect=$(curl --silent --show-error --insecure --max-time 15 "${resolve[@]}" -o /dev/null -w '%{redirect_url}' "$origin/admin/admin-users")
[[ $admin_redirect == https://stats.lamateam.eu/* ]] || { echo "Admin guest redirect is not canonical HTTPS." >&2; exit 1; }
cookie_file=$(mktemp)
curl --silent --show-error --insecure --max-time 15 "${resolve[@]}" -D "$cookie_file" -o /dev/null "$origin/admin/login"
grep -Eiq '^Set-Cookie: laravel-session=.*; secure([;]|$)' "$cookie_file" || { echo "Laravel session cookie is not Secure." >&2; exit 1; }

rsync -a --exclude=hlstats.conf "$stage/scripts/" "$scripts/"
if [[ $new_daemon_digest != "$old_daemon_digest" ]]; then
  cd "$scripts"
  chmod +x hlstats-awards.pl hlstats.pl hlstats-resolve.pl run_hlstats
  chmod 0640 hlstats.conf
  chown -R ubuntu:ubuntu "$scripts"
  systemctl restart hlstats.service
  systemctl is-active --quiet hlstats.service
  sudo -u ubuntu "$scripts/run_hlstats" status 27500
fi

# Remove known legacy frontend files only. Keep non-application root data untouched;
# none of it is served after the Apache DocumentRoot change above.
for name in \
  .htaccess adminer-en.php autocomplete.php clantag.php config.example.php config.php \
  css exit favicon.ico hlstats.css hlstats.php includes index.php ingame.php pages \
  robots.txt show_graph.php sig.php status.php styles trend_graph.php updater; do
  rm -rf -- "$webroot/$name"
done
echo "PASS Laravel root is active; old PHP frontend removed; release $release_id"
