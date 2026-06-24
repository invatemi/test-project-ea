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

echo "* * * * * cd /var/www/html && php artisan schedule:run >> /var/www/html/storage/logs/scheduler-cron.log 2>&1" | crontab -
cron

php artisan db:seed --class=Database\\Seeders\\WbApiSeeder --force --no-interaction 2>/dev/null || true

exec "$@"
