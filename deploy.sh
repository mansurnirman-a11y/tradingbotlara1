#!/bin/bash
set -e

echo "Starting Laravel deployment..."

# Navigate to application directory (fallback if not run from target)
cd "$(dirname "$0")"

# Put Laravel in maintenance mode
echo "Taking application offline..."
php artisan down || true

# Pull the latest code from GitHub
echo "Pulling latest code from main branch..."
git pull origin main

# Install Composer dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Clear and cache configurations
echo "Caching configuration and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
echo "Restarting queue workers..."
php artisan queue:restart || true
supervisorctl restart capitalfirst-worker:* || true

# Bring Laravel back online
echo "Bringing application online..."
php artisan up

echo "Laravel deployment completed successfully!"
