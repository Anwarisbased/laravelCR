#!/bin/bash
set -e

# Wait for database to be ready
echo "Waiting for database to be ready..."
until mysqladmin ping -h db --silent; do
    echo "Database is not ready yet. Waiting..."
    sleep 2
done
echo "Database is ready!"

# Change to working directory
cd /var/www/html

# Run Laravel setup commands
echo "Running Laravel setup..."

# Run migrations
php artisan migrate --force || echo "Migration failed (may be due to database not being ready yet)"

# Run seeders
php artisan db:seed --force || echo "Seeding failed (may be due to database not being ready yet)"

# Start Apache
echo "Starting Apache server..."
apache2-foreground