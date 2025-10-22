# 📚 COPRRA Documentation

**COPRRA** - Advanced Price Comparison Platform

Version: 1.0.0
Last Updated: 2025-10-01

---

## 📖 Table of Contents

1. [Introduction](#introduction)
2. [Installation & Setup](#installation--setup)
3. [Configuration](#configuration)
4. [Core Components](#core-components)
5. [API Documentation](#api-documentation)
6. [Usage Examples](#usage-examples)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 Introduction

COPRRA is a comprehensive price comparison platform built with Laravel 12. It allows users to compare prices across multiple stores, track price changes, and find the best deals.

### Key Features

- ✅ Multi-store price comparison
- ✅ Dynamic exchange rate system
- ✅ Multi-currency support (USD, EUR, GBP, JPY, SAR, AED, EGP)
- ✅ Multi-language support (EN, AR)
- ✅ Real-time price tracking
- ✅ Price alerts and notifications
- ✅ Advanced search and filtering
- ✅ RESTful API
- ✅ Comprehensive testing suite

---

## 🚀 Installation & Setup

### Prerequisites

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer
- Node.js & NPM

### Step 1: Clone and Install

```bash
git clone <repository-url>
cd coprra
composer install
npm install
```

### Step 2: Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

### Step 3: Database Setup

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coprra
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations:

```bash
php artisan migrate
```

### Step 4: Seed Initial Data

```bash
# Seed exchange rates from config
php artisan exchange-rates:update --seed

# Seed all data
php artisan db:seed
```

### Step 5: Build Assets

```bash
npm run build
```

### Step 6: Start Development Server

```bash
php artisan serve
```

Visit: `http://localhost:8000`

---

## ⚙️ Configuration

### COPRRA Configuration File

Location: `config/coprra.php`

#### Application Settings

```php
'name' => env('COPRRA_NAME', 'COPRRA'),
'version' => '1.0.0',
'default_currency' => env('COPRRA_DEFAULT_CURRENCY', 'USD'),
'default_language' => env('COPRRA_DEFAULT_LANGUAGE', 'en'),
```

#### Price Comparison Settings

```php
'price_comparison' => [
    'cache_duration' => env('PRICE_CACHE_DURATION', 3600),
    'max_stores_per_product' => env('MAX_STORES_PER_PRODUCT', 10),
    'price_update_interval' => env('PRICE_UPDATE_INTERVAL', 6),
],
```

#### Exchange Rates

```php
'exchange_rates' => [
    'USD' => 1.0,
    'EUR' => 0.85,
    'GBP' => 0.73,
    'JPY' => 110.0,
    'SAR' => 3.75,
    'AED' => 3.67,
    'EGP' => 30.9,
],
```

### Environment Variables

All COPRRA settings can be configured via `.env`:

```env
# COPRRA Application Settings
COPRRA_NAME=COPRRA
COPRRA_DEFAULT_CURRENCY=USD
COPRRA_DEFAULT_LANGUAGE=en

# Price Comparison Settings
PRICE_CACHE_DURATION=3600
MAX_STORES_PER_PRODUCT=10
PRICE_UPDATE_INTERVAL=6

# Exchange Rates API (Optional)
EXCHANGE_RATE_API_KEY=
EXCHANGE_RATE_API_URL=https://api.exchangerate-api.com/v4/latest/USD
```

---

## 🧩 Core Components

### 1. PriceHelper

Location: `app/Helpers/PriceHelper.php`

Utility class for price manipulation and formatting.

#### Methods

**formatPrice(float $price, ?string $currencyCode = null): string**
```php
use App\Helpers\PriceHelper;

$formatted = PriceHelper::formatPrice(100.50, 'USD');
// Output: "$100.50"
```

**calculatePriceDifference(float $originalPrice, float $comparePrice): float**
```php
$difference = PriceHelper::calculatePriceDifference(100.0, 80.0);
// Output: -20.0 (20% discount)
```

**convertCurrency(float $amount, string $from, string $to): float**
```php
$converted = PriceHelper::convertCurrency(100.0, 'USD', 'EUR');
// Output: 85.0
```

**isGoodDeal(float $price, array $allPrices): bool**
```php
$isGood = PriceHelper::isGoodDeal(80.0, [100.0, 90.0, 85.0]);
// Output: true
```

**getBestPrice(array $prices): ?float**
```php
$best = PriceHelper::getBestPrice([100.0, 80.0, 90.0]);
// Output: 80.0
```

### 2. ExchangeRateService

Location: `app/Services/ExchangeRateService.php`

Service for managing dynamic exchange rates.

#### Methods

**getRate(string $fromCurrency, string $toCurrency): float**
```php
use App\Services\ExchangeRateService;

$service = app(ExchangeRateService::class);
$rate = $service->getRate('USD', 'EUR');
```

**convert(float $amount, string $from, string $to): float**
```php
$converted = $service->convert(100.0, 'USD', 'SAR');
```

**fetchAndStoreRates(): int**
```php
$count = $service->fetchAndStoreRates();
// Fetches rates from external API and stores in database
```

### 3. CoprraServiceProvider

Location: `app/Providers/CoprraServiceProvider.php`

Bootstraps COPRRA functionality and registers Blade directives.

#### Blade Directives

**@currency**
```blade
<p>Price: @currency(100.50)</p>
<!-- Output: Price: 100.50 -->
```

**@pricecompare**
```blade
<p>Price: @pricecompare(100.50, 'USD')</p>
<!-- Output: Price: $100.50 -->
```

**@rtl**
```blade
<html dir="@rtl">
<!-- Output: <html dir="rtl"> for Arabic, <html dir="ltr"> for English -->
```

### 4. Artisan Commands

**Update Exchange Rates**
```bash
# Update from API
php artisan exchange-rates:update

# Seed from config
php artisan exchange-rates:update --seed

# Force update
php artisan exchange-rates:update --force
```

---

## 🔌 API Documentation

### Base URL

```
http://localhost:8000/api/v1
```

### Authentication

All API requests require authentication using Laravel Sanctum tokens.

```http
Authorization: Bearer {token}
```

### Rate Limiting

- Default: 100 requests per minute
- Configurable via `API_RATE_LIMIT` in `.env`

### Endpoints

#### Products

**GET /api/v1/products**
```http
GET /api/v1/products?page=1&per_page=20
```

Response:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "slug": "product-name",
      "price": 100.00,
      "currency": "USD",
      "stores": [
        {
          "store_id": 1,
          "store_name": "Store A",
          "price": 100.00,
          "url": "https://store-a.com/product"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 100
  }
}
```

**GET /api/v1/products/{id}**
```http
GET /api/v1/products/1
```

#### Price Comparison

**GET /api/v1/products/{id}/compare**
```http
GET /api/v1/products/1/compare
```

Response:
```json
{
  "product": {
    "id": 1,
    "name": "Product Name"
  },
  "prices": [
    {
      "store": "Store A",
      "price": 100.00,
      "currency": "USD",
      "is_best_deal": false
    },
    {
      "store": "Store B",
      "price": 85.00,
      "currency": "USD",
      "is_best_deal": true
    }
  ],
  "best_price": 85.00,
  "average_price": 92.50,
  "savings": 15.00
}
```

#### Exchange Rates

**GET /api/v1/exchange-rates**
```http
GET /api/v1/exchange-rates
```

**GET /api/v1/exchange-rates/convert**
```http
GET /api/v1/exchange-rates/convert?amount=100&from=USD&to=EUR
```

---

## 💡 Usage Examples

### Example 1: Display Product with Price Comparison

```blade
@foreach($products as $product)
    <div class="product-card">
        <h3>{{ $product->name }}</h3>
        <p>Price: @pricecompare($product->price, $product->currency->code)</p>

        @if($product->stores->count() > 1)
            <div class="price-comparison">
                <h4>Available at:</h4>
                @foreach($product->stores as $store)
                    <div class="store-price">
                        <span>{{ $store->name }}</span>
                        <span>@pricecompare($store->pivot->price, $product->currency->code)</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach
```

### Example 2: Currency Conversion

```php
use App\Helpers\PriceHelper;

// Convert USD to SAR
$priceInUSD = 100.00;
$priceInSAR = PriceHelper::convertCurrency($priceInUSD, 'USD', 'SAR');

echo "Price in USD: $" . $priceInUSD;
echo "Price in SAR: ر.س" . $priceInSAR;
```

### Example 3: Find Best Deal

```php
use App\Helpers\PriceHelper;

$prices = [100.00, 95.00, 110.00, 85.00];
$bestPrice = PriceHelper::getBestPrice($prices);

foreach ($prices as $price) {
    $isGood = PriceHelper::isGoodDeal($price, $prices);
    echo "Price: $price - " . ($isGood ? "Good Deal!" : "Not the best");
}
```

---

## 🧪 Testing

### Run All Tests

```bash
php artisan test
```

### Run COPRRA Tests Only

```bash
php artisan test tests/Unit/COPRRA/
php artisan test tests/Feature/COPRRA/
```

### Run Specific Test

```bash
php artisan test --filter=PriceHelperTest
```

### Test Coverage

- **Unit Tests**: 58 tests
- **Feature Tests**: 15 tests
- **Total**: 73 tests

---

## 🔧 Troubleshooting

### Issue: Exchange rates not updating

**Solution:**
```bash
# Check if scheduled tasks are running
php artisan schedule:list

# Manually update rates
php artisan exchange-rates:update

# Check logs
tail -f storage/logs/laravel.log
```

### Issue: Prices not displaying correctly

**Solution:**
1. Clear cache: `php artisan cache:clear`
2. Check currency configuration in `config/coprra.php`
3. Verify database has currency records

### Issue: API returns 429 (Too Many Requests)

**Solution:**
- Increase rate limit in `.env`: `API_RATE_LIMIT=200`
- Clear cache: `php artisan cache:clear`

---

## 📞 Support

For issues and questions:
- Email: info@coprra.com
- Documentation: `/docs`
- GitHub Issues: [repository-url]/issues

---

## 🏗️ Architecture

### System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                     Frontend Layer                       │
│  (Blade Templates, Livewire, Alpine.js, Tailwind CSS)  │
└─────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────┐
│                   Application Layer                      │
│         (Controllers, Services, Helpers)                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ PriceHelper  │  │ExchangeRate  │  │   COPRRA     │ │
│  │              │  │   Service    │  │   Provider   │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────┐
│                     Data Layer                           │
│  (Eloquent Models, Database, Cache, External APIs)     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   Products   │  │ExchangeRates │  │    Stores    │ │
│  │   Prices     │  │  Currencies  │  │  Categories  │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Database Schema

#### Key Tables

**products**
- id, name, slug, description, price, currency_id, category_id
- Relationships: belongsTo(Currency), belongsTo(Category), belongsToMany(Stores)

**stores**
- id, name, slug, url, logo, is_active
- Relationships: belongsToMany(Products)

**exchange_rates**
- id, from_currency, to_currency, rate, source, fetched_at
- Indexes: (from_currency, to_currency), updated_at

**currencies**
- id, code, name, symbol, exchange_rate, decimal_places

---

## 🔐 Security

### Best Practices

1. **API Authentication**: Always use Sanctum tokens
2. **Rate Limiting**: Configured per endpoint
3. **CSRF Protection**: Enabled for all forms
4. **XSS Protection**: Blade escaping by default
5. **SQL Injection**: Eloquent ORM prevents injection

### Security Configuration

```env
# Security Settings
ENABLE_2FA=false
PASSWORD_MIN_LENGTH=8
SESSION_TIMEOUT=120
```

---

## 🚀 Performance Optimization

### Caching Strategy

**Price Comparison Cache**
```php
// Cache key format
$cacheKey = "price_comparison_{$productId}";
$duration = config('coprra.price_comparison.cache_duration', 3600);

// Usage
$prices = Cache::remember($cacheKey, $duration, function() {
    return $this->fetchPrices();
});
```

**Exchange Rates Cache**
```php
// Cached for 24 hours
$cacheKey = "exchange_rate_{$from}_{$to}";
Cache::put($cacheKey, $rate, 86400);
```

### Database Optimization

1. **Indexes**: All foreign keys and frequently queried columns
2. **Eager Loading**: Use `with()` to prevent N+1 queries
3. **Query Caching**: Enabled via config

```php
// Good: Eager loading
$products = Product::with(['stores', 'currency'])->get();

// Bad: N+1 problem
$products = Product::all();
foreach ($products as $product) {
    $product->stores; // Separate query for each product
}
```

---

## 📊 Monitoring & Analytics

### Logging

All important events are logged:

```php
// Exchange rate updates
Log::info('Exchange rates updated', ['count' => $count]);

// Price comparisons
Log::info('Price comparison performed', [
    'product_id' => $productId,
    'stores_count' => $storesCount
]);
```

### Analytics Configuration

```env
# Analytics and Tracking
GOOGLE_ANALYTICS_ID=
TRACK_USER_BEHAVIOR=true
TRACK_PRICE_CLICKS=true
```

---

## 🔄 Scheduled Tasks

### Cron Configuration

Add to your crontab:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks List

```bash
php artisan schedule:list
```

**Daily Tasks:**
- Exchange rates update (2:00 AM)
- Log pruning
- Cache cleanup
- Session garbage collection

---

## 🌐 Internationalization (i18n)

### Supported Languages

- English (en)
- Arabic (ar)

### Adding New Language

1. Create language files in `resources/lang/{locale}/`
2. Update config: `config/coprra.php`
3. Add translations for all keys

### Usage

```php
// In controllers
app()->setLocale('ar');

// In Blade
{{ __('messages.welcome') }}

// RTL Support
<html dir="@rtl">
```

---

## 🔌 Extending COPRRA

### Adding New Currency

1. Add to database:
```php
Currency::create([
    'code' => 'CNY',
    'name' => 'Chinese Yuan',
    'symbol' => '¥',
    'exchange_rate' => 6.45,
    'decimal_places' => 2,
]);
```

2. Update config:
```php
'exchange_rates' => [
    'CNY' => 6.45,
],
```

3. Update ExchangeRateService:
```php
private const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'JPY', 'SAR', 'AED', 'EGP', 'CNY'];
```

### Adding New Store Adapter

Create adapter in `app/Services/StoreAdapters/`:

```php
namespace App\Services\StoreAdapters;

class AmazonAdapter implements StoreAdapterInterface
{
    public function fetchPrice(string $productUrl): float
    {
        // Implementation
    }

    public function fetchProductDetails(string $productUrl): array
    {
        // Implementation
    }
}
```

---

## 📝 Contributing

### Development Workflow

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push to branch: `git push origin feature/amazing-feature`
5. Open Pull Request

### Code Standards

- **PSR-12**: PHP coding standard
- **PHPStan Level 8**: Static analysis
- **Laravel Pint**: Code formatting
- **100% Test Coverage**: For critical components

### Running Quality Checks

```bash
# Code formatting
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse

# Tests
php artisan test
```

---

## 📚 Additional Resources

### Official Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [Livewire Documentation](https://livewire.laravel.com)
- [Tailwind CSS](https://tailwindcss.com)

### External APIs

- [Exchange Rate API](https://exchangerate-api.com)
- [Open Exchange Rates](https://openexchangerates.org)

### Community

- GitHub Discussions
- Discord Server
- Stack Overflow Tag: `coprra`

---

**Last Updated:** 2025-10-01
**Version:** 1.0.0
**Maintained by:** COPRRA Development Team
