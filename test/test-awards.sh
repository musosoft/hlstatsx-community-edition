#!/bin/sh
set -eu

container=$(docker compose --profile laravel -f "${HLX_TEST_ROOT_COMPOSE_FILE:-../docker-compose.yml}" -f docker-compose.yml -p "${HLX_TEST_PROJECT_NAME:-hlstatsx-community-edition-test}" ps -q awards)
[ -n "$container" ] || { echo "Awards container is not running" >&2; exit 1; }
docker exec -i "$container" sh -c /awards.sh
