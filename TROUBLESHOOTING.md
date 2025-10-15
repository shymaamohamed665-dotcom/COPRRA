# COPRRA - Troubleshooting Guide

## Common Issues and Solutions

### 1. 500 Internal Server Error

#### Symptoms
- Application returns HTTP 500 error
- White screen or generic error page
- No detailed error message

#### Causes & Solutions

**A. Permission Issues (Most Common)**

```bash
# Check current permissions
ls -la storage/

# Fix permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Fix permissions (Docker)
docker exec -it coprra-app chmod -R 775 storage bootstrap/cache
docker exec -it coprra-app chown -R www-data:www-data storage bootstrap/cache
```

**B. Missing Environment File**

```bash
# Check if .env exists
ls -la .env

# If missing, copy from example
cp .env.example .env
php artisan key:generate
```

**C. Missing Storage Directories**

```bash
# Create required directories
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
chmod -R 775 storage
```

**D. Check Laravel Logs**

```bash
# View latest errors
tail -50 storage/logs/laravel.log

# Watch logs in real-time
tail -f storage/logs/laravel.log
```

---

### 2. Database Connection Errors

#### Symptoms
- `SQLSTATE[HY000] [2002] Connection refused`
- `SQLSTATE[HY000] [1045] Access denied`
- `SQLSTATE[HY000] [2002] No such host is known`

#### Solutions

**A. Verify Database Credentials**

```bash
# Test connection manually
mysql -h 127.0.0.1 -u coprra -p coprra

# In Docker
docker exec -it coprra-db mysql -u coprra -p
```

**B. Check Database Service Status**

```bash
# Local
sudo systemctl status mysql

# Docker
docker-compose ps db
docker logs coprra-db
```

**C. Fix Hostname Issues**

```env
# Local development - use 127.0.0.1
DB_HOST=127.0.0.1

# Docker - use container name
DB_HOST=db
```

**D. Check Database Exists**

```bash
php artisan migrate:status
# If error, create database:
mysql -u root -p -e "CREATE DATABASE coprra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

### 3. Composer/NPM Installation Issues

#### Symptoms
- `composer install` fails
- `npm install` fails
- Missing dependencies

#### Solutions

**A. Clear Caches**

```bash
# Composer
composer clear-cache
rm -rf vendor
composer install

# NPM
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

**B. Memory Limit Issues**

```bash
# Increase PHP memory limit temporarily
php -d memory_limit=2G /usr/local/bin/composer install

# Or set in php.ini
memory_limit = 2G
```

**C. Timeout Issues**

```bash
# Increase Composer timeout
export COMPOSER_PROCESS_TIMEOUT=600
composer install
```

---

### 4. Docker Issues

#### Container Won't Start

```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs app
docker-compose logs nginx

# Restart services
docker-compose down
docker-compose up -d

# Force rebuild
docker-compose build --no-cache
docker-compose up -d
```

#### Permission Denied in Container

```bash
# Fix ownership
docker exec -it coprra-app chown -R www-data:www-data /var/www/html/storage

# Or recreate volumes
docker-compose down -v
docker-compose up -d
```

#### Port Already in Use

```bash
# Find process using port 8000
lsof -i :8000  # Linux/Mac
netstat -ano | findstr :8000  # Windows

# Kill process or change port in docker-compose.yml
ports:
  - "8080:80"  # Changed from 8000
```

---

### 5. Frontend/Asset Issues

#### Assets Not Loading

```bash
# Rebuild assets
npm run build

# Check public/build directory exists
ls -la public/build

# Clear Laravel caches
php artisan view:clear
php artisan config:clear
```

#### Vite Not Starting

```bash
# Check node version (should be 18+)
node --version

# Reinstall dependencies
rm -rf node_modules
npm install

# Try different port
npm run dev -- --port 5174
```

---

### 6. Test Failures

#### All Tests Failing

```bash
# Ensure test environment is set up
cp .env.testing .env.testing.backup
cat .env.example > .env.testing
echo "DB_CONNECTION=sqlite" >> .env.testing
echo "DB_DATABASE=:memory:" >> .env.testing

# Run specific test suite
php artisan test --testsuite=Unit

# Check configuration
php artisan config:show database --env=testing
```

