# Laravel Docker Deployment Guide for Vultr

## Overview
This guide will help you deploy your Laravel application on Vultr using Docker and Docker Compose. This approach includes:
- Your Laravel application in an Apache/PHP container
- A MySQL database container
- phpMyAdmin for database management
- All in one simple deployment

## Prerequisites
- Vultr account with free credits
- Basic SSH knowledge

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

## Step 3: Deploy using Docker (Simple One-Command Method)

Simply run this command to automatically deploy your app:

```bash
curl -fsSL https://raw.githubusercontent.com/Anwarisbased/laravelCR/main/vultr-docker-deploy.sh | bash
```

Or manually:

```bash
# Update system
apt update && apt upgrade -y

# Install Docker and Docker Compose
apt install docker.io docker-compose -y

# Start and enable Docker
systemctl start docker
systemctl enable docker

# Clone your repository
cd /root
git clone https://github.com/Anwarisbased/laravelCR.git laravel-app

# Navigate to app directory
cd laravel-app

# Build and start containers
docker-compose up -d
```

## What This Deployment Includes:

### Containers Created:
1. **Laravel Application** (Apache/PHP 8.3)
2. **MySQL Database** (MySQL 8.0)
3. **phpMyAdmin** (for easy database management)

### Services:
- **Web App**: Port 80 (http://YOUR_VULTR_IP)
- **phpMyAdmin**: Port 8080 (http://YOUR_VULTR_IP:8080)
- **MySQL**: Port 3306 (internal access only)

### Database Credentials:
- **Database**: `laravel`
- **Username**: `sail`
- **Password**: `password`
- **Root Password**: `root`

## Step 4: Access Your Application

After deployment:
1. **Main App**: Visit `http://YOUR_VULTR_IP`
2. **phpMyAdmin**: Visit `http://YOUR_VULTR_IP:8080`
   - Username: `root`
   - Password: `root`

## Managing Your Application

### Check container status:
```bash
cd /root/laravel-app
docker-compose ps
```

### View logs:
```bash
cd /root/laravel-app
docker-compose logs
```

### Stop containers:
```bash
cd /root/laravel-app
docker-compose down
```

### Restart containers:
```bash
cd /root/laravel-app
docker-compose restart
```

### Update your application:
```bash
cd /root/laravel-app
git pull origin main
docker-compose down
docker-compose up -d --build
```

## Advanced Configuration

### Environment Variables
Edit the `docker-compose.yml` file to change:
- Database credentials
- Port mappings
- Environment variables

### Persistent Data
All database data is stored in a Docker volume named `mysql_data`, so it persists even if containers are deleted.

### Custom Domain
To use a custom domain:
1. Point your domain's A record to your Vultr IP
2. Modify the Docker Compose file to include your domain
3. Consider adding SSL with Let's Encrypt

## Benefits of This Approach:

### Cost Effective:
- **No $90/month database fees**
- **Free credits cover server costs**
- **Everything in one $15/month instance**

### Fully Featured:
- Production-ready setup
- Database included
- Management tools (phpMyAdmin)
- Automatic restart on crashes

### Easy Management:
- Simple deployment with one command
- Easy updates with git
- Container isolation for stability

### Scalable:
- Can upgrade to larger instances when needed
- Compatible with Laravel Forge migration later

## Troubleshooting

### If containers won't start:
```bash
cd /root/laravel-app
docker-compose down
docker-compose up -d
docker-compose logs
```

### If database connection fails:
Check that the database container is running:
```bash
docker-compose ps
```

### If you need to reset the database:
```bash
docker-compose down -v  # This removes the data volume
docker-compose up -d
```

## When You Can Afford Laravel Forge:

1. **Subscribe to Forge** ($19/month)
2. **Create a new Forge server** with your Vultr account
3. **Let Forge handle deployments** automatically
4. **Migrate your data** from the current setup

This approach gives you a complete, production-ready Laravel environment without the expensive managed database fees!