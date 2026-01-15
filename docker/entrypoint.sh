#!/bin/bash

# Exit on fail
set -e

# Wait for database connection (simple wait)
# In production, you might want a more robust check

# Helper to run artisan commands
function artisan {
    php artisan "$@"
}

echo "Starting deployment checks..."

# Check if .env exists, if not copy example (mostly for first run/dev)
if [ ! -f .env ]; then
    echo "Creating .env from .env.example"
    cp .env.example .env
    artisan key:generate
fi

# Optimization only in production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration..."
    artisan config:cache
    artisan route:cache
    artisan view:cache
    artisan event:cache
fi

echo "Running migrations..."
artisan migrate --force

echo "Starting PHP-FPM..."
exec php-fpm