#### SQLite Issues

```bash
# Install SQLite extension
# Ubuntu/Debian
sudo apt-get install php8.2-sqlite3

# Mac
brew install sqlite

# Windows - ensure php_sqlite3.dll is enabled in php.ini
```

---

### 7. Session/Auth Issues

#### Session Not Persisting

```bash
# Clear sessions
php artisan session:clear

# Check session driver
# In .env
SESSION_DRIVER=file  # or redis/database

# Ensure session directory is writable
chmod -R 775 storage/framework/sessions
```

#### Authentication Loop

```bash
# Clear all caches
php artisan optimize:clear

# Regenerate key
php artisan key:generate

# Check APP_KEY is set in .env
grep APP_KEY .env
```

---

### 8. Cache Issues

#### Stale Configuration

```bash
# Clear ALL caches
php artisan optimize:clear

# Or individually
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

#### Redis Connection Issues

```bash
# Check Redis is running
redis-cli ping  # Should return PONG

# Docker
docker exec -it coprra-redis redis-cli ping

# If not running
docker-compose restart redis
```

---

### 9. Nginx Configuration Issues

#### Nginx Won't Start

```bash
# Test configuration
nginx -t

# Docker
docker exec coprra-nginx nginx -t

# Check logs
docker logs coprra-nginx
tail -f /var/log/nginx/error.log
```

#### 502 Bad Gateway

```bash
# Check PHP-FPM is running
docker-compose ps app

# Check PHP-FPM logs
docker logs coprra-app

# Verify PHP-FPM socket/port in nginx config
```

---

### 10. Performance Issues

#### Slow Page Load

```bash
# Enable caching
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Enable OPcache (check php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000

# Use Redis for sessions/cache
SESSION_DRIVER=redis
CACHE_DRIVER=redis
```

#### High Memory Usage

```bash
# Check for memory leaks
php artisan queue:work --memory=256

# Optimize Composer autoloader
composer dump-autoload --optimize --classmap-authoritative

# Monitor memory
docker stats coprra-app
```

---

## Diagnostic Commands

### System Health Check

```bash
# Run comprehensive health check
bash scripts/health-check.sh

# Check Laravel installation
php artisan about

# List all routes
php artisan route:list

# Check migrations
php artisan migrate:status

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Environment Diagnostics

```bash
# Show PHP configuration
php -i | grep -i "configuration file"
php --ini

# Show all config values
php artisan config:show

# Check environment
php artisan env

# List installed extensions
php -m
```

### Docker Diagnostics

```bash
# Container status
docker-compose ps

# Resource usage
docker stats

# Network inspection
docker network ls
docker network inspect coprra-network

# Volume inspection
docker volume ls
docker volume inspect coprra_mysql-data
```

---

## Getting Additional Help

### Enable Debug Mode

```env
# In .env (NEVER in production!)
APP_DEBUG=true
APP_ENV=local
LOG_LEVEL=debug
```

### Collect Debug Information

```bash
# System information
php artisan about > debug-info.txt

# Composer info
composer diagnose >> debug-info.txt

# Docker info (if applicable)
docker-compose ps >> debug-info.txt
docker stats --no-stream >> debug-info.txt

# Log last 100 lines
tail -100 storage/logs/laravel.log >> debug-info.txt
```

### Report an Issue

When reporting issues, include:
1. Laravel version: `php artisan --version`
2. PHP version: `php --version`
3. Environment: local, Docker, production
4. Error message (full stack trace)
5. Steps to reproduce
6. What you've already tried

---

## Emergency Recovery

### Complete Reset (Development Only!)

```bash
# WARNING: This deletes all data!

# Docker
docker-compose down -v
rm -rf vendor node_modules
composer install
npm install
docker-compose up -d
docker exec -it coprra-app php artisan migrate:fresh --seed

# Local
php artisan down
rm -rf vendor node_modules bootstrap/cache/* storage/framework/cache/*
composer install
npm install
php artisan migrate:fresh --seed
php artisan up
```

---

**Troubleshooting Guide Version:** 1.0
**Last Updated:** October 15, 2025

For setup instructions, see [SETUP_GUIDE.md](./SETUP_GUIDE.md)
