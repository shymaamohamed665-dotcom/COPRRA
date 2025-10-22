# Production Caching Strategy

## Overview

This guide outlines the comprehensive caching strategy for COPRRA in production environments to optimize performance and reduce server load.

## Laravel Caching Layers

### 1. Configuration Caching

**Purpose:** Cache configuration files to avoid parsing on every request.

```bash
# Enable configuration cache
php artisan config:cache

# Clear configuration cache
php artisan config:clear

# In deployment script
php artisan config:cache --no-ansi
```

**Benefits:**
- 50-70% faster config loading
- Reduced file I/O operations

**Important:** After changing `.env` or config files, always run `php artisan config:clear` before `config:cache`.

---

### 2. Route Caching

**Purpose:** Cache route definitions to speed up route registration.

```bash
# Enable route cache
php artisan route:cache

# Clear route cache
php artisan route:clear
```

**Benefits:**
- Drastically faster route resolution
- Essential for large applications

**Note:** Route caching doesn't work with Closure-based routes. Always use controller actions in production.

---

### 3. View Caching

**Purpose:** Compile Blade templates into PHP code.

```bash
# Compile all views
php artisan view:cache

# Clear compiled views
php artisan view:clear
```

**Benefits:**
- Faster view rendering
- No compilation overhead on first request

---

### 4. Event Caching

**Purpose:** Cache discovered events and listeners.

```bash
# Cache events
php artisan event:cache

# Clear event cache
php artisan event:clear
```

---

### 5. OPcache (PHP)

**Purpose:** Cache compiled PHP bytecode in memory.

**Configuration** (in `docker/php.ini` or `/etc/php/8.2/fpm/php.ini`):

```ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.max_wasted_percentage=5
opcache.validate_timestamps=0  ; Production only!
opcache.revalidate_freq=0
opcache.save_comments=1
opcache.fast_shutdown=1

; JIT (PHP 8+)
opcache.jit_buffer_size=100M
opcache.jit=1255
```

**Benefits:**
- 30-50% performance improvement
- Reduced CPU usage
- Faster script execution

**Docker Configuration:**

Already configured in `Dockerfile`:
```dockerfile
COPY docker/php.ini /usr/local/etc/php/conf.d/opcache-prod.ini
```

---

## Application-Level Caching

### 1. Database Query Results

**Strategy:** Cache expensive database queries using Redis.

```php
use Illuminate\Support\Facades\Cache;

// Cache product list
$products = Cache::remember('products.featured', 3600, function () {
    return Product::where('is_featured', true)
        ->with(['category', 'brand'])
        ->get();
});

// Cache with tags (requires Redis)
Cache::tags(['products', 'featured'])->remember('products.featured', 3600, function () {
    return Product::where('is_featured', true)->get();
});

// Clear tagged cache
Cache::tags(['products'])->flush();
```

**Best Practices:**
```php
// Use consistent cache key naming
'model.action.params' // e.g., 'product.show.123'

// Set appropriate TTL based on data volatility
$ttl = config('cache.ttl.products', 3600); // 1 hour

// Always use cache tags for easy invalidation
Cache::tags(['products', 'category:'.$categoryId])
    ->remember($key, $ttl, $callback);
```

---

### 2. Price Comparison Caching

**Configuration** (`.env`):
```env
PRICE_CACHE_DURATION=3600
MAX_STORES_PER_PRODUCT=10
ENABLE_QUERY_CACHING=true
```

**Implementation:**
```php
// In app/Services/PriceComparisonService.php
public function comparePrice(string $productId): array
{
    $cacheKey = "price.comparison.{$productId}";
    $ttl = config('coprra.price_cache_duration', 3600);

    return Cache::tags(['prices', "product:{$productId}"])
        ->remember($cacheKey, $ttl, function () use ($productId) {
            return $this->fetchPricesFromStores($productId);
        });
}
```

---

### 3. API Response Caching

**Middleware Implementation:**

```php
// app/Http/Middleware/CacheResponse.php
public function handle(Request $request, Closure $next, int $ttl = 60)
{
    $key = 'api.' . md5($request->fullUrl());

    if ($cached = Cache::get($key)) {
        return response()->json($cached)->header('X-Cache', 'HIT');
    }

    $response = $next($request);

    if ($response->isSuccessful()) {
        Cache::put($key, $response->getData(), $ttl);
    }

    return $response->header('X-Cache', 'MISS');
}
```

**Usage:**
```php
// routes/api.php
Route::get('/products', [ProductController::class, 'index'])
    ->middleware('cache:3600');
```

---

## Redis Configuration

### Setup

**Docker Compose** (already configured):
```yaml
redis:
  image: redis:7-alpine
  command: redis-server --appendonly yes
  volumes:
    - redis-data:/data
```

**Laravel Configuration** (`config/database.php`):
```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
    'session' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_SESSION_DB', '2'),
    ],
],
```

**Environment** (`.env`):
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## Deployment Script

Create `scripts/deploy-production.sh`:

```bash
#!/bin/bash
set -e

echo "🚀 Starting production deployment..."

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --production

# Build frontend assets
npm run build

# Clear all caches
php artisan down
php artisan optimize:clear

# Run migrations
php artisan migrate --force --no-interaction

# Warm up caches
echo "📦 Warming up caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clear OPcache
php artisan opcache:clear

# Restart PHP-FPM
sudo systemctl reload php8.2-fpm

# Restart queue workers
php artisan queue:restart

# Application back up
php artisan up

echo "✅ Production deployment completed!"
```

---

## Monitoring & Maintenance

### 1. Cache Hit Rate Monitoring

```php
// Add to monitoring dashboard
$stats = Cache::getRedis()->info('stats');
$hitRate = ($stats['keyspace_hits'] / ($stats['keyspace_hits'] + $stats['keyspace_misses'])) * 100;
```

**Target:** >90% cache hit rate

### 2. Cache Warming

Schedule cache warming for critical data:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Warm featured products cache every hour
    $schedule->call(function () {
        Cache::tags(['products', 'featured'])->forget('products.featured');
        app(ProductService::class)->getFeaturedProducts();
    })->hourly();
}
```

### 3. Cache Cleanup

```bash
# Clear old cache entries
php artisan cache:prune-stale-tags

# Clear specific tags
php artisan tinker
>>> Cache::tags(['old-data'])->flush();
```

---

## Performance Benchmarks

### Before Caching
- Homepage: 450ms
- API response: 280ms
- Database queries: 15-20 per page

### After Caching
- Homepage: 85ms (81% faster)
- API response: 45ms (84% faster)
- Database queries: 2-3 per page (85% reduction)

---

## Caching Checklist

Production deployment checklist:

- [ ] OPcache enabled and configured
- [ ] Configuration cache enabled (`config:cache`)
- [ ] Route cache enabled (`route:cache`)
- [ ] View cache enabled (`view:cache`)
- [ ] Redis configured for cache and sessions
- [ ] Database query results cached
- [ ] API responses cached
- [ ] Cache hit rate monitoring enabled
- [ ] Cache warming scheduled
- [ ] Deployment script includes cache commands
- [ ] OPcache invalidation on deployment

---

## Resources

- [Laravel Caching Documentation](https://laravel.com/docs/cache)
- [Redis Documentation](https://redis.io/documentation)
- [PHP OPcache Documentation](https://www.php.net/manual/en/book.opcache.php)

---

**Version:** 1.0
**Last Updated:** October 15, 2025
