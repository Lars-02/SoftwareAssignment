#!/bin/sh
set -e

if [ ! -d "vendor" ]; then
    echo "Running composer install..."
    composer install --no-dev --optimize-autoloader
fi

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

exec "$@"
