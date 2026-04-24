#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# When the source dir is bind-mounted from the host, the image's vendor/ is
# shadowed. Install composer deps on first boot if missing.
if [ ! -f vendor/autoload.php ]; then
    echo "› vendor/ missing — running composer install…"
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
fi

# First-boot bootstrapping: copy .env, generate key, wait for MySQL, run migrations.
if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

# Wait for MySQL to be reachable before migrating.
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    echo "› Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}…"
    for i in $(seq 1 60); do
        if php -r "exit(@fsockopen(getenv('DB_HOST') ?: 'mysql', (int)(getenv('DB_PORT') ?: 3306)) ? 0 : 1);"; then
            echo "› MySQL is up."
            break
        fi
        sleep 1
    done
fi

php artisan config:clear || true
php artisan migrate --force || true

# Cache for production performance (skip on local debug).
if [ "${APP_ENV:-local}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

chown -R www-data:www-data storage bootstrap/cache vendor || true

exec "$@"
