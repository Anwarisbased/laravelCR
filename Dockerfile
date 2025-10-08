FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www

# Install Laravel dependencies with Composer
RUN composer install --no-dev --optimize-autoloader

# Set proper permissions
RUN chown -R www-data:www-data /var/www/storage
RUN chown -R www-data:www-data /var/www/bootstrap/cache

# Install and configure Nginx
RUN apt-get update && apt-get install -y nginx

# Remove default nginx configuration
RUN rm /etc/nginx/sites-enabled/default

# Create Nginx configuration for Laravel
RUN echo 'server {
    listen 8080;
    index index.php index.html;
    root /var/www/public;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
}' > /etc/nginx/sites-available/laravel

# Enable the site
RUN ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/

# Expose port
EXPOSE 8080

# Create supervisord configuration to run both PHP-FPM and Nginx
RUN echo '[supervisord]
nodaemon=true
user=root

[program:php-fpm]
command=/usr/sbin/php-fpm8.2 -F
priority=10

[program:nginx]
command=/usr/sbin/nginx -g "daemon off;"
priority=20
' > /etc/supervisor/conf.d/supervisord.conf

# Start supervisord to run both services
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]