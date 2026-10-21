#!/bin/sh
set -eu

umask 027

ROOT="${ROOT:-/app}"

mkdir -p \
    "$ROOT/storage/app/private" \
    "$ROOT/storage/app/public" \
    "$ROOT/storage/framework/cache/data" \
    "$ROOT/storage/framework/sessions" \
    "$ROOT/storage/framework/views" \
    "$ROOT/storage/logs" \
    "$ROOT/bootstrap/cache"

exec "$@"
