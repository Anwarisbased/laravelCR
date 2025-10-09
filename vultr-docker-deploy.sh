#!/bin/bash
# Simple Docker Deployment Script for Vultr

# Update system
echo "Updating system..."
apt update && apt upgrade -y

# Install Docker
echo "Installing Docker..."
apt install docker.io docker-compose -y

# Start and enable Docker
echo "Starting Docker..."
systemctl start docker
systemctl enable docker

# Clone repository if not already cloned
if [ ! -d "/root/laravel-app" ]; then
    echo "Cloning repository..."
    cd /root
    git clone https://github.com/Anwarisbased/laravelCR.git laravel-app
fi

# Navigate to app directory
cd /root/laravel-app

# Build and start containers
echo "Building and starting containers..."
docker-compose up -d

echo "Deployment complete!"
echo "Your app should be available at: http://YOUR_VULTR_IP"
echo "phpMyAdmin available at: http://YOUR_VULTR_IP:8080"