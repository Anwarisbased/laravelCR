#!/bin/bash
set -e

# Copy the production .env file if it doesn't exist or if we're in production
if [ "$APP_ENV" = "production" ]; then
    echo "Setting up production environment"
    cp -n /var/www/html/.env.production /var/www/html/.env 2>/dev/null || true
fi

# Generate APP_KEY if it doesn't exist
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY"
    php artisan key:generate --force
else
    echo "Using provided APP_KEY"
    # Make sure APP_KEY is set in the .env file
    sed -i "s/^APP_KEY=.*/APP_KEY=${APP_KEY//\//\\/}/" /var/www/html/.env
fi

# Run migrations and seeders
echo "Running database migrations"
php artisan migrate --force || echo "Migration failed (may be due to database not being ready yet). Will retry in 10 seconds."
sleep 10
php artisan migrate --force || echo "Migration failed again."

echo "Running database seeders"
php artisan db:seed --force || echo "Seeding failed (may be due to database not being ready yet). Will retry in 10 seconds."
sleep 10
php artisan db:seed --force || echo "Seeding failed again."

# Start Apache
apache2-foreground