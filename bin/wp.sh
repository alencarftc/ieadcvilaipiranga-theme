#!/usr/bin/env bash
# Atalho para o wp-cli do ambiente local. Ex.: ./bin/wp.sh plugin list
cd "$(dirname "$0")/.."
exec docker compose run --rm wpcli wp "$@"
