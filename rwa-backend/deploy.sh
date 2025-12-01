#!/bin/bash

# Laravel Deployment Script
# Run this on the server after FTP upload

echo "Starting Laravel deployment..."

# Navigate to project directory
cd /home/u151629516/domains/creativehandz.in/public_html/rosewoodestate2rwa

# Install/Update Composer dependencies
echo "Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

# Generate application key if needed
echo "Generating application key..."
php artisan key:generate --force

# Clear and cache config
echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configuration for production
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deployment completed successfully!"