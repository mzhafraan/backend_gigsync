#!/bin/bash
set -e

echo "Deploying update to production..."
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart gigsync-queue:*
echo "Deployment completed successfully!"
