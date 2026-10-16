#!/bin/sh
set -e

cd /var/www/html

# Directorios que Laravel necesita en tiempo de ejecución (storage es un volumen)
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

if [ "$APP_ENV" = "production" ]; then
    php artisan optimize
fi

exec "$@"
