# COPRRA - Complete Setup Guide

## Quick Start (Docker - Recommended)

### Prerequisites
- Docker Engine 20.10+ and Docker Compose 2.0+
- Git 2.30+
- 4GB RAM minimum, 8GB recommended
- 10GB free disk space

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/coprra.git
   cd coprra
   ```

2. **Copy environment file**
   ```bash
   cp .env.docker .env
   ```

3. **Generate application key**
   ```bash
   # Generate a secure key
   php artisan key:generate
   # Or manually set in .env: APP_KEY=base64:YOUR_32_CHARACTER_KEY
   ```

4. **Start Docker containers**
   ```bash
   # Using the enhanced configuration
   docker-compose -f docker-compose.enhanced.yml up -d

   # Or using the standard configuration
   docker-compose up -d
   ```

5. **Install dependencies (in container)**
   ```bash
   docker exec -it coprra-app composer install
   docker exec -it coprra-app npm install
   docker exec -it coprra-app npm run build
   ```

6. **Run database migrations**
   ```bash
   docker exec -it coprra-app php artisan migrate --seed
   ```

7. **Create storage link**
   ```bash
   docker exec -it coprra-app php artisan storage:link
   ```

8. **Access the application**
   - Application: http://localhost:8000
   - Mailpit UI: http://localhost:8025
   - Database: localhost:33061

---

## Local Development Setup (Without Docker)

### Prerequisites
- PHP 8.2+
- Composer 2.5+
- Node.js 18+ & NPM 9+
- MySQL 8.0+ or MariaDB 10.6+
- Redis 7+ (optional but recommended)

### Setup Steps

1. **Clone and navigate**
   ```bash
   git clone https://github.com/your-org/coprra.git
   cd coprra
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install NPM dependencies**
   ```bash
   npm install
   ```

4. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   Edit `.env` and set:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=coprra
   DB_USERNAME=your_db_user
   DB_PASSWORD=your_db_password
   ```

6. **Create database**
   ```bash
   mysql -u root -p
   CREATE DATABASE coprra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

7. **Run migrations**
   ```bash
   php artisan migrate --seed
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   # Or for development with hot reload:
   npm run dev
   ```

9. **Start development server**
   ```bash
   php artisan serve
   ```

10. **Access the application**
    - http://localhost:8000

---

## Configuration Details

### Environment Variables

#### Required Variables
```env
APP_NAME=COPRRA
APP_ENV=local|production
APP_KEY=base64:... # Generate with: php artisan key:generate
APP_DEBUG=true|false
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coprra
DB_USERNAME=coprra
DB_PASSWORD=secure_password
```

#### Optional but Recommended
```env
# Redis (for caching and sessions)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null

# COPRRA Specific
COPRRA_DEFAULT_CURRENCY=USD
COPRRA_DEFAULT_LANGUAGE=en
PRICE_CACHE_DURATION=3600
MAX_STORES_PER_PRODUCT=10
API_RATE_LIMIT=100
```

### File Permissions

Ensure proper permissions for Laravel directories:

```bash
# Local development
chmod -R 775 storage bootstrap/cache
chown -R $USER:www-data storage bootstrap/cache

# Production
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Storage Directories

Laravel requires these writable directories:
- `storage/app/`
- `storage/framework/cache/`
- `storage/framework/sessions/`
- `storage/framework/views/`
- `storage/logs/`
- `bootstrap/cache/`

---

## Post-Setup Verification

### Health Check

Run the comprehensive health check script:
```bash
bash scripts/health-check.sh
```

### Manual Verification

1. **Check database connection**
   ```bash
   php artisan migrate:status
   ```

2. **Check configuration**
   ```bash
   php artisan config:show app
   ```

3. **Check routes**
   ```bash
   php artisan route:list
   ```

4. **Run tests**
   ```bash
   php artisan test
   ```

5. **Check storage permissions**
   ```bash
   php artisan storage:link
   ls -la storage/
   ```

---

## IDE Setup

### PHPStorm

1. Install Laravel Plugin
2. Enable Laravel settings: Preferences → Languages & Frameworks → PHP → Laravel
3. Mark `tests` directory as Test Sources Root
4. Configure PHPUnit: Run → Edit Configurations → PHPUnit

### VS Code

Recommended extensions:
- Laravel Extension Pack
- PHP Intelephense
- EditorConfig for VS Code
- Prettier - Code formatter
- ESLint

Add to `.vscode/settings.json`:
```json
{
  "php.suggest.basic": false,
  "intelephense.files.exclude": [
    "**/vendor/**"
  ],
  "editor.formatOnSave": true
}
```

---

## Docker-Specific Tips

### Useful Docker Commands

```bash
# View logs
docker-compose logs -f app
docker-compose logs -f nginx

# Execute commands in container
docker exec -it coprra-app bash
docker exec -it coprra-app php artisan tinker

# Restart services
docker-compose restart app
docker-compose restart nginx

# Stop all services
docker-compose down

# Remove volumes (WARNING: deletes data)
docker-compose down -v

# View container status
docker-compose ps

# Monitor resource usage
docker stats
```

### Rebuilding Containers

After changing Dockerfile or dependencies:
```bash
docker-compose build --no-cache
docker-compose up -d
```

---

## Next Steps

1. Read the [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) guide
2. Review [CLAUDE.md](./CLAUDE.md) for development guidelines
3. Check [DOCUMENTATION_INDEX.md](./DOCUMENTATION_INDEX.md) for all documentation
4. Set up your IDE using the guidelines above
5. Run the test suite: `php artisan test`

---

## Getting Help

- Check [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)
- Review [GitHub Issues](https://github.com/your-org/coprra/issues)
- Contact the development team

---

**Setup Guide Version:** 1.0
**Last Updated:** October 15, 2025
