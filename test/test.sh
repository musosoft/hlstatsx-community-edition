#!/bin/sh
set -eu

TEST=${1:-} # Test environment
UP=${2:-} # Whether to docker compose up and down the test stack
CACHE=${3:-} # Whether to override with docker-compose.build.yml
PROJECT_NAME=${HLX_TEST_PROJECT_NAME:-hlstatsx-community-edition-test}
export HLX_TEST_PROJECT_NAME="$PROJECT_NAME"
export LARAVEL_APP_KEY=${LARAVEL_APP_KEY:-base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=}
export LARAVEL_DB_USER=${LARAVEL_DB_USER:-hlstatsxce}
export LARAVEL_DB_PASSWORD=${LARAVEL_DB_PASSWORD:-hlstatsxce}
if [ "$TEST" = 'prod' ]; then
    export HLX_TEST_ROOT_COMPOSE_FILE=../docker-compose.example.yml
    export LARAVEL_HOST_PORT=${LARAVEL_HOST_PORT:-18081}
else
    export HLX_TEST_ROOT_COMPOSE_FILE=../docker-compose.yml
fi

# Validation and normalization
if ! echo "$TEST" | grep -E '^(dev|prod)$' > /dev/null; then
    echo "Specify TEST as the first argument. E.g. 'dev', 'prod'"
    exit 1
fi
if [ -n "$CACHE" ]; then
    CACHE='-f docker-compose.build.yml'
fi

SCRIPT_DIR=$( cd "$( dirname "$0" )" && pwd )
export HLX_TEST_DIR="$SCRIPT_DIR"
ERR=
setup_test() {
    cd "$SCRIPT_DIR"
    if [ -n "$UP" ]; then
        setup
    fi
    docker compose --profile laravel -p "$PROJECT_NAME" up -d
    run
}
cleanup_test() {
    ERR=$?
    if [ -n "$UP" ]; then
        cleanup
    fi
    docker compose --profile laravel -p "$PROJECT_NAME" down
    if [ -z "$ERR" ] || [ "$ERR" = 0 ]; then
        echo "All tests succeeded"
    else
        echo "Some tests failed"
        echo "Exit code: $ERR"
        exit "$ERR"
    fi
}
trap cleanup_test INT TERM EXIT

echo "Testing..."
if [ "$TEST" = 'dev' ]; then
    setup() {
        if [ -n "$CACHE" ]; then
            CACHE='-f docker-compose.build.yml'
        fi
        (cd .. && docker compose --profile laravel -p "$PROJECT_NAME" -f docker-compose.yml $CACHE up --build -d)
        wait_for_database
        migrate_laravel
    }
    run() {
        docker exec $( docker compose -p "$PROJECT_NAME" ps -q test-container-networking ) ./test-ready.sh
        docker exec $( docker compose -p "$PROJECT_NAME" ps -q test-container-networking ) ./test-routes.sh
        ./test-awards.sh
        ./test-heatmaps.sh
    }
    cleanup() {
        (cd .. && docker compose --profile laravel -p "$PROJECT_NAME" -f docker-compose.yml $CACHE stop)
    }
fi
if [ "$TEST" = 'prod' ]; then
    setup() {
        if [ -n "$CACHE" ]; then
            CACHE='-f docker-compose.example.build.yml'
        fi
        (cd .. && docker compose --profile laravel -p "$PROJECT_NAME" -f docker-compose.example.yml $CACHE up --build -d)
        wait_for_database
        migrate_laravel
    }
    run() {
        docker exec $( docker compose -p "$PROJECT_NAME" ps -q test-container-networking ) ./test-ready.sh
        docker exec $( docker compose -p "$PROJECT_NAME" ps -q test-container-networking ) ./test-routes.sh
        ./test-awards.sh
        ./test-heatmaps.sh
        docker exec $( docker compose -p "$PROJECT_NAME" ps -q test-host-networking ) ./test-endpoints.sh
    }
    cleanup() {
        (cd .. && docker compose --profile laravel -p "$PROJECT_NAME" -f docker-compose.example.yml $CACHE stop)
    }
fi
migrate_laravel() {
    container=$(docker compose --profile laravel -f "$HLX_TEST_ROOT_COMPOSE_FILE" -p "$PROJECT_NAME" ps -q laravel)
    docker exec "$container" php artisan migrate --force --isolated
}
wait_for_database() {
    container=$(docker compose --profile laravel -f "$HLX_TEST_ROOT_COMPOSE_FILE" -p "$PROJECT_NAME" ps -q laravel)
    attempt=0
    while [ "$attempt" -lt 90 ]; do
        if docker exec "$container" php -r '
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: 3306, getenv("DB_DATABASE"));
            new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        ' >/dev/null 2>&1; then
            return 0
        fi
        attempt=$((attempt + 1))
        sleep 2
    done
    echo "Laravel database did not become ready" >&2
    return 1
}
setup_test
