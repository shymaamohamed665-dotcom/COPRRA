# 🚀 Deployment Guide

This guide covers deploying Coprra to production environments.

## 📋 Table of Contents

- [Prerequisites](#prerequisites)
- [Server Requirements](#server-requirements)
- [Deployment Steps](#deployment-steps)
- [Environment Configuration](#environment-configuration)
- [Database Setup](#database-setup)
- [Web Server Configuration](#web-server-configuration)
- [SSL/TLS Setup](#ssltls-setup)
- [Performance Optimization](#performance-optimization)
- [Monitoring](#monitoring)
- [Backup Strategy](#backup-strategy)
- [Troubleshooting](#troubleshooting)

---

## ✅ Prerequisites

Before deploying, ensure you have:

- [ ] Server with SSH access
- [ ] Domain name configured
- [ ] SSL certificate (Let's Encrypt recommended)
- [ ] Database server (MySQL 8.0+ or PostgreSQL 13+)
- [ ] PHP 8.2+ with required extensions
- [ ] Composer installed
- [ ] Node.js 18+ and NPM
- [ ] Git installed

---

## 🖥️ Server Requirements

### Minimum Requirements

- **CPU:** 2 cores
- **RAM:** 2 GB
- **Storage:** 20 GB SSD
- **Bandwidth:** 100 GB/month

### Recommended Requirements

- **CPU:** 4 cores
- **RAM:** 4 GB
- **Storage:** 50 GB SSD
- **Bandwidth:** 500 GB/month

### PHP Extensions

```bash
php -m | grep -E 'bcmath|ctype|curl|dom|fileinfo|json|mbstring|openssl|pcre|pdo|tokenizer|xml|gd|zip'
```

Required extensions:
- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PCRE
- PDO
- Tokenizer
- XML
- GD
- ZIP

---

## 📦 Deployment Steps

### 1. Clone Repository

```bash
cd /var/www
git clone https://github.com/your-org/coprra.git
cd coprra
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm ci --production

# Build assets
npm run build
```

### 3. Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/coprra

# Set directory permissions
sudo find /var/www/coprra -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/coprra -type f -exec chmod 644 {} \;

# Set storage and cache permissions
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit environment variables
nano .env
```

### 5. Database Migration

```bash
# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed --force
```

### 6. Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 7. Create Storage Link

```bash
php artisan storage:link
```

---

## ⚙️ Environment Configuration

### Production .env Settings

```env
APP_NAME=Coprra
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://coprra.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coprra_production
DB_USERNAME=coprra_user
DB_PASSWORD=secure_password_here

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@coprra.com"
MAIL_FROM_NAME="${APP_NAME}"

# Security
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=coprra.com,www.coprra.com
```

---

## 🗄️ Database Setup

### MySQL Configuration

```sql
-- Create database
CREATE DATABASE coprra_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'coprra_user'@'localhost' IDENTIFIED BY 'secure_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON coprra_production.* TO 'coprra_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;
```

### Database Optimization

```sql
-- Enable query cache (MySQL 5.7)
SET GLOBAL query_cache_size = 67108864;
SET GLOBAL query_cache_type = 1;

-- Optimize tables
OPTIMIZE TABLE orders, products, users;
```

---

## 🌐 Web Server Configuration

### Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name coprra.com www.coprra.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name coprra.com www.coprra.com;
    root /var/www/coprra/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/coprra.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/coprra.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Logging
    access_log /var/log/nginx/coprra-access.log;
    error_log /var/log/nginx/coprra-error.log;

    # Index
    index index.php;

    charset utf-8;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName coprra.com
    ServerAlias www.coprra.com
    Redirect permanent / https://coprra.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName coprra.com
    ServerAlias www.coprra.com
    DocumentRoot /var/www/coprra/public

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/coprra.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/coprra.com/privkey.pem

    <Directory /var/www/coprra/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/coprra-error.log
    CustomLog ${APACHE_LOG_DIR}/coprra-access.log combined
</VirtualHost>
```

---

## 🔒 SSL/TLS Setup

### Let's Encrypt (Certbot)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d coprra.com -d www.coprra.com

# Auto-renewal
sudo certbot renew --dry-run
```

---

## ⚡ Performance Optimization

### 1. Enable OPcache

```ini
; /etc/php/8.2/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### 2. Configure Redis

```bash
# Install Redis
sudo apt install redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf

# Set maxmemory
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### 3. Queue Workers

```bash
# Install Supervisor
sudo apt install supervisor

# Create worker configuration
sudo nano /etc/supervisor/conf.d/coprra-worker.conf
```

```ini
[program:coprra-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/coprra/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/coprra/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Start workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start coprra-worker:*
```

---

## 📊 Monitoring

### Laravel Telescope (Development Only)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### Log Monitoring

```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log

# Monitor Nginx logs
tail -f /var/log/nginx/coprra-error.log
```

---

## 💾 Backup Strategy

### Database Backup

```bash
#!/bin/bash
# /usr/local/bin/backup-coprra-db.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/coprra"
DB_NAME="coprra_production"
DB_USER="coprra_user"
DB_PASS="secure_password_here"

mkdir -p $BACKUP_DIR

mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete
```

### Cron Job

```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-coprra-db.sh
```

---

## 🔧 Troubleshooting

### Clear All Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

### Permission Issues

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 500 Error

Check logs:
```bash
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/coprra-error.log
```

---

**Last Updated:** 2025-10-01  
**Version:** 2.0.0

