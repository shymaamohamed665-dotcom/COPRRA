# Deployment Guide (Production)

This guide describes how to deploy the Coprra Laravel application to a production environment (e.g., Hostinger, VPS, or cloud).

## Pre-Deployment Checklist

- Run test suite: `composer run test` (or `php artisan test`)
- Static analysis clean: `composer run analyse`
- Build assets: `npm run build`
- Review `.env` for production (no secrets in VCS)
- Ensure backups strategy is configured and tested
- Confirm database migrations plan
- Confirm queues, cache, and session drivers
- Confirm storage permissions and symlink
- Verify health endpoint `/health` returns `up` statuses

## First-Time Deployment

1. Provision server (PHP 8.2+, MySQL 8.0+, Redis optional)
2. Clone repository
   ```bash
   git clone <repo-url>
   cd COPRRA
   ```
3. Install PHP dependencies
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. Install Node dependencies and build
   ```bash
   npm ci
   npm run build
   ```
5. Configure environment
   ```bash
   cp .env.example .env
   php artisan key:generate
   # Edit .env with production values
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=your_db
   DB_USERNAME=your_user
   DB_PASSWORD=your_pass
   REDIS_HOST=127.0.0.1
   SENTRY_LARAVEL_DSN=your-dsn
   SENTRY_TRACES_SAMPLE_RATE=0.1
   ```
6. Storage link and permissions
   ```bash
   php artisan storage:link
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```
7. Database migrations & seeders
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```
8. Optimize application
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   composer dump-autoload --optimize --classmap-authoritative
   ```
9. Web server configuration
   - Set document root to `public/`
   - Enable URL rewriting (mod_rewrite / nginx rewrite)
   - TLS/SSL configured

## Updating an Existing Deployment

```bash
# Pull latest changes
git pull origin <branch>

# Install updated PHP deps
composer install --no-dev --optimize-autoloader

# Rebuild assets
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear and re-cache config/routes/views
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Rollback Procedure

1. Identify last known good commit/tag
2. Revert code
   ```bash
   git checkout <good-commit-or-tag>
   ```
3. Roll back migrations (if applicable)
   ```bash
   php artisan migrate:rollback --step=1 --force
   ```
4. Clear caches and verify
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
5. Validate health and basic flows (login, checkout)

## Disaster Recovery

1. Restore from backups
   - Database: import `.sql` dump
   - Files: restore `storage/` and `public/uploads` backups
2. Rebuild application state
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan migrate --force
   php artisan db:seed --force
   php artisan storage:link
   php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
3. Validate
   - `/health` returns `healthy`
   - Key user journeys succeed
   - Error monitoring active (Sentry)

## Operational Notes

- Health endpoint: `GET /health` returns component statuses (`database`, `cache`, `storage`, `queue`)
- Observability: configure Sentry DSN and sample rate; verify via `/sentry-test`
- Queues: ensure worker processes supervised (systemd/supervisor) if using async queues
- Cron: schedule recurring tasks via `php artisan schedule:run`
- Mail: use Mailhog in dev; configure SMTP in production

## Appendix

- PHP-FPM tuning: adjust `pm`, `pm.max_children` based on CPU/RAM
- Nginx: enable gzip, HTTP/2, and cache static assets
- Security headers: enforced via middleware; ensure HTTPS
- CORS: restrict origins via `config/cors.php`
