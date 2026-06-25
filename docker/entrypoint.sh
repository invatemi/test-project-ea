#!/bin/sh
set -e

if [ ! -d vendor ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

php artisan migrate --force --no-interaction
php artisan db:seed --class=Database\\Seeders\\WbApiSeeder --force --no-interaction 2>/dev/null || true

mkdir -p /var/log/supervisor

SUPERVISOR_CONF=/etc/supervisor/supervisord.conf
cp /var/www/html/docker/supervisord.conf "$SUPERVISOR_CONF"

if [ "${RUN_QUEUE_WORKER}" = "true" ]; then
    sed -i 's/autostart=false/autostart=true/' "$SUPERVISOR_CONF"
    echo "Queue worker enabled."
else
    echo "Queue worker disabled (set RUN_QUEUE_WORKER=true to enable)."
fi

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
