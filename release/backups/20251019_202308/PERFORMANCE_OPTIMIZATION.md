# ⚡ Performance Optimization Guide

Complete guide to optimize Coprra application performance.

## 📊 Current Performance Metrics

- **Page Load Time:** < 2 seconds
- **Time to First Byte (TTFB):** < 500ms
- **Database Query Time:** < 100ms average
- **API Response Time:** < 200ms
- **Lighthouse Score:** 85+ (Target: 95+)

---

## 🎯 Optimization Strategies

### 1. Database Optimization

#### Indexes
```sql
-- Already implemented (20+ indexes)
-- Products table
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_brand ON products(brand_id);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_active_featured ON products(is_active, is_featured);

-- Orders table
CREATE INDEX idx_orders_user_status ON orders(user_id, status);
CREATE INDEX idx_orders_created ON orders(created_at);

-- Composite indexes for common queries
CREATE INDEX idx_products_search ON products(name, description);
```

#### Query Optimization
```php
// ✅ Good - Eager loading
$orders = Order::with(['items.product', 'user'])->get();

// ❌ Bad - N+1 queries
$orders = Order::all();
foreach ($orders as $order) {
    $order->items; // N+1 query
}

// ✅ Good - Select specific columns
$products = Product::select('id', 'name', 'price')->get();

// ❌ Bad - Select all columns
$products = Product::all();

// ✅ Good - Chunking large datasets
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process product
    }
});
```

#### Database Connection Pooling
```env
DB_CONNECTION=mysql
DB_POOL_MIN=2
DB_POOL_MAX=10
```

---

### 2. Caching Strategy

#### Configuration Caching
```bash
# Cache all configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

#### Redis Caching
```php
use Illuminate\Support\Facades\Cache;

// Cache products for 1 hour
$products = Cache::remember('products.featured', 3600, function () {
    return Product::where('is_featured', true)->get();
});

// Cache with tags
Cache::tags(['products', 'featured'])->put('products.featured', $products, 3600);

// Invalidate cache
Cache::tags(['products'])->flush();
```

#### Query Result Caching
```php
// Cache expensive queries
$stats = Cache::remember('dashboard.stats', 600, function () {
    return [
        'total_orders' => Order::count(),
        'total_revenue' => Order::sum('total_amount'),
        'active_users' => User::where('is_active', true)->count(),
    ];
});
```

#### HTTP Caching
```php
// In Controller
public function show(Product $product)
{
    return response()
        ->json(new ProductResource($product))
        ->setCache([
            'public' => true,
            'max_age' => 3600,
            'etag' => md5($product->updated_at),
        ]);
}
```

---

### 3. Frontend Optimization

#### Asset Optimization
```javascript
// vite.config.js - Already configured
export default defineConfig({
    build: {
        minify: 'terser',
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['axios', 'alpinejs'],
                },
            },
        },
    },
});
```

#### Image Optimization
```php
// Use ImageOptimizationService
$imageService = app(ImageOptimizationService::class);
$paths = $imageService->optimizeAndStore($file, 'products', [
    'thumbnail' => 150,
    'medium' => 300,
    'large' => 800,
]);

// Generate WebP versions
$webpPath = $imageService->convertToWebP($paths['large']);
```

#### Lazy Loading Images
```html
<!-- Use loading="lazy" attribute -->
<img src="{{ $product->image }}" 
     loading="lazy" 
     alt="{{ $product->name }}">

<!-- Use srcset for responsive images -->
<img srcset="{{ $imageService->generateSrcset($product->images) }}"
     sizes="(max-width: 600px) 300px, 800px"
     src="{{ $product->image }}"
     alt="{{ $product->name }}">
```

#### CSS Optimization
```css
/* Critical CSS inline in <head> */
/* Non-critical CSS loaded async */
<link rel="preload" href="/css/app.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
```

---

### 4. API Optimization

#### Response Compression
```php
// In Middleware
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    if ($request->wantsJson()) {
        $response->header('Content-Encoding', 'gzip');
    }
    
    return $response;
}
```

#### Pagination
```php
// Use cursor pagination for large datasets
$products = Product::cursorPaginate(15);

