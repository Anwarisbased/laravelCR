#!/bin/bash
# Laravel Manual Deployment Script for Vultr

# Update system
sudo apt update && sudo apt upgrade -y

# Install LEMP Stack
sudo apt install nginx mysql-server php8.1-fpm php8.1-mysql php8.1-xml php8.1-curl php8.1-gd php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-intl php8.1-cli unzip git -y

# Start and enable services
sudo systemctl start nginx
sudo systemctl enable nginx
sudo systemctl start mysql
sudo systemctl enable mysql
sudo systemctl start php8.1-fpm
sudo systemctl enable php8.1-fpm

# Secure MySQL
sudo mysql_secure_installation

# Create database and user
sudo mysql -e "CREATE DATABASE laravel_db;"
sudo mysql -e "CREATE USER 'laravel_user'@'localhost' IDENTIFIED BY 'your_secure_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON laravel_db.* TO 'laravel_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Clone your Laravel project
cd /var/www
sudo git clone https://github.com/Anwarisbased/laravelCR.git html
cd html

# Set permissions
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# Install Composer dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Create .env file
sudo cp .env.example .env
sudo nano .env  # Update database credentials

# Generate app key
sudo php artisan key:generate

# Run migrations
sudo php artisan migrate --force

# Configure Nginx
sudo nano /etc/nginx/sites-available/laravel  # Add Laravel config

# Enable site
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

echo "Laravel app deployed! Visit your Vultr IP address"