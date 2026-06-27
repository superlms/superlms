#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Ensure writable directories exist with correct ownership when a host volume is mounted
mkdir -p \
    public/build \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache || true

# Seed the shared nginx asset volume with the Vite build generated in the image.
# Always re-seed so a fresh image overwrites stale assets on the host bind mount.
if [ -d /opt/app-public-build ]; then
    cp -R /opt/app-public-build/. public/build/
    chown -R www-data:www-data public/build || true
fi

# Generate APP_KEY if it's missing — useful for fresh deploys
if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force --no-interaction || true
fi

# Symlink storage to public if requested
if [ "${RUN_STORAGE_LINK:-true}" = "true" ]; then
    php artisan storage:link --force || true
fi

# Migrations are intentionally NOT run on container boot.
# On ECS every web/worker task would race to migrate the same database,
# causing duplicate/locked migrations. Instead migrations run as a single
# dedicated one-off task (the "migrate" role — Phase 11):
#     php artisan migrate --force --no-interaction
#     php artisan lms:migrate --no-interaction

# Cache config/routes/views for production
if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache  || true
    php artisan route:cache   || true
    php artisan view:cache    || true
    php artisan event:cache   || true
fi

exec "$@"
