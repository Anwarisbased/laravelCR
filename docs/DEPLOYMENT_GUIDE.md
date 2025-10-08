# Deployment Guide for CannaRewards Synergy Engine

## Overview
This guide will help you set up a staging environment for the CannaRewards Synergy Engine platform. We'll focus on a multi-tenant architecture with Customer.io integration.

## Prerequisites
- A server (DigitalOcean, Vultr, AWS, etc.)
- Domain name
- Customer.io account and credentials
- Redis server
- Database (MySQL/PostgreSQL)

## Server Setup with Laravel Forge (Recommended)

### 1. Server Provisioning
1. Create a server with your preferred cloud provider (DigitalOcean, Vultr recommended for credits)
2. Choose Ubuntu 22.04 LTS
3. Minimum recommended: 2GB RAM, 1 CPU (can start smaller for staging)
4. Add your SSH key during server creation

### 2. Laravel Forge Setup
1. Create a Laravel Forge account
2. Add your server to Forge
3. Configure your domain (e.g., staging.yourapp.com)
4. Forge will automatically install:
   - Nginx
   - PHP 8.2+
   - MySQL
   - Redis
   - Certbot (for SSL)

### 3. Database Setup
1. Create a database in Forge
2. Create multiple databases if you plan to use separate DBs per brand initially
3. Note the database credentials

## Application Deployment

### 1. Clone the Repository
```bash
cd /home/forge/staging.yourapp.com
git clone your-repo .
```

### 2. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build  # or npm run prod for production build
```

### 3. Environment Configuration
Create your `.env` file:
```env
APP_NAME="CannaRewards Staging"
APP_ENV=staging
APP_KEY=
APP_DEBUG=true
APP_URL=https://staging.yourapp.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=forge
DB_PASSWORD=******

# Customer.io Integration
CUSTOMER_IO_SITE_ID=your_site_id
CUSTOMER_IO_API_KEY=your_api_key
CUSTOMER_IO_TRACKING_API_KEY=your_tracking_key

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis
QUEUE_DRIVER=redis

# File Storage
FILESYSTEM_DISK=s3  # or local for staging
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Run Database Migrations
```bash
php artisan migrate --force
```

### 6. Set File Permissions
```bash
sudo chown -R forge:forge /home/forge/staging.yourapp.com
chmod -R 755 /home/forge/staging.yourapp.com
```

## Customer.io Setup for Staging

### 1. Create Staging Environment in Customer.io
1. Log into Customer.io
2. Create a separate site for staging
3. Get the staging credentials for your .env file

### 2. Webhook Configuration
1. In Customer.io settings, add webhook endpoint: `https://staging.yourapp.com/api/webhooks/customer-io`
2. Note the webhook signing key and add to your .env

## Frontend Development Setup

### 1. Next.js PWA Configuration
The frontend should be served from the same domain initially to avoid CORS issues:
- Backend API: `https://staging.yourapp.com/api/*`
- Frontend: `https://staging.yourapp.com/*`

### 2. Environment Variables for Frontend
Create a `.env.local` in your Next.js project:
```env
NEXT_PUBLIC_API_BASE_URL=https://staging.yourapp.com/api
NEXT_PUBLIC_CUSTOMER_IO_APP_ID=your_app_id
NEXT_PUBLIC_SENTRY_DSN=your_sentry_dsn
```

## Performance Optimization for Staging

### 1. Caching Configuration
Ensure these are in your .env:
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 2. Queue Worker Setup
Set up a queue worker in Forge:
```
cd /home/forge/staging.yourapp.com && php artisan queue:work --sleep=3 --tries=3
```

### 3. Database Optimization
Add these to your .env for performance:
```env
DB_PERSISTENT=true
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

## Staging-Specific Optimizations

### 1. Database Indices for Multi-Tenant
Ensure you have proper indices for multi-tenant queries:
```sql
-- Common indices needed for multi-tenant performance
CREATE INDEX idx_users_brand_id ON users(brand_id);
CREATE INDEX idx_user_action_log_brand_user ON user_action_log(brand_id, user_id);
CREATE INDEX idx_products_brand_id ON products(brand_id);
CREATE INDEX idx_user_achievements_brand_user ON user_achievements(brand_id, user_id);
```

### 2. Rate Limiting for Staging
Configure reasonable limits for testing:
```env
API_RATE_LIMIT=60  # 60 requests per minute per IP
```

## Common Setup Issues and Solutions

### 1. SSL Certificate Issues
- Forge usually handles this automatically, but if not:
- In Forge, click "Install Certificate" for your domain
- Choose Let's Encrypt

### 2. Queue Worker Not Running
- Check Forge's "Daemons" section
- Add a daemon for the queue worker
- Monitor the queue: `php artisan queue:listen --tries=3`

### 3. Database Connection Issues
- Verify database credentials in .env
- Check if MySQL is running: `sudo systemctl status mysql`
- Ensure firewall allows connections if needed

## Next Steps After Deployment

1. Verify the API is working with basic endpoints
2. Test Customer.io integration with test events
3. Set up basic frontend with authentication flow
4. Implement basic PWA functionality
5. Add caching layer and test performance

## Development Workflow

### Frontend Development
1. Develop frontend locally with hot-reloading
2. Deploy backend changes to staging
3. Test integration points on staging
4. Use browser devtools to monitor API calls and performance

### Backend Development
1. Make backend changes
2. Deploy to staging
3. Test API endpoints directly
4. Verify Customer.io events are being sent correctly

This staging environment will give you the ability to test all aspects of your Synergy Engine with real data and performance metrics, not just theoretical backend-only functionality.