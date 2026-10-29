#!/usr/bin/env bash
set -euo pipefail

base_url=${1:-https://stats.lamateam.eu}
base_url=${base_url%/}
paths=(
  /
  /search
  /players
  /players/1
  /players/1/events
  /players/1/sessions
  /players/1/awards
  /players/1/chat
  /clans
  /servers
  /weapons
  /maps
  /chat
  /countries
  /awards
  /roles
  /help
  /privacy
  /legal
  /actions
  /bans
  /voicecomm
  /ingame/players
  /ingame/motd
  /api/v1/live-feed
  /api/v1/map/markers
  /sitemap.xml
  /robots.txt
  /admin/login
)

failed=0
for path in "${paths[@]}"; do
  status=$(curl --silent --show-error --max-time 15 -o /dev/null -w '%{http_code}' "$base_url$path") || status=000
  if [[ $status =~ ^[23][0-9][0-9]$ ]]; then
    printf 'PASS %s %s\n' "$path" "$status"
  else
    printf 'FAIL %s %s\n' "$path" "$status" >&2
    failed=1
  fi
done

admin_redirect=$(curl --silent --show-error --max-time 15 -o /dev/null -w '%{redirect_url}' "$base_url/admin/admin-users") || admin_redirect=''
if [[ $admin_redirect == https://* ]]; then
  printf 'PASS admin guest redirect uses HTTPS\n'
else
  printf 'FAIL admin guest redirect is not HTTPS\n' >&2
  failed=1
fi

cookie_security=$(curl --silent --show-error --max-time 15 -D - -o /dev/null "$base_url/admin/login" \
  | awk '{ line = tolower($0); if (line ~ /^set-cookie: laravel-session=/) { secure = (line ~ /; secure([;]|$)/); found = 1 } } END { if (found && secure) print "secure"; else print "insecure" }') || cookie_security='insecure'
if [[ $cookie_security == secure ]]; then
  printf 'PASS admin session cookie is Secure\n'
else
  printf 'FAIL admin session cookie is not Secure\n' >&2
  failed=1
fi

exit "$failed"
