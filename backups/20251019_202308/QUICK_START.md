# 🚀 Quick Start Guide - Coprra

Get Coprra up and running in 5 minutes!

---

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL 8.0 or higher
- Redis (optional, recommended)

---

## ⚡ Installation

### 1. Clone Repository

```bash
git clone https://github.com/your-org/coprra.git
cd coprra
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Database

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coprra
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Run Migrations

```bash
# Create database tables
php artisan migrate

# Seed database with sample data (optional)
php artisan db:seed
```

### 6. Build Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 7. Start Server

```bash
# Start Laravel development server
php artisan serve

# In another terminal, start Vite dev server
npm run dev
```

Visit: `http://localhost:8000`

---

## 🧪 Run Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test suite
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Feature
```

---

## 🔍 Code Quality Checks

```bash
# Run all quality checks
composer quality

# Individual checks
composer format          # Format code
composer format-test     # Check formatting
composer analyse         # Run PHPStan
```

---

## 🔐 Create Admin User

```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Enums\UserRole;

User::create([
    'name' => 'Admin User',
    'email' => 'admin@coprra.com',
    'password' => Hash::make('SecurePassword123!'),
    'role' => UserRole::ADMIN,
    'email_verified_at' => now(),
]);
```

---

## 📊 Optional: Setup Telescope

```bash
# Install Telescope
composer require laravel/telescope --dev

# Publish assets
php artisan telescope:install

# Run migrations
php artisan migrate
```

Visit: `http://localhost:8000/telescope`

---

## 🚀 Production Deployment

### 1. Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 2. Build Assets

```bash
npm run build
```

### 3. Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Setup Queue Workers

```bash
# Start queue worker
php artisan queue:work redis --daemon

# Or use Supervisor (recommended)
# See DEPLOYMENT.md for details
```

### 5. Setup Cron Job

Add to crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔧 Common Commands

### Development

```bash
# Clear all caches
php artisan optimize:clear

# Or individually
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migrations with seeding
php artisan migrate:fresh --seed
```

### Testing

```bash
# Run tests
composer test

# Run specific test
vendor/bin/phpunit tests/Unit/Enums/OrderStatusTest.php

# Run with filter
vendor/bin/phpunit --filter test_can_transition_to_allowed_status
```

### Code Quality

```bash
# Format code
composer format

# Check code quality
composer analyse

# Run all quality checks
composer quality
```

---

## 📚 Documentation

- **[README.md](README.md)** - Project overview
- **[CONTRIBUTING.md](CONTRIBUTING.md)** - Contribution guidelines
- **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** - API documentation
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Deployment guide
- **[SECURITY.md](SECURITY.md)** - Security policy
- **[TELESCOPE_SETUP.md](TELESCOPE_SETUP.md)** - Telescope setup
- **[PERFORMANCE_OPTIMIZATION.md](PERFORMANCE_OPTIMIZATION.md)** - Performance guide

---

## 🐛 Troubleshooting

### Database Connection Error

```bash
# Check database credentials in .env
# Ensure MySQL is running
sudo systemctl status mysql

# Test connection
php artisan tinker
DB::connection()->getPdo();
```

### Permission Errors

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Asset Build Errors

```bash
# Clear node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Cache Issues

```bash
# Clear all caches
php artisan optimize:clear
composer dump-autoload
```

---

## 💡 Tips

1. **Use Redis** for better performance:
   ```env
   CACHE_DRIVER=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   ```

2. **Enable OPcache** in production for better PHP performance

3. **Use Queue Workers** for background jobs:
   ```bash
   php artisan queue:work redis --daemon
   ```

4. **Monitor with Telescope** in development:
   ```bash
   composer require laravel/telescope --dev
   ```

5. **Run tests before deploying**:
   ```bash
   composer quality
   ```

---

## 🆘 Need Help?

- Check [Documentation](README.md)
- Review [API Documentation](API_DOCUMENTATION.md)
- See [Deployment Guide](DEPLOYMENT.md)
- Read [Contributing Guidelines](CONTRIBUTING.md)

---

## 🎉 You're Ready!

Your Coprra application is now running!

**Next Steps:**
1. Create admin user
2. Configure email settings
3. Setup payment gateway
4. Customize branding
5. Deploy to production

---

**Happy Coding!** 🚀