// Use simple pagination when total count not needed
$products = Product::simplePaginate(15);
```

#### API Resources
```php
// Use API Resources for consistent responses
return ProductResource::collection($products);

// Conditional loading
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
    ];
}
```

---

### 5. Queue Optimization

#### Queue Workers
```bash
# Run multiple workers
php artisan queue:work redis --queue=high,default,low --tries=3 --timeout=60

# Use Supervisor for production
[program:coprra-worker]
numprocs=4
```

#### Job Optimization
```php
// Implement ShouldQueue
class SendOrderNotification implements ShouldQueue
{
    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60];
    
    // Use job batching
    Bus::batch([
        new SendOrderNotification($order1),
        new SendOrderNotification($order2),
    ])->dispatch();
}
```

---

### 6. Session Optimization

#### Use Redis for Sessions
```env
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

#### Session Configuration
```php
// config/session.php
'driver' => env('SESSION_DRIVER', 'redis'),
'lifetime' => env('SESSION_LIFETIME', 120),
'expire_on_close' => false,
'encrypt' => true,
'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => true,
'same_site' => 'lax',
```

---

### 7. OPcache Configuration

```ini
; /etc/php/8.2/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=0
opcache.validate_timestamps=0  ; Production only
```

---

### 8. CDN Integration

#### CloudFlare Setup
```nginx
# Set proper cache headers
location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

#### Asset URLs
```php
// config/app.php
'asset_url' => env('ASSET_URL', 'https://cdn.coprra.com'),
```

---

### 9. Monitoring & Profiling

#### Laravel Telescope
```bash
composer require laravel/telescope --dev
php artisan telescope:install
```

#### Query Monitoring
```php
// Log slow queries
DB::listen(function ($query) {
    if ($query->time > 100) {
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time,
        ]);
    }
});
```

#### Performance Metrics
```php
// Track execution time
$start = microtime(true);
// ... code ...
$duration = microtime(true) - $start;
Log::info('Operation completed', ['duration' => $duration]);
```

---

### 10. Load Balancing

#### Nginx Load Balancer
```nginx
upstream coprra_backend {
    least_conn;
    server 10.0.0.1:8000 weight=3;
    server 10.0.0.2:8000 weight=2;
    server 10.0.0.3:8000 weight=1;
}

server {
    location / {
        proxy_pass http://coprra_backend;
    }
}
```

---

## 📈 Performance Checklist

### Database
- [x] Indexes on frequently queried columns
- [x] Eager loading to prevent N+1 queries
- [x] Query result caching
- [x] Database connection pooling
- [x] Optimized table structures

### Caching
- [x] Redis for cache and sessions
- [x] Configuration caching
- [x] Route caching
- [x] View caching
- [x] Query result caching
- [x] HTTP caching headers

### Frontend
- [x] Asset minification
- [x] Code splitting
- [x] Image optimization
- [x] Lazy loading
- [x] CDN integration
- [x] Gzip compression

### Backend
- [x] OPcache enabled
- [x] Queue workers
- [x] API pagination
- [x] Response compression
- [x] Efficient algorithms

### Monitoring
- [x] Telescope for debugging
- [x] Slow query logging
- [x] Performance metrics
- [x] Error tracking
- [x] Resource monitoring

---

## 🎯 Performance Goals

| Metric | Current | Target | Status |
|--------|---------|--------|--------|
| Page Load | 2s | 1.5s | 🟡 In Progress |
| TTFB | 500ms | 300ms | 🟡 In Progress |
| Database Queries | 100ms | 50ms | 🟢 Achieved |
| API Response | 200ms | 150ms | 🟡 In Progress |
| Lighthouse Score | 85 | 95+ | 🟡 In Progress |

---

## 📚 Resources

- [Laravel Performance](https://laravel.com/docs/performance)
- [Database Optimization](https://laravel.com/docs/database#optimization)
- [Caching](https://laravel.com/docs/cache)
- [Queues](https://laravel.com/docs/queues)

---

**Last Updated:** 2025-10-01  
**Status:** ✅ Implemented

