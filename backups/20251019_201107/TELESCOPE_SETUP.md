# 🔭 Laravel Telescope Setup Guide

Laravel Telescope is an elegant debug assistant for Laravel applications. It provides insight into requests, exceptions, database queries, queued jobs, mail, notifications, cache operations, scheduled tasks, and more.

## 📦 Installation

### Step 1: Install Telescope

```bash
composer require laravel/telescope --dev
```

### Step 2: Publish Assets

```bash
php artisan telescope:install
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

### Step 4: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=telescope-config
```

## ⚙️ Configuration

### Environment Configuration

Add to `.env`:

```env
TELESCOPE_ENABLED=true
TELESCOPE_DRIVER=database

# Telescope Authentication
TELESCOPE_ADMIN_EMAIL=admin@coprra.com
```

### config/telescope.php

```php
<?php

return [
    'enabled' => env('TELESCOPE_ENABLED', true),

    'driver' => env('TELESCOPE_DRIVER', 'database'),

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'chunk' => 1000,
        ],
    ],

    'path' => env('TELESCOPE_PATH', 'telescope'),

    'middleware' => [
        'web',
        Authorize::class,
    ],

    'ignore_paths' => [
        'nova-api*',
        'pulse*',
    ],

    'ignore_commands' => [
        'schedule:run',
        'schedule:finish',
    ],

    'watchers' => [
        Watchers\CacheWatcher::class => env('TELESCOPE_CACHE_WATCHER', true),
        Watchers\CommandWatcher::class => env('TELESCOPE_COMMAND_WATCHER', true),
        Watchers\DumpWatcher::class => env('TELESCOPE_DUMP_WATCHER', true),
        Watchers\EventWatcher::class => env('TELESCOPE_EVENT_WATCHER', true),
        Watchers\ExceptionWatcher::class => env('TELESCOPE_EXCEPTION_WATCHER', true),
        Watchers\GateWatcher::class => env('TELESCOPE_GATE_WATCHER', true),
        Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),
        Watchers\LogWatcher::class => env('TELESCOPE_LOG_WATCHER', true),
        Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),
        Watchers\ModelWatcher::class => [
            'enabled' => env('TELESCOPE_MODEL_WATCHER', true),
            'events' => ['created', 'updated', 'deleted'],
        ],
        Watchers\NotificationWatcher::class => env('TELESCOPE_NOTIFICATION_WATCHER', true),
        Watchers\QueryWatcher::class => [
            'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
            'slow' => 100, // Log queries slower than 100ms
        ],
        Watchers\RedisWatcher::class => env('TELESCOPE_REDIS_WATCHER', true),
        Watchers\RequestWatcher::class => [
            'enabled' => env('TELESCOPE_REQUEST_WATCHER', true),
            'size_limit' => env('TELESCOPE_RESPONSE_SIZE_LIMIT', 64),
        ],
        Watchers\ScheduleWatcher::class => env('TELESCOPE_SCHEDULE_WATCHER', true),
        Watchers\ViewWatcher::class => env('TELESCOPE_VIEW_WATCHER', true),
    ],
];
```

## 🔐 Authorization

### Create Telescope Authorization Gate

Create `app/Providers/TelescopeServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        // Only enable in local and staging environments
        Telescope::filter(function (IncomingEntry $entry) {
            if ($this->app->environment('local')) {
                return true;
            }

            return $entry->isReportableException() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return $user->role === UserRole::ADMIN;
        });
    }
}
```

### Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\TelescopeServiceProvider::class,
],
```

## 🚀 Usage

### Access Telescope Dashboard

Visit: `http://your-app.test/telescope`

### Available Watchers

1. **Requests** - HTTP requests and responses
2. **Commands** - Artisan commands
3. **Schedule** - Scheduled tasks
4. **Jobs** - Queued jobs
5. **Exceptions** - Application exceptions
6. **Logs** - Application logs
7. **Dumps** - Dump statements
8. **Queries** - Database queries
9. **Models** - Eloquent model events
10. **Events** - Application events
11. **Mail** - Sent emails
12. **Notifications** - Sent notifications
13. **Gates** - Authorization checks
14. **Cache** - Cache operations
15. **Redis** - Redis commands

### Monitoring Slow Queries

Telescope automatically logs queries slower than 100ms (configurable).

View slow queries:
1. Go to Telescope dashboard
2. Click "Queries"
3. Sort by duration

### Monitoring Exceptions

All exceptions are automatically logged.

View exceptions:
1. Go to Telescope dashboard
2. Click "Exceptions"
3. View stack traces and context

### Monitoring Jobs

Track queued job execution.

View jobs:
1. Go to Telescope dashboard
2. Click "Jobs"
3. See success/failure rates

## 📊 Performance Monitoring

### Custom Tags

Tag specific operations for monitoring:

```php
use Laravel\Telescope\Telescope;

Telescope::tag(function () {
    return ['user:' . auth()->id()];
});
```

### Monitor Specific Operations

```php
Telescope::recordQuery($query);
Telescope::recordEvent($event);
Telescope::recordException($exception);
```

## 🧹 Maintenance

### Prune Old Entries

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('telescope:prune --hours=48')->daily();
}
```

### Manual Pruning

```bash
# Prune entries older than 48 hours
php artisan telescope:prune --hours=48

# Prune all entries
php artisan telescope:clear
```

## 🔒 Production Considerations

### Disable in Production

In `.env`:

```env
TELESCOPE_ENABLED=false
```

### Or Limit Watchers

Disable expensive watchers in production:

```env
TELESCOPE_QUERY_WATCHER=false
TELESCOPE_MODEL_WATCHER=false
TELESCOPE_VIEW_WATCHER=false
```

### Use Sampling

Only record a percentage of requests:

```php
Telescope::filter(function (IncomingEntry $entry) {
    if ($entry->type === 'request') {
        return rand(1, 100) <= 10; // 10% sampling
    }

    return true;
});
```

## 📈 Best Practices

1. **Only enable in development/staging** - Telescope adds overhead
2. **Prune regularly** - Old entries consume database space
3. **Restrict access** - Only admins should access Telescope
4. **Monitor slow queries** - Optimize queries over 100ms
5. **Track exceptions** - Fix recurring exceptions
6. **Use tags** - Tag important operations for easy filtering
7. **Disable unused watchers** - Reduce overhead

## 🐛 Troubleshooting

### Telescope not showing data

1. Check `.env`: `TELESCOPE_ENABLED=true`
2. Run migrations: `php artisan migrate`
3. Clear cache: `php artisan config:clear`

### Access denied

1. Check authorization gate in `TelescopeServiceProvider`
2. Ensure user has admin role

### Performance issues

1. Disable expensive watchers
2. Increase prune frequency
3. Use sampling in production

## 📚 Resources

- [Official Documentation](https://laravel.com/docs/telescope)
- [GitHub Repository](https://github.com/laravel/telescope)

---

**Last Updated:** 2025-10-01  
**Version:** 5.0

