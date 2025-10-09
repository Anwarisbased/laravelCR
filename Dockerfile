FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Install Laravel dependencies with Composer
RUN composer install --no-dev --optimize-autoloader

# Create storage directories if they don't exist
RUN mkdir -p /var/www/html/storage/framework/cache/data && \
    mkdir -p /var/www/html/storage/framework/sessions && \
    mkdir -p /var/www/html/storage/framework/views && \
    mkdir -p /var/www/html/storage/logs && \
    mkdir -p /var/www/html/bootstrap/cache && \
    mkdir -p /var/www/html/database

# Create SQLite database file for fallback
RUN touch /var/www/html/database/database.sqlite

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/storage
RUN chown -R www-data:www-data /var/www/html/bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/database/database.sqlite

# Create symbolic links for storage if they don't exist
RUN php /var/www/html/artisan storage:link --ansi || echo "Could not create storage link, continuing..."

# Run Laravel setup commands
RUN php artisan key:generate --ansi || echo "APP_KEY already set"
RUN php artisan config:cache --ansi
RUN php artisan route:cache --ansi
RUN php artisan view:cache --ansi

# Configure Apache
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Use Render's PORT environment variable or default to 80
ENV PORT 80
ENV APACHE_PORT 80

EXPOSE 80

# Copy entrypoint script
COPY entrypoint.sh /var/www/html/entrypoint.sh
RUN chmod +x /var/www/html/entrypoint.sh

# Use our custom entrypoint script
ENTRYPOINT ["/var/www/html/entrypoint.sh"]