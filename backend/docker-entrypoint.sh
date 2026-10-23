#!/bin/sh
set -e

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Running database seeders..."
php artisan db:seed --force

echo "==> Starting FrankenPHP server..."
exec frankenphp php-server --listen :8000 --root /app/public
