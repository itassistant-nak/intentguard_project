#!/usr/bin/env sh
set -e

echo "[IntentGuard API] Starting backend entrypoint..."

# Wait for MySQL if configured
if [ -n "$DB_HOST" ]; then
    echo "[IntentGuard API] Waiting for database connection at $DB_HOST:${DB_PORT:-3306}..."
    max_retries=30
    count=0
    until nc -z -v -w3 "$DB_HOST" "${DB_PORT:-3306}" 2>/dev/null; do
        count=$((count + 1))
        if [ $count -ge $max_retries ]; then
            echo "[IntentGuard API] Error: Timed out waiting for database connection."
            exit 1
        fi
        echo "[IntentGuard API] Database not ready yet (attempt $count/$max_retries). Waiting 2s..."
        sleep 2
    done
    echo "[IntentGuard API] Database connection established successfully."
fi

# Ensure storage and bootstrap/cache permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Run database migrations
echo "[IntentGuard API] Executing database migrations..."
php artisan migrate --force

# Seed initial policies and admin if not already present
echo "[IntentGuard API] Running essential seeders..."
php artisan db:seed --force 2>/dev/null || true

# Cache Laravel optimizations in production
if [ "$APP_ENV" = "production" ]; then
    echo "[IntentGuard API] Caching routes and configuration for production..."
    php artisan config:cache
    php artisan route:cache
fi

echo "[IntentGuard API] Initialization complete. Starting application server on 0.0.0.0:8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
