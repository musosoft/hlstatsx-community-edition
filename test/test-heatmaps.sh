#!/bin/sh
set -eu

container=$(docker compose --profile laravel -f "${HLX_TEST_ROOT_COMPOSE_FILE:-../docker-compose.yml}" -f docker-compose.yml -p "${HLX_TEST_PROJECT_NAME:-hlstatsx-community-edition-test}" ps -q heatmaps)
[ -n "$container" ] || { echo "Heatmaps container is not running" >&2; exit 1; }
docker exec -i "$container" php /heatmaps/generate.php
