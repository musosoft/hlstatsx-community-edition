#!/bin/bash
set -eu

SCRIPT_DIR=$( cd "$( dirname "$0" )" && pwd )
FIXTURE=$(mktemp "${TMPDIR:-/tmp}/test-run-hlstats-start.XXXXXX")
trap 'rm -f "$FIXTURE"' EXIT

# Exercise the real command dispatcher with deterministic daemon outcomes.
# No daemon, database, PID files, or network access are needed.
cat > "$FIXTURE" <<'EOF'
read -ra RESULTS <<< "$DAEMON_RESULTS"
CALL=0
start_daemon() {
    local result=${RESULTS[$CALL]:-1}
    CALL=$((CALL + 1))
    return "$result"
}
check_port() { return 0; }
EOF
sed -n '/^# Daemon control case switcher/,$p' "$SCRIPT_DIR/../src/scripts/run_hlstats" >> "$FIXTURE"
grep -q '^# Daemon control case switcher' "$FIXTURE"

check_start() {
    local name=$1 results=$2 count=$3 expected_status=$4 expected_output=$5
    local output status=0
    output=$(DAEMON_RESULTS="$results" bash "$FIXTURE" start "$count") || status=$?
    if [ "$status" -ne "$expected_status" ] || [ "$output" != "$expected_output" ]; then
        printf 'FAIL: %s (exit %s, expected %s)\n%s\n' "$name" "$status" "$expected_status" "$output" >&2
        exit 1
    fi
    printf 'PASS: %s\n' "$name"
}

check_start 'single successful start' '0' 1 0 \
    'Daemon successfully started on port 27500'
check_start 'single already-running daemon' '2' 1 0 \
    'Daemon is already running on port 27500'
check_start 'multiple mixed outcomes preserve port increments' '0 2 0' 3 0 \
    $'Daemon successfully started on port 27500\nDaemon is already running on port 27501\nDaemon successfully started on port 27502'
check_start 'failed start remains a failure' '1' 1 1 \
    'CRITICAL ERROR: Unable to start daemon on port 27500'
check_start 'failure after success stops the loop' '0 1 0' 3 1 \
    $'Daemon successfully started on port 27500\nCRITICAL ERROR: Unable to start daemon on port 27501'
