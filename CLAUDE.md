# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**COPRRA** is an enterprise-grade Laravel 12 e-commerce and price comparison platform built with PHP 8.2+. It features advanced security, type-safe architecture with PHP 8.1+ enums, comprehensive testing (114+ tests, 95% coverage), and strict code quality standards (PHPStan Level 8).

**Key Features:**
- Shopping cart & order management with event-driven notifications
- Multi-store price comparison with external API integrations
- Role-based access control (Admin, Moderator, User, Guest)
- AI-powered product classification and recommendations
- Multi-language (Arabic/English) and multi-currency support
- Points & rewards system with financial transaction tracking
- Comprehensive security (rate limiting, SQL injection protection, security headers)

## Development Commands

### Setup & Installation
```bash
# Initial setup from fresh clone
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
composer run test:ai              # AI-related tests
composer run test:security        # Security tests
composer run test:performance     # Performance tests
composer run test:integration     # Integration tests

# Run with coverage
composer run test:coverage

# Run comprehensive suite
composer run test:comprehensive

# Run single test file
php artisan test tests/Feature/Models/ProductTest.php

# Run single test method
php artisan test --filter=test_can_create_product
```

### Code Quality & Analysis
```bash
# Code formatting
./vendor/bin/pint                 # Fix code style with Laravel Pint
composer run format               # Same as above
composer run format-test          # Check code style without fixing

# Static analysis
composer run analyse:phpstan      # Run PHPStan (Level max)
composer run analyse:psalm        # Run Psalm
composer run analyse:security     # Security vulnerability check
composer run analyse:all          # Run all analysis tools

# Code quality check
./vendor/bin/phpmd app text cleancode,codesize,controversial,design,naming,unusedcode

# Run everything
composer run quality              # Format, analyze, test
composer run measure:all          # Comprehensive quality check
```

### Frontend Development
```bash
# Development with hot reload
npm run dev

# Production build
npm run build

# Code quality
npm run lint                      # ESLint
npm run lint:fix                 # Fix ESLint issues
npm run stylelint                # Stylelint CSS
npm run stylelint:fix            # Fix Stylelint issues
npm run check                     # Run all frontend checks
npm run test:frontend             # Run lint + stylelint
```

### Database
```bash
# Migrations
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed

# Seeders
php artisan db:seed
php artisan db:seed --class=ProductSeeder
```

### Cache Management
```bash
# Clear all caches
composer run clear-all           # Clears cache, config, routes, views
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache all
composer run cache-all           # Cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Hostinger Deployment
```bash
# Production deployment
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Architecture Overview

### Directory Structure
```
app/
├── Console/Commands/          # Artisan commands (UpdatePricesCommand, OptimizeDatabase, etc.)
├── Contracts/                 # Service interfaces (FileSecurityService, UserBanService, etc.)
├── DataObjects/               # DTOs (StorageStatistics, StorageBreakdown, etc.)
├── Enums/                     # Type-safe enums (OrderStatus, UserRole, NotificationStatus)
├── Events/                    # Event classes for event-driven architecture
├── Exceptions/                # Custom exceptions (GlobalExceptionHandler, Handler)
├── Http/
│   ├── Controllers/           # Controllers (organized by domain)
│   │   ├── Admin/            # Admin-specific controllers
│   │   ├── Api/              # API controllers with versioning support
│   │   └── Auth/             # Authentication controllers
│   ├── Middleware/           # Custom middleware (SecurityHeaders, RateLimiting, etc.)
│   └── Requests/             # Form Request classes for validation
├── Listeners/                 # Event listeners
├── Models/                    # Eloquent models with relationships
├── Policies/                  # Authorization policies
├── Providers/                 # Service providers (12+ providers for modularity)
│   ├── AppServiceProvider.php
│   ├── CoprraServiceProvider.php
│   ├── ApiServiceProvider.php
│   └── SecurityHeadersServiceProvider.php
├── Repositories/              # Repository pattern (ProductRepository)
├── Rules/                     # Custom validation rules
├── Schemas/                   # API response schemas (ProductSchema, PaginationSchema)
└── Services/                  # Business logic services (50+ services)
    ├── AI/                    # AI-related services (StrictQualityAgent, ContinuousQualityMonitor)
    ├── Activity/              # Activity tracking
    ├── AgentFixer/            # Agent-based code fixing
    ├── Api/                   # API-specific services
    ├── Backup/                # Backup services
    ├── CDN/                   # CDN management
    ├── Compression/           # Compression services
    ├── ExchangeRates/         # Currency exchange rate services
    ├── FileCleanup/           # File cleanup services
    ├── LogProcessing/         # Log processing
    ├── Performance/           # Performance monitoring
    ├── PriceUpdate/           # Price update services
    ├── Product/               # Product-specific services
    ├── SEO/                   # SEO services
    ├── Security/              # Security services
    ├── StoreAdapters/         # External store adapters (Amazon, eBay, Noon, etc.)
    └── Validators/            # Validation services
```

### Key Architectural Patterns

#### 1. **Service Layer Architecture**
- All business logic resides in dedicated service classes in `app/Services/`
- Services are registered as singletons in service providers
- Controllers are thin and delegate to services
- Example: `ProductService`, `PriceSearchService`, `OrderService`

#### 2. **Repository Pattern**
- Data access logic is abstracted through repositories
- `ProductRepository` handles all database queries for products
- Repositories injected via dependency injection

#### 3. **Contract-Based Design**
- Interfaces defined in `app/Contracts/` for all major services
- Enables loose coupling and easier testing
- Examples: `FileSecurityService`, `UserBanService`, `StoreAdapter`

#### 4. **Type-Safe Enums (PHP 8.1+)**
- All enums extend from backed enums with utility methods
- Located in `app/Enums/`
- Use `HasEnumUtilities` trait for common functionality
- Examples: `OrderStatus`, `UserRole`, `NotificationStatus`
- Pattern: Enums have `label()`, `color()`, and domain-specific methods

#### 5. **Event-Driven Architecture**
- Events in `app/Events/`, Listeners in `app/Listeners/`
- Used for notifications (order updates, price alerts)
- Registered in `EventServiceProvider`

#### 6. **Form Request Validation**
- All input validation happens in Form Request classes
- Located in `app/Http/Requests/`
- Pattern: `{Action}{Resource}Request` (e.g., `StoreProductRequest`, `UpdateUserRequest`)

#### 7. **API Architecture**
- RESTful API with versioning support (`/api/v1/`)
- API controllers in `app/Http/Controllers/Api/`
- Sanctum for authentication
- Response schemas in `app/Schemas/` for consistency
- Rate limiting: `throttle:public`, `throttle:authenticated`, `throttle:admin`

#### 8. **Store Adapter Pattern**
- External store integrations via adapter pattern
- Base interface: `app/Contracts/StoreAdapter.php`
- Implementations in `app/Services/StoreAdapters/` (Amazon, eBay, Noon, etc.)
- Managed by `StoreAdapterManager`

#### 9. **Multi-Provider Architecture**
- 12+ service providers for modular registration
- Domain-specific providers: `CoprraServiceProvider`, `ApiServiceProvider`
- Feature providers: `SecurityHeadersServiceProvider`, `CompressionServiceProvider`

#### 10. **DTO Pattern**
- Data Transfer Objects in `app/DataObjects/`
- Used for complex data structures (e.g., `StorageStatistics`, `AnalysisResult`)

### Routes Architecture

#### Web Routes (`routes/web.php`)
- Public routes: Products, categories, search (no auth)
- Locale switching: `/language/{langCode}`, `/currency/{currencyCode}`
- Protected routes (auth middleware): Profile, cart, wishlist, reviews, price alerts
- Admin routes: Prefix `/admin`, middleware `['auth', 'admin']`
- AI Control Panel: `/admin/ai/*`

#### API Routes (`routes/api.php`)
- Authentication: `/api/login`, `/api/register` (Sanctum)
- Public API: `throttle:public` middleware
- Authenticated API: `throttle:authenticated` middleware
- Admin API: `throttle:admin` middleware
- Versioned endpoints: `/api/v1/*`
- Webhooks: `/api/webhooks/{store}` (signature verification)
- Test routes: AI endpoints (`/api/ai/*`), external data simulation

### Testing Architecture

#### Test Suites (defined in phpunit.xml)
- **Unit**: `tests/Unit/` - Isolated unit tests
- **Feature**: `tests/Feature/` - Integration with framework
- **AI**: `tests/AI/` - AI service tests
- **Security**: `tests/Security/` - Security tests (XSS, CSRF, SQL injection)
- **Performance**: `tests/Performance/` - Performance benchmarks
- **Integration**: `tests/Integration/` - End-to-end workflows

#### Test Configuration
- Uses SQLite in-memory for speed: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`
- Array drivers for cache/session
- Test utilities in `tests/TestUtilities/`
- Custom test base classes: `SafeTestBase`, `AIBaseTestCase`

### Important Configuration

#### Environment Variables (from .env.example)
**COPRRA-Specific:**
- `COPRRA_DEFAULT_CURRENCY`, `COPRRA_DEFAULT_LANGUAGE`
- `PRICE_CACHE_DURATION`, `MAX_STORES_PER_PRODUCT`
- `API_RATE_LIMIT`, `API_VERSION`, `API_ENABLE_DOCS`
- Exchange rates: `EXCHANGE_RATE_*`

**Security:**
- `REQUIRE_2FA=true` - Two-factor authentication requirement

**AI Integration:**
- `OPENAI_API_KEY`, `OPENAI_BASE_URL`, `OPENAI_MAX_TOKENS`, `OPENAI_TEMPERATURE`

#### Multi-Language & RTL Support
- Locale middleware: `LocaleMiddleware`, `RTLMiddleware`
- Session-based language switching
- User-specific locale settings: `UserLocaleSetting` model
- Translation files in `resources/lang/`

### Code Quality Standards

#### Strict Type Safety
- All files use `declare(strict_types=1);`
- PHPStan Level max configuration
- Larastan for Laravel-specific analysis
- Return type declarations required

#### Code Style
- Laravel Pint for PSR-12 compliance
- Configured in `pint.json` (if exists) or uses defaults
- Pre-commit hooks via Husky (`package.json` lint-staged)
- Auto-formatting: PHP (Pint), JS/CSS (Prettier)

#### Security Best Practices
- All user input validated via Form Requests
- CSRF protection via `VerifyCsrfToken` middleware
- SQL injection protection via Eloquent ORM
- XSS prevention via Blade templating
- Security headers via `SecurityHeadersMiddleware`
- Rate limiting on sensitive routes
- Password policies via `PasswordPolicyService`

### Key Services to Know

#### Core Services
- **ProductService**: Product CRUD, caching, search
- **PriceSearchService**: Multi-store price comparison
- **OrderService**: Order processing, status management
- **AIService**: Text analysis, product classification, recommendations
- **NotificationService**: User notifications
- **CacheService**: Centralized caching logic
- **ExchangeRateService**: Currency conversion
- **WebhookService**: External webhook processing

#### Security Services
- **FileSecurityService**: File upload validation
- **LoginAttemptService**: Brute force protection
- **PasswordPolicyService**: Password validation
- **UserBanService**: User ban management
- **SuspiciousActivityService**: Anomaly detection

#### Analysis Services
- **QualityAnalysisService**: Code quality analysis
- **PerformanceAnalysisService**: Performance metrics
- **SecurityAnalysisService**: Security auditing
- **TestAnalysisService**: Test suite analysis

### Database Conventions

#### Model Relationships
- Use eager loading to avoid N+1 queries
- Soft deletes on major models (products, categories, brands, stores)
- Encrypted fields for sensitive data (via migration `2025_01_15_000002_add_encrypted_fields`)

#### Indexes & Performance
- Performance indexes added via dedicated migrations
- Foreign key constraints enforced
- Database optimization via `OptimizeDatabase` command

### Frontend Stack

#### Technologies
- **Vite**: Build tool with HMR
- **Alpine.js**: Lightweight reactive framework
- **GSAP**: Animations
- **Axios**: HTTP client
- **Laravel Vite Plugin**: Laravel integration

#### Asset Organization
- Entry points: `resources/css/app.css`, `resources/js/app.js`
- Vite aliases: `@` → `/resources/js`, `~` → `/resources`
- Code splitting: vendor chunks, utils chunks
- Critical CSS: `resources/css/critical.css`

#### Build Configuration
- Production: Terser minification, console stripping
- Development: Source maps, HMR on port 5173
- Chunk size limit: 1000kb

### Git Workflow

#### Branches
- `main`: Production-ready code
- `develop`: Development branch
- Feature branches: `feature/amazing-feature`
- Fix branches: `fix/invalid-fixes-*`

#### CI/CD
- GitHub Actions workflows in `.github/workflows/`
- CI runs: PHPUnit, PHPStan, Pint, frontend tests
- MySQL 8.0 service for integration tests
- Caches: Composer dependencies, npm packages

## Development Guidelines

### When Adding New Features

1. **Create necessary files:**
   - Migration for database changes
   - Model with relationships and casts
   - Form Requests for validation
   - Service class for business logic
   - Controller method (thin, delegates to service)
   - Tests (Unit + Feature)

2. **Follow naming conventions:**
   - Controllers: `{Resource}Controller`
   - Services: `{Domain}Service`
   - Requests: `{Action}{Resource}Request`
   - Models: Singular (Product, Order)
   - Tables: Plural (products, orders)

3. **Register dependencies:**
   - Add service bindings to appropriate Service Provider
   - Update `config/app.php` if adding new provider
   - Add routes to `routes/web.php` or `routes/api.php`

4. **Maintain type safety:**
   - Use strict types declaration
   - Define return types
   - Use enums for fixed value sets
   - Document complex types with PHPDoc

### When Modifying Existing Code

1. **Run tests before changes:**
   ```bash
   composer run test
   ```

2. **After changes, run quality checks:**
   ```bash
   composer run quality
   ```

3. **Update related tests** to reflect changes

4. **Check PHPStan** for type errors:
   ```bash
   composer run analyse:phpstan
   ```

### Working with External Stores

- Implement `StoreAdapter` contract
- Add adapter to `app/Services/StoreAdapters/`
- Register in `StoreAdapterManager`
- Add webhook route in `routes/api.php` under `/webhooks/{store}`
- Implement signature verification for security

### AI Service Integration

- Uses OpenAI API (configurable)
- Main methods: `analyzeText()`, `classifyProduct()`, `generateRecommendations()`, `analyzeImage()`
- Test with mock service: `tests/AI/MockAIService.php`
- Rate limited endpoints: Use `throttle:public` middleware

## Troubleshooting

### Common Issues

**Tests failing with database errors:**
- Ensure migrations are up to date
- Check SQLite is available for in-memory testing
- Clear test cache: `php artisan config:clear`

**PHPStan errors:**
- Check `phpstan.neon` for exclusions
- Use stub files in `phpstan/` directory for third-party packages
- Run with memory limit: `php -d memory_limit=1G ./vendor/bin/phpstan analyse`

**Frontend build issues:**
- Clear Vite cache: `npm run clean`
- Reinstall dependencies: `rm -rf node_modules && npm install`
- Check Node version: Requires v18+

**Hostinger deployment issues:**
- Ensure PHP 8.2+ is available
- Check file permissions on `storage/` and `bootstrap/cache/`
- Verify `.env` is properly configured
- Run optimization commands

## Performance Optimization

### Caching Strategy
- Route caching: `php artisan route:cache`
- Config caching: `php artisan config:cache`
- View caching: `php artisan view:cache`
- Application optimization: `php artisan optimize`
- Database query caching via `CacheService`

### Database Optimization
- Use eager loading for relationships
- Leverage database indexes
- Run optimization: `php artisan optimize:database`

### Asset Optimization
- Production build: `npm run build`
- CDN configuration: `CDN_URL` in `.env`
- Image optimization via `ImageOptimizationService`

## Useful Artisan Commands

```bash
# COPRRA-specific commands
php artisan stats                    # Show application statistics
php artisan update:prices            # Update product prices
php artisan generate:translations    # Generate translation files
php artisan optimize:database        # Optimize database
php artisan generate:sitemap         # Generate SEO sitemap
php artisan seo:audit                # Run SEO audit
php artisan agent:propose-fix        # AI-powered code fix proposals
php artisan cache:management         # Cache management
php artisan clean:analytics          # Clean old analytics data
php artisan process:webhooks         # Process pending webhooks
php artisan exchange-rates:update    # Update currency exchange rates
```

## Updated Developer Guidance
- Prefer Docker-first workflow; see `README.md` Docker section
- Use `scripts/cleanup.sh` to relocate root debris (`*.txt`, `*.log`, `*.out`, `test_*.php`) into `storage/reports/`
- Reference new operations docs under `docs/` (e.g., `docs/DEPLOYMENT.md`, runbooks)
- Pre-commit/pre-push hooks run QA; bypass temporarily with `--no-verify` when appropriate