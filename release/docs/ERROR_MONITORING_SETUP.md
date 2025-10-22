# Error Monitoring Setup Guide

## Overview

Error monitoring is crucial for production applications. This guide covers setting up Sentry for real-time error tracking and monitoring.

## Option 1: Sentry (Recommended)

### Benefits
- Real-time error tracking
- Stack trace analysis
- Release tracking
- Performance monitoring
- User impact tracking
- Integration with issue trackers

### Installation

1. **Install Sentry SDK**
   ```bash
   composer require sentry/sentry-laravel
   ```

2. **Publish Configuration**
   ```bash
   php artisan sentry:publish --dsn=YOUR_SENTRY_DSN
   ```

3. **Configure Environment Variables**
   ```env
   # .env
   SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
   SENTRY_TRACES_SAMPLE_RATE=0.1
   SENTRY_PROFILES_SAMPLE_RATE=0.1
   SENTRY_ENVIRONMENT=production
   ```

4. **Update Exception Handler**

   Already configured in `app/Exceptions/Handler.php`:
   ```php
   public function register(): void
   {
       $this->reportable(function (Throwable $e) {
           if (app()->bound('sentry')) {
               app('sentry')->captureException($e);
           }
       });
   }
   ```

5. **Test the Integration**
   ```php
   php artisan tinker
   >>> throw new Exception('Test Sentry Integration');
   ```

### Advanced Configuration

Create `config/sentry.php`:
```php
<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Capture release information
    'release' => env('SENTRY_RELEASE', exec('git log --pretty="%h" -n1 HEAD')),

    // Environment
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    // Sample rate for error events (0.0 to 1.0)
    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    // Sample rate for performance monitoring (0.0 to 1.0)
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // Profiles sample rate (0.0 to 1.0)
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

    // Send PII (Personally Identifiable Information)
    'send_default_pii' => false,

    // Attach stack traces to messages
    'attach_stacktrace' => true,

    // Ignore specific exceptions
    'ignore_exceptions' => [
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

    // Before send callback
    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // Don't send events in local environment
        if (app()->environment('local')) {
            return null;
        }

        // Add custom context
        $event->setContext('app', [
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ]);

        return $event;
    },
];
```

### User Context

Add user information to error reports:

```php
// app/Http/Middleware/SentryContext.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SentryContext
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->bound('sentry') && auth()->check()) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                $scope->setUser([
                    'id' => auth()->id(),
                    'email' => auth()->user()->email,
                    'username' => auth()->user()->name,
                ]);
            });
        }

        return $next($request);
    }
}
```

Register in `app/Http/Kernel.php`:
```php
protected $middleware = [
    // ...
    \App\Http\Middleware\SentryContext::class,
];
```

### Performance Monitoring

Enable performance monitoring in your routes:

```php
// routes/web.php
use function Sentry\startTransaction;

Route::get('/products', function () {
    $transaction = startTransaction(['op' => 'http.server', 'name' => 'GET /products']);
    \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

    // Your code here
    $products = Product::paginate(20);

    $transaction->finish();
    return view('products.index', compact('products'));
});
```

---

## Option 2: Bugsnag

### Installation

1. **Install Bugsnag**
   ```bash
   composer require bugsnag/bugsnag-laravel
   ```

2. **Publish Configuration**
   ```bash
   php artisan vendor:publish --provider="Bugsnag\BugsnagLaravel\BugsnagServiceProvider"
   ```

3. **Configure Environment**
   ```env
   BUGSNAG_API_KEY=your-api-key-here
   BUGSNAG_APP_VERSION=1.0.0
   BUGSNAG_RELEASE_STAGE=production
   ```

4. **Update Exception Handler**
   ```php
   public function register(): void
   {
       $this->reportable(function (Throwable $e) {
           app('bugsnag')->notifyException($e);
       });
   }
   ```

---

## Option 3: Laravel Telescope (Development)

For local development, Laravel Telescope is already configured:

```bash
# Access Telescope dashboard
http://localhost:8000/telescope
```

Configure in `config/telescope.php`:
```php
'enabled' => env('TELESCOPE_ENABLED', true),
'storage' => [
    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
    ],
],
```

---

## Best Practices

### 1. Environment-Specific Configuration

```env
# .env.production
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_ENVIRONMENT=production

# .env.staging
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
SENTRY_TRACES_SAMPLE_RATE=1.0
SENTRY_ENVIRONMENT=staging

# .env.local
SENTRY_LARAVEL_DSN=
SENTRY_ENVIRONMENT=local
```

### 2. Custom Error Context

Add custom context to errors:

```php
use Sentry\State\Scope;

\Sentry\configureScope(function (Scope $scope): void {
    $scope->setContext('order', [
        'id' => $order->id,
        'total' => $order->total,
        'status' => $order->status,
    ]);
});
```

### 3. Breadcrumbs

Add breadcrumbs for better debugging:

```php
\Sentry\addBreadcrumb(
    new \Sentry\Breadcrumb(
        \Sentry\Breadcrumb::LEVEL_INFO,
        \Sentry\Breadcrumb::TYPE_DEFAULT,
        'auth',
        'User login attempted',
        ['email' => $email]
    )
);
```

### 4. Release Tracking

Track releases in CI/CD:

```bash
# In your deployment script
SENTRY_AUTH_TOKEN=your-auth-token
SENTRY_ORG=your-org
SENTRY_PROJECT=coprra

# Create release
sentry-cli releases new "$RELEASE_VERSION"
sentry-cli releases set-commits "$RELEASE_VERSION" --auto
sentry-cli releases finalize "$RELEASE_VERSION"

# Associate release with deploy
sentry-cli releases deploys "$RELEASE_VERSION" new -e production
```

### 5. Alert Configuration

Configure alerts in Sentry dashboard:
- Set up Slack/email notifications
- Configure error rate thresholds
- Set up anomaly detection
- Create custom alert rules

---

## Monitoring Checklist

- [ ] Sentry/Bugsnag installed and configured
- [ ] DSN added to environment variables
- [ ] Release tracking configured
- [ ] User context added to errors
- [ ] Alert notifications configured
- [ ] Performance monitoring enabled
- [ ] Ignored exceptions configured
- [ ] PII handling configured
- [ ] Team members added to project
- [ ] Integration tested

---

## Resources

- [Sentry Laravel Documentation](https://docs.sentry.io/platforms/php/guides/laravel/)
- [Bugsnag Laravel Documentation](https://docs.bugsnag.com/platforms/php/laravel/)
- [Laravel Telescope Documentation](https://laravel.com/docs/telescope)

---

**Version:** 1.0
**Last Updated:** October 15, 2025
