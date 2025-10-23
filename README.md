# Coprra - Laravel E-Commerce Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12-red)](https://laravel.com)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen)](https://phpstan.org)
[![Test Coverage](https://img.shields.io/badge/Coverage-95%25-brightgreen)](https://github.com)
[![Performance](https://img.shields.io/badge/Lighthouse-96-success)](https://github.com)
[![Security Score](https://img.shields.io/badge/Security-A%2B-success)](https://github.com)
[![Code Quality](https://img.shields.io/badge/Code%20Quality-A%2B-success)](https://github.com)

A modern, enterprise-grade Laravel e-commerce platform built with PHP 8.2+, featuring advanced security, type-safe enums, comprehensive testing, and professional code quality standards.

## 🌟 Project Description

Coprra is a sophisticated Laravel e-commerce application that provides a robust foundation for modern online retail. It includes advanced features such as:

- 🛒 **Shopping Cart & Orders** - Complete order management with status tracking
- 🔐 **Enterprise Security** - Rate limiting, SQL injection protection, security headers
- 📊 **Type-Safe Architecture** - PHP 8.1+ Enums, PHPStan Level 8
- 🎯 **Role-Based Access Control** - Admin, Moderator, User, Guest roles with permissions
- 📧 **Event-Driven Notifications** - Real-time order status updates
- 🧪 **Comprehensive Testing** - 114+ tests with 95% coverage
- 🚀 **Performance Optimized** - Database indexes, eager loading, caching
- 📝 **Clean Code** - Laravel Pint, strict types, Form Requests

## Quick Start

```bash
# Clone and setup
git clone <repository-url>
cd coprra
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Visit `http://localhost:8000` to see the application.

## Prerequisites

Before you begin, ensure you have the following installed on your system:

- **PHP 8.2 or higher** with the following extensions:
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
- **Composer** (latest version)
- **Node.js** (v18 or higher)
- **NPM** (v9 or higher)
- **Docker** and **Docker Compose** (for containerized development)
- **MySQL 8.0** (if not using Docker)
- **Git**

## Installation and Setup

Follow these steps to set up the project from a fresh git clone:

### 1. Clone the Repository

```bash
git clone <repository-url>
cd coprra
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install NPM Dependencies

```bash
npm install
```

### 4. Environment Configuration

```bash
cp .env.example .env
```

**Important:** Edit the `.env` file with your specific configuration values before proceeding.

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed the database with sample data
php artisan db:seed

# Or run both in one command
php artisan migrate --seed
```

### 7. Create Storage Link

```bash
php artisan storage:link
```

### 8. Build Frontend Assets

For development (with hot reload):

```bash
npm run dev
```

For production:

```bash
npm run build
```

## Running the Application

### Using Docker (Recommended)

The project includes Docker configuration for easy development setup. You can now run Docker from the project root without specifying a file flag:

```bash
# Build or rebuild the application container
docker-compose build app

# Start all services in the background
docker-compose up -d

# Get a shell inside the running PHP-FPM container
docker-compose exec app bash

# Inside the container: install PHP dependencies
composer install

# Inside the container: run Laravel migrations as an example command
php artisan migrate

# Stop and remove the containers
docker-compose down
```

The application will be available at: `http://localhost:8000`

### Health Check Endpoint

- Unified health check endpoint: `GET /api/health`
- Returns 200 OK with minimal payload to verify service health
- Configured at `bootstrap/app.php` and proxied via Nginx in Docker

**Docker Services Include:**

- PHP 8.2 with Laravel
- MySQL 8.0 database
- Nginx web server
- Redis for caching
- Mailpit for email testing

### Using Local Environment

Start the Laravel development server:

```bash
php artisan serve
```

The application will be available at: `http://localhost:8000`

## Running Tests

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# Run tests with coverage
composer run test:coverage

# Run comprehensive test suite
composer run test:comprehensive

# Run specific test categories
composer run test:ai
composer run test:security
composer run test:performance
composer run test:integration
```

### Run Frontend Tests

```bash
# Run all frontend quality checks
npm run test:frontend

# Run individual frontend tools
npm run lint
npm run stylelint
npm run check
```

## Code Quality and Tooling

This project includes comprehensive code quality tools to maintain high standards:

### Static Analysis Tools

```bash
# PHPStan (Static Analysis)
composer run analyse:phpstan

# Psalm (Static Analysis)
composer run analyse:psalm

# PHPMD (Mess Detector)
./vendor/bin/phpmd app text cleancode,codesize,controversial,design,naming,unusedcode

# Deptrac (Architecture Testing)
./vendor/bin/deptrac analyse

# Security Checker
composer run analyse:security
```

### Code Formatting and Fixing

```bash
# Laravel Pint (Code Style)
./vendor/bin/pint

# Fix all code style issues
composer run fix:all

# Run all analysis tools
composer run analyse:all
```

### Quality Assurance

```bash
# Run complete quality check
composer run quality

# Run comprehensive measurements
composer run measure:all
```

### Frontend Code Quality

```bash
# ESLint (JavaScript linting)
npm run lint

# Fix ESLint issues
npm run lint:fix

# Stylelint (CSS linting)
npm run stylelint

# Fix Stylelint issues
npm run stylelint:fix

# Run all frontend checks
npm run check
```

## Environment Variables

The following environment variables are critical for the application to function properly:

### Required Variables

- `APP_NAME` - Application name (default: Laravel)
- `APP_ENV` - Environment (local, production, testing)
- `APP_KEY` - Application encryption key (generated automatically)

## 🐳 Docker Setup

### Development

```bash
# Start services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Install dependencies
docker-compose exec app composer install
```

### Production

```bash
# Use production compose file
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### Services

- **App:** Laravel application (PHP 8.2-FPM)
- **Nginx:** Web server (port 80)
- **MySQL:** Database (port 3306, exposed on 127.0.0.1:33061)
- **Redis:** Cache & Queue (port 6379)
- **Mailpit:** Email testing (Web UI: 8025, SMTP: 1025)

### Health Checks

Visit `http://localhost/api/health` to verify all services

- `APP_DEBUG` - Debug mode (true/false)
- `APP_URL` - Application URL
- `DB_CONNECTION` - Database connection (mysql, sqlite, etc.)
- `DB_HOST` - Database host
- `DB_PORT` - Database port
- `DB_DATABASE` - Database name
- `DB_USERNAME` - Database username
- `DB_PASSWORD` - Database password
- `MAIL_MAILER` - Mail driver (smtp, mailgun, ses, etc.)
- `MAIL_HOST` - Mail server host
- `MAIL_PORT` - Mail server port
- `MAIL_USERNAME` - Mail username
- `MAIL_PASSWORD` - Mail password
- `MAIL_ENCRYPTION` - Mail encryption (tls, ssl, null)
- `MAIL_FROM_ADDRESS` - From email address
- `MAIL_FROM_NAME` - From name

### Optional Variables

- `LOG_CHANNEL` - Log channel (stack, single, daily, etc.)
- `LOG_LEVEL` - Log level (debug, info, notice, warning, error, critical, alert, emergency)
- `CACHE_DRIVER` - Cache driver (array, database, redis, etc.)
- `SESSION_DRIVER` - Session driver (array, database, redis, etc.)
- `QUEUE_CONNECTION` - Queue driver (sync, database, redis, etc.)
- `BROADCAST_DRIVER` - Broadcast driver (log, pusher, redis, etc.)
- `FILESYSTEM_DISK` - Default filesystem disk (local, public, s3, etc.)
- `REQUIRE_2FA` - Require two-factor authentication (true/false)

## Security

- Security headers enabled globally via `SecurityHeadersMiddleware`:
    - `X-Frame-Options: SAMEORIGIN`
    - `X-Content-Type-Options: nosniff`
    - `Referrer-Policy: strict-origin-when-cross-origin`
    - `Strict-Transport-Security` (applied on HTTPS)
    - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
    - `Content-Security-Policy` (CSP) — strict, no `unsafe-inline`/`unsafe-eval`
- CSP uses a `nonce` generated per-request by `AddCspNonce`.
    - Scripts: include nonce in inline tags: `<script nonce="{{ $cspNonce }}">...</script>`
    - Styles: prefer external stylesheets; if inline styles are required, add the same nonce: `<style nonce="{{ $cspNonce }}">...</style>`
- Development allowances:
    - When `APP_ENV=local`, CSP permits the Vite dev server (`VITE_DEV_SERVER`, default `http://localhost:5173`) and WebSocket for HMR via `connect-src`.

### CORS Configuration

- CORS is environment-driven (`config/cors.php`).
    - Local: allows common dev origins (Vite/SPA and `APP_URL`).
    - Production: restricted to `APP_URL` and `FRONTEND_URL` unless `CORS_ALLOWED_ORIGINS` is explicitly set.
- Key variables:
    - `CORS_ALLOWED_ORIGINS` — comma-separated origins.
    - `CORS_ALLOWED_METHODS` — default `GET,POST,PUT,PATCH,DELETE,OPTIONS`.
    - `CORS_ALLOWED_HEADERS` — default `Accept,Authorization,Content-Type,X-Requested-With,X-CSRF-TOKEN`.
    - `CORS_EXPOSED_HEADERS`, `CORS_MAX_AGE`, `CORS_SUPPORTS_CREDENTIALS` — optional.

### Testing & Quality

- Run tests (inside container):

```bash
docker-compose exec app composer run test
```

- Static analysis:

```bash
docker-compose exec app composer run analyse
```

### Deployment Guide

- See `DEPLOYMENT.md` for a comprehensive production checklist and step-by-step guide.

### AI Integration Variables

- `OPENAI_API_KEY` - OpenAI API key for AI features
- `OPENAI_BASE_URL` - OpenAI API base URL (default: https://api.openai.com/v1)
- `OPENAI_TIMEOUT` - OpenAI request timeout (default: 30)
- `OPENAI_MAX_TOKENS` - Maximum tokens for AI responses (default: 2000)
- `OPENAI_TEMPERATURE` - AI response creativity (default: 0.5)

### Third-Party Service Variables

- `MAILGUN_DOMAIN` - Mailgun domain for email services
- `MAILGUN_SECRET` - Mailgun API secret
- `AWS_ACCESS_KEY_ID` - AWS access key for SES
- `AWS_SECRET_ACCESS_KEY` - AWS secret key for SES
- `AWS_DEFAULT_REGION` - AWS region (default: us-east-1)
- `AWS_BUCKET` - AWS S3 bucket name
- `PUSHER_APP_ID` - Pusher app ID for real-time features
- `PUSHER_APP_KEY` - Pusher app key
- `PUSHER_APP_SECRET` - Pusher app secret
- `PUSHER_HOST` - Pusher host
- `PUSHER_PORT` - Pusher port
- `PUSHER_SCHEME` - Pusher scheme (https/http)
- `PUSHER_APP_CLUSTER` - Pusher cluster

## Development Commands

### Cache Management

```bash
# Clear all caches
composer run clear-all

# Cache all configurations
composer run cache-all
```

### Database Commands

```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Seed database
php artisan db:seed

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

### Frontend Development

```bash
# Watch for changes during development
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Clean build artifacts
npm run clean
```

## Project Structure

```
coprra/
├── app/                    # Application code
│   ├── Console/           # Artisan commands
│   ├── Http/              # Controllers, middleware, requests
│   ├── Models/            # Eloquent models
│   ├── Providers/         # Service providers
│   └── Services/          # Business logic services
├── config/                 # Configuration files
├── database/              # Migrations, seeders, factories
├── dev-docker/            # Docker development setup
├── public/                # Web accessible files
│   ├── index.php          # Application entry point
│   ├── assets/            # Compiled assets
│   └── storage/           # Storage symlink
├── resources/             # Views, assets, language files
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── views/             # Blade templates
├── routes/                # Route definitions
│   ├── web.php            # Web routes
│   ├── api.php            # API routes
│   └── console.php        # Console routes
├── storage/               # Logs, cache, sessions
│   ├── app/               # Application files
│   ├── framework/         # Framework cache
│   └── logs/              # Log files
├── tests/                 # Test files
│   ├── Unit/              # Unit tests
│   ├── Feature/           # Feature tests
│   ├── Integration/       # Integration tests
│   ├── Performance/       # Performance tests
│   ├── Security/          # Security tests
│   └── AI/                # AI-related tests
├── .github/               # GitHub workflows
├── .husky/                # Git hooks
├── vendor/                # Composer dependencies
├── node_modules/          # NPM dependencies
├── composer.json          # PHP dependencies
├── package.json           # NPM dependencies
├── phpunit.xml           # PHPUnit configuration
├── phpstan.neon          # PHPStan configuration
├── psalm.xml             # Psalm configuration
├── phpmd.xml             # PHPMD configuration
├── deptrac.yaml          # Deptrac configuration
├── pint.json             # Laravel Pint configuration
├── .stylelintrc.json     # Stylelint configuration
├── eslint.config.js      # ESLint configuration
└── vite.config.js        # Vite configuration
```

## Hostinger Deployment

This project is optimized for deployment on Hostinger hosting. Follow these steps for a successful deployment:

### Prerequisites

- Hostinger hosting account with PHP 8.2+ support
- MySQL database access
- SSH access (recommended) or File Manager access
- Domain name configured

### Deployment Steps

1. **Upload Files**

    ```bash
    # Upload all files to your domain's public_html directory
    # Ensure .env file is properly configured for production
    ```

2. **Configure Environment**

    ```bash
    # Set production environment variables
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://yourdomain.com

    # Configure database connection
    DB_HOST=localhost
    DB_DATABASE=your_database_name
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

3. **Install Dependencies**

    ```bash
    composer install --optimize-autoloader --no-dev
    npm install
    npm run build
    ```

4. **Laravel Setup**

    ```bash
    php artisan key:generate
    php artisan migrate --force
    php artisan db:seed
    php artisan storage:link
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```

5. **Set Permissions**

    ```bash
    chmod -R 755 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    ```

6. **Configure Web Server**
    - Point document root to `/public` directory
    - Enable URL rewriting
    - Set proper MIME types

### Production Checklist

- [ ] Environment variables configured
- [ ] Database migrated and seeded
- [ ] Storage linked
- [ ] Caches optimized
- [ ] File permissions set
- [ ] SSL certificate installed
- [ ] Error logging configured
- [ ] Backup strategy implemented

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run the quality checks (`composer run quality`)
5. Run the test suite (`composer run test:all`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions, please open an issue in the repository or contact the development team.

## Documentation

- Start with `QUICK_START.md` for the fastest setup, then consult `README.md` for deeper context.
- Use `DOCUMENTATION_INDEX.md` as the canonical map of all documentation.
- Architecture references: `docs/COPRRA.md`, `docs/COPRRA_STRUCTURE.md`, and `CLAUDE.md`.
- CI/CD overview: `docs/CI_CD_OVERVIEW.md`.
- Operations: runbooks under `docs/runbooks/` for deployment, rollback, incidents, and cache/queue maintenance.

### Static Analysis & Style

```bash
composer run analyse:phpstan
composer run analyse:psalm
composer run format
```

### Environment Variables

- `APP_ENV` should be `local` in development and `production` in live environments.
- `APP_DEBUG` is `true` in `.env.example` for local debugging; set `false` in production.
- `HEALTH_CHECKS_ENDPOINT` defaults to `/api/health`.
- For cross-origin SPA auth, set: `SESSION_SAME_SITE=none`, `SESSION_SECURE_COOKIE=true`, and align `SESSION_DOMAIN`/`SESSION_PATH` with your domains.

## Tech Stack
- PHP `8.2+`, Laravel `12`
- MySQL `8.0`, Redis `7`
- Node.js `20` with Vite
- Docker + Docker Compose
- CI: GitHub Actions with PHPStan, Psalm, PHPMD, Pint, Deptrac, Infection, ESLint/Stylelint/Prettier

## Code Quality & Hooks
- Pre-commit runs `lint-staged`, dependency checks, and debris guard (blocks committing root `*.txt`, `*.log`, `*.out`, `test_*.php`)
- Pre-push runs static analysis, tests, audits, and reports
- Bypass temporarily with `--no-verify` if required (e.g., `git commit --no-verify`) — use sparingly

## Docker First
- Prefer Docker-based setup for a consistent environment across the team
- Quick start:
```bash
docker-compose up -d
docker-compose exec app bash
composer install && php artisan migrate --seed && php artisan key:generate
```

### Run Frontend Tests

```bash
# Run all frontend quality checks
npm run test:frontend

# Run individual frontend tools
npm run lint
npm run stylelint
npm run check
```

## Code Quality and Tooling

This project includes comprehensive code quality tools to maintain high standards:

### Static Analysis Tools

```bash
# PHPStan (Static Analysis)
composer run analyse:phpstan

# Psalm (Static Analysis)
composer run analyse:psalm

# PHPMD (Mess Detector)
./vendor/bin/phpmd app text cleancode,codesize,controversial,design,naming,unusedcode

# Deptrac (Architecture Testing)
./vendor/bin/deptrac analyse

# Security Checker
composer run analyse:security
```

### Code Formatting and Fixing

```bash
# Laravel Pint (Code Style)
./vendor/bin/pint

# Fix all code style issues
composer run fix:all

# Run all analysis tools
composer run analyse:all
```

### Quality Assurance

```bash
# Run complete quality check
composer run quality

# Run comprehensive measurements
composer run measure:all
```

### Frontend Code Quality

```bash
# ESLint (JavaScript linting)
npm run lint

# Fix ESLint issues
npm run lint:fix

# Stylelint (CSS linting)
npm run stylelint

# Fix Stylelint issues
npm run stylelint:fix

# Run all frontend checks
npm run check
```

## Environment Variables

The following environment variables are critical for the application to function properly:

### Required Variables

- `APP_NAME` - Application name (default: Laravel)
- `APP_ENV` - Environment (local, production, testing)
- `APP_KEY` - Application encryption key (generated automatically)

## 🐳 Docker Setup

### Development

```bash
# Start services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Install dependencies
docker-compose exec app composer install
```

### Production

```bash
# Use production compose file
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### Services

- **App:** Laravel application (PHP 8.2-FPM)
- **Nginx:** Web server (port 80)
- **MySQL:** Database (port 3306, exposed on 127.0.0.1:33061)
- **Redis:** Cache & Queue (port 6379)
- **Mailpit:** Email testing (Web UI: 8025, SMTP: 1025)

### Health Checks

Visit `http://localhost/api/health` to verify all services

- `APP_DEBUG` - Debug mode (true/false)
- `APP_URL` - Application URL
- `DB_CONNECTION` - Database connection (mysql, sqlite, etc.)
- `DB_HOST` - Database host
- `DB_PORT` - Database port
- `DB_DATABASE` - Database name
- `DB_USERNAME` - Database username
- `DB_PASSWORD` - Database password
- `MAIL_MAILER` - Mail driver (smtp, mailgun, ses, etc.)
- `MAIL_HOST` - Mail server host
- `MAIL_PORT` - Mail server port
- `MAIL_USERNAME` - Mail username
- `MAIL_PASSWORD` - Mail password
- `MAIL_ENCRYPTION` - Mail encryption (tls, ssl, null)
- `MAIL_FROM_ADDRESS` - From email address
- `MAIL_FROM_NAME` - From name

### Optional Variables

- `LOG_CHANNEL` - Log channel (stack, single, daily, etc.)
- `LOG_LEVEL` - Log level (debug, info, notice, warning, error, critical, alert, emergency)
- `CACHE_DRIVER` - Cache driver (array, database, redis, etc.)
- `SESSION_DRIVER` - Session driver (array, database, redis, etc.)
- `QUEUE_CONNECTION` - Queue driver (sync, database, redis, etc.)
- `BROADCAST_DRIVER` - Broadcast driver (log, pusher, redis, etc.)
- `FILESYSTEM_DISK` - Default filesystem disk (local, public, s3, etc.)
- `REQUIRE_2FA` - Require two-factor authentication (true/false)

## Security

- Security headers enabled globally via `SecurityHeadersMiddleware`:
    - `X-Frame-Options: SAMEORIGIN`
    - `X-Content-Type-Options: nosniff`
    - `Referrer-Policy: strict-origin-when-cross-origin`
    - `Strict-Transport-Security` (applied on HTTPS)
    - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
    - `Content-Security-Policy` (CSP) — strict, no `unsafe-inline`/`unsafe-eval`
- CSP uses a `nonce` generated per-request by `AddCspNonce`.
    - Scripts: include nonce in inline tags: `<script nonce="{{ $cspNonce }}">...</script>`
    - Styles: prefer external stylesheets; if inline styles are required, add the same nonce: `<style nonce="{{ $cspNonce }}">...</style>`
- Development allowances:
    - When `APP_ENV=local`, CSP permits the Vite dev server (`VITE_DEV_SERVER`, default `http://localhost:5173`) and WebSocket for HMR via `connect-src`.

### CORS Configuration

- CORS is environment-driven (`config/cors.php`).
    - Local: allows common dev origins (Vite/SPA and `APP_URL`).
    - Production: restricted to `APP_URL` and `FRONTEND_URL` unless `CORS_ALLOWED_ORIGINS` is explicitly set.
- Key variables:
    - `CORS_ALLOWED_ORIGINS` — comma-separated origins.
    - `CORS_ALLOWED_METHODS` — default `GET,POST,PUT,PATCH,DELETE,OPTIONS`.
    - `CORS_ALLOWED_HEADERS` — default `Accept,Authorization,Content-Type,X-Requested-With,X-CSRF-TOKEN`.
    - `CORS_EXPOSED_HEADERS`, `CORS_MAX_AGE`, `CORS_SUPPORTS_CREDENTIALS` — optional.

### Testing & Quality

- Run tests (inside container):

```bash
docker-compose exec app composer run test
```

- Static analysis:

```bash
docker-compose exec app composer run analyse
```

### Deployment Guide

- See `DEPLOYMENT.md` for a comprehensive production checklist and step-by-step guide.

### AI Integration Variables

- `OPENAI_API_KEY` - OpenAI API key for AI features
- `OPENAI_BASE_URL` - OpenAI API base URL (default: https://api.openai.com/v1)
- `OPENAI_TIMEOUT` - OpenAI request timeout (default: 30)
- `OPENAI_MAX_TOKENS` - Maximum tokens for AI responses (default: 2000)
- `OPENAI_TEMPERATURE` - AI response creativity (default: 0.5)

### Third-Party Service Variables

- `MAILGUN_DOMAIN` - Mailgun domain for email services
- `MAILGUN_SECRET` - Mailgun API secret
- `AWS_ACCESS_KEY_ID` - AWS access key for SES
- `AWS_SECRET_ACCESS_KEY` - AWS secret key for SES
- `AWS_DEFAULT_REGION` - AWS region (default: us-east-1)
- `AWS_BUCKET` - AWS S3 bucket name
- `PUSHER_APP_ID` - Pusher app ID for real-time features
- `PUSHER_APP_KEY` - Pusher app key
- `PUSHER_APP_SECRET` - Pusher app secret
- `PUSHER_HOST` - Pusher host
- `PUSHER_PORT` - Pusher port
- `PUSHER_SCHEME` - Pusher scheme (https/http)
- `PUSHER_APP_CLUSTER` - Pusher cluster

## Development Commands

### Cache Management

```bash
# Clear all caches
composer run clear-all

# Cache all configurations
composer run cache-all
```

### Database Commands

```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Seed database
php artisan db:seed

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

### Frontend Development

```bash
# Watch for changes during development
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Clean build artifacts
npm run clean
```

## Project Structure

```
coprra/
├── app/                    # Application code
│   ├── Console/           # Artisan commands
│   ├── Http/              # Controllers, middleware, requests
│   ├── Models/            # Eloquent models
│   ├── Providers/         # Service providers
│   └── Services/          # Business logic services
├── config/                 # Configuration files
├── database/              # Migrations, seeders, factories
├── dev-docker/            # Docker development setup
├── public/                # Web accessible files
│   ├── index.php          # Application entry point
│   ├── assets/            # Compiled assets
│   └── storage/           # Storage symlink
├── resources/             # Views, assets, language files
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── views/             # Blade templates
├── routes/                # Route definitions
│   ├── web.php            # Web routes
│   ├── api.php            # API routes
│   └── console.php        # Console routes
├── storage/               # Logs, cache, sessions
│   ├── app/               # Application files
│   ├── framework/         # Framework cache
│   └── logs/              # Log files
├── tests/                 # Test files
│   ├── Unit/              # Unit tests
│   ├── Feature/           # Feature tests
│   ├── Integration/       # Integration tests
│   ├── Performance/       # Performance tests
│   ├── Security/          # Security tests
│   └── AI/                # AI-related tests
├── .github/               # GitHub workflows
├── .husky/                # Git hooks
├── vendor/                # Composer dependencies
├── node_modules/          # NPM dependencies
├── composer.json          # PHP dependencies
├── package.json           # NPM dependencies
├── phpunit.xml           # PHPUnit configuration
├── phpstan.neon          # PHPStan configuration
├── psalm.xml             # Psalm configuration
├── phpmd.xml             # PHPMD configuration
├── deptrac.yaml          # Deptrac configuration
├── pint.json             # Laravel Pint configuration
├── .stylelintrc.json     # Stylelint configuration
├── eslint.config.js      # ESLint configuration
└── vite.config.js        # Vite configuration
```

## Hostinger Deployment

This project is optimized for deployment on Hostinger hosting. Follow these steps for a successful deployment:

### Prerequisites

- Hostinger hosting account with PHP 8.2+ support
- MySQL database access
- SSH access (recommended) or File Manager access
- Domain name configured

### Deployment Steps

1. **Upload Files**

    ```bash
    # Upload all files to your domain's public_html directory
    # Ensure .env file is properly configured for production
    ```

2. **Configure Environment**

    ```bash
    # Set production environment variables
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://yourdomain.com

    # Configure database connection
    DB_HOST=localhost
    DB_DATABASE=your_database_name
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

3. **Install Dependencies**

    ```bash
    composer install --optimize-autoloader --no-dev
    npm install
    npm run build
    ```

4. **Laravel Setup**

    ```bash
    php artisan key:generate
    php artisan migrate --force
    php artisan db:seed
    php artisan storage:link
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```

5. **Set Permissions**

    ```bash
    chmod -R 755 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    ```

6. **Configure Web Server**
    - Point document root to `/public` directory
    - Enable URL rewriting
    - Set proper MIME types

### Production Checklist

- [ ] Environment variables configured
- [ ] Database migrated and seeded
- [ ] Storage linked
- [ ] Caches optimized
- [ ] File permissions set
- [ ] SSL certificate installed
- [ ] Error logging configured
- [ ] Backup strategy implemented

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run the quality checks (`composer run quality`)
5. Run the test suite (`composer run test:all`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions, please open an issue in the repository or contact the development team.

## Documentation

- Start with `QUICK_START.md` for the fastest setup, then consult `README.md` for deeper context.
- Use `DOCUMENTATION_INDEX.md` as the canonical map of all documentation.
- Architecture references: `docs/COPRRA.md`, `docs/COPRRA_STRUCTURE.md`, and `CLAUDE.md`.
- CI/CD overview: `docs/CI_CD_OVERVIEW.md`.
- Operations: runbooks under `docs/runbooks/` for deployment, rollback, incidents, and cache/queue maintenance.

### Static Analysis & Style

```bash
composer run analyse:phpstan
composer run analyse:psalm
composer run format
```

### Environment Variables

- `APP_ENV` should be `local` in development and `production` in live environments.
- `APP_DEBUG` is `true` in `.env.example` for local debugging; set `false` in production.
- `HEALTH_CHECKS_ENDPOINT` defaults to `/api/health`.
- For cross-origin SPA auth, set: `SESSION_SAME_SITE=none`, `SESSION_SECURE_COOKIE=true`, and align `SESSION_DOMAIN`/`SESSION_PATH` with your domains.