#!/usr/bin/env bash

set -e

ENVIRONMENT="${APP_ENV:-dev}"

if [[ "$ENVIRONMENT" == "dev" ]]
then
    echo "== Clearing cache and installing composer =="
    # Delete temp, it might be incompatible with current changes
    rm -rf var/cache/*

    # Always have up to date dependencies
    composer install --no-interaction
fi

## Database setup

if [[ "$ENVIRONMENT" == "dev" ]]; then
    wait-for-it ${DATABASE_HOST:-mariadb}:${DATABASE_PORT:-3306} --timeout=15
fi

echo "== Setting 777 permission to var/ =="
mkdir -p var/cache
time chmod -R 777 var
