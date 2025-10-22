---
description: Repository Information Overview
alwaysApply: true
---

# Coprra Information

## Summary
Coprra is a sophisticated Laravel e-commerce platform built with PHP 8.2+ and Laravel 12. It provides a robust foundation for modern online retail with features like shopping cart management, enterprise security, role-based access control, and event-driven notifications.

## Structure
- **app/** - Application code (Controllers, Models, Services)
- **config/** - Configuration files
- **database/** - Migrations, seeders, factories
- **dev-docker/** - Docker development setup
- **public/** - Web accessible files
- **resources/** - Views, assets, language files
- **routes/** - Route definitions
- **tests/** - Comprehensive test suites (Unit, Feature, Integration, Performance, Security, AI)

## Language & Runtime
**Language**: PHP 8.2+
**Framework**: Laravel 12
**Build System**: Composer (PHP), npm/Vite (Frontend)
**Package Manager**: Composer (PHP), npm (JavaScript)

## Dependencies
**Main Dependencies**:
- laravel/framework (^12.0)
- laravel/sanctum (^3.3|^4.0)
- laravel/cashier (^16.0.1)
- laravel/telescope (^5.12.0)
- spatie/laravel-permission (^6.21.0)
- livewire/livewire (^3.0)
- guzzlehttp/guzzle (^7.2)

**Development Dependencies**:
- phpunit/phpunit (^10.0)
- phpstan/phpstan (^2.1)
- larastan/larastan (^3.7)
- vimeo/psalm (^6.13)
- enlightn/security-checker (^2.0)

## Build & Installation
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

## Docker
**Dockerfile**: dev-docker/Dockerfile
**Image**: PHP 8.2-fpm
**Configuration**: Docker Compose with services for app (PHP-FPM), nginx, and MySQL 8.0
**Run Command**:
```bash
docker-compose -f dev-docker/docker-compose.yml up -d
```

## Testing
**Framework**: PHPUnit
**Test Location**: tests/ directory with specialized subdirectories
**Naming Convention**: *Test.php suffix
**Configuration**: phpunit.xml
**Run Command**:
```bash
# Run all tests
php artisan test

# Run specific test suites
composer run test:ai
composer run test:security
composer run test:performance
composer run test:integration
composer run test:comprehensive
```

## Frontend
**Framework**: Vite
**Language**: JavaScript
**Styling**: CSS/SCSS
**Build Command**:
```bash
# Development
npm run dev

# Production
npm run build
```

## Quality Tools
**PHP Linting**: Laravel Pint
**Static Analysis**: PHPStan (Level 8), Psalm
**JS/CSS Linting**: ESLint, Stylelint
**Run Commands**:
```bash
# PHP quality checks
composer run analyse:all

# Frontend quality checks
npm run check
```
