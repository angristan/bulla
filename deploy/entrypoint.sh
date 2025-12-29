#!/bin/sh
set -e

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    if [ ! -f /app/.env ]; then
        cp /app/.env.example /app/.env 2>/dev/null || true
    fi
    php artisan key:generate --force
fi

# Set database path for SQLite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    export DB_DATABASE="${DB_DATABASE:-/app/database/database.sqlite}"
    touch "$DB_DATABASE"
fi

# Run migrations
php artisan migrate --force

# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database

# Execute main command
exec "$@"
