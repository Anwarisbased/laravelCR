# Laravel Setup Guide for Vultr (Manual Installation)

## Step 1: Create Vultr Instance
1. Sign up for Vultr using your credits
2. Create a new Cloud Compute instance:
   - Location: Any location
   - Type: Ubuntu 22.04 LTS
   - Plan: $15/month (use your free credits)
   - Add SSH key if you have one

## Step 2: SSH into your instance
```bash
ssh root@YOUR_VULTR_IP_ADDRESS
```

## Step 3: Update system and install dependencies
```bash
# Update package manager
apt update && apt upgrade -y

# Install LEMP stack components
apt install nginx mysql-server php8.1-fpm php8.1-mysql php8.1-xml php8.1-curl php8.1-gd php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-intl unzip git curl -y
```

## Step 4: Start and enable services
```bash
systemctl start nginx mysql php8.1-fpm
systemctl enable nginx mysql php8.1-fpm
```

## Step 5: Secure MySQL
```bash
mysql_secure_installation
# Follow prompts (set root password, remove anonymous users, etc.)
```

## Step 6: Create database and user
```bash
mysql -u root -p
```

In MySQL prompt:
```sql
CREATE DATABASE laravel_db;
CREATE USER 'laravel_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON laravel_db.* TO 'laravel_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Step 7: Clone and set up your Laravel app
```bash
# Remove default nginx site
rm /var/www/html

# Clone your repository
cd /var/www
git clone https://github.com/Anwarisbased/laravelCR.git html
cd html

# Set permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Install Composer (if not already installed)
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install Laravel dependencies
sudo -u www-data /usr/local/bin/composer install --no-dev --optimize-autoloader
```

## Step 8: Configure Laravel
```bash
# Create .env file
sudo -u www-data cp .env.example .env

# Edit .env file with your database credentials
nano .env
```

Update these lines in .env:
```
APP_NAME=Laravel
APP_ENV=production
APP_KEY=  # Leave blank for now
APP_DEBUG=false
APP_URL=http://YOUR_VULTR_IP_ADDRESS

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=your_secure_password
```

## Step 9: Generate app key and run setup
```bash
# Generate app key
sudo -u www-data php artisan key:generate

# Run migrations
sudo -u www-data php artisan migrate --force

# Create storage link
sudo -u www-data php artisan storage:link
```

## Step 10: Configure Nginx
```bash
# Create Nginx config
nano /etc/nginx/sites-available/laravel
```

Paste this configuration:
```nginx
server {
    listen 80;
    server_name YOUR_VULTR_IP_ADDRESS;
    root /var/www/html/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    margin: 0;
    padding: 0;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:
```bash
ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

## Your app should now be accessible at:
http://YOUR_VULTR_IP_ADDRESS

## When you can afford Laravel Forge:
1. Upgrade your Vultr instance plan if needed
2. Subscribe to Laravel Forge ($19/month)
3. Connect your existing Vultr server to Forge
4. Let Forge manage your deployments
5. Or create a new Forge-managed server and migrate your data

## Benefits of this approach:
- No monthly database costs ($0/month vs $90/month)
- Full control over your server
- Can upgrade to Forge later for easier management
- Your free credits cover the server costs initially