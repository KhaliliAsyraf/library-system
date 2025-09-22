#!/bin/bash
set -e

# Run composer install automatically if composer.json exists
if [ -f "composer.json" ]; then
  echo ">>> Running composer install..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Fix permissions for Laravel
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Run migrations (normal + testing)
if [ -f "artisan" ]; then
  echo ">>> Running php artisan migrate"
  php artisan migrate --force

  echo ">>> Running php artisan migrate --env=testing"
  php artisan migrate --env=testing --force
fi

# Continue with default CMD (php-fpm)
exec "$@"
