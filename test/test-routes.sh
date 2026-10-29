#!/bin/sh
set -eu

echo "[test-routes] Laravel root smoke"
command -v curl >/dev/null 2>&1 || apk add --no-cache curl
BASE_URL=${HLX_TEST_BASE_URL:-http://laravel:8080}
PATHS='
/
/search
/players
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
/favicon.ico
/hlstatsimg/flags/us.gif
/hlstatsimg/games/cstrike/game.png
'
echo "$PATHS" | awk NF | while IFS= read -r path; do
    status=$(curl --silent --show-error --max-time 20 -o /dev/null -w '%{http_code}' "$BASE_URL$path") || status=000
    case "$status" in
        2??|3??) echo "PASS $path $status" ;;
        *) echo "FAIL $path $status" >&2; exit 1 ;;
    esac
done

legacy=$(curl --silent --show-error --max-time 20 -o /dev/null -w '%{http_code}' "$BASE_URL/hlstats.php?mode=players")
case "$legacy" in 301|302|308) echo "PASS legacy URL redirects ($legacy)" ;; *) echo "FAIL legacy URL redirect ($legacy)" >&2; exit 1 ;; esac
