#!/bin/sh
set -e

mkdir -p database storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
touch database/database.sqlite

if [ -z "$APP_KEY" ]; then
    APP_KEY=$(php artisan key:generate --show --no-interaction)
    export APP_KEY
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
