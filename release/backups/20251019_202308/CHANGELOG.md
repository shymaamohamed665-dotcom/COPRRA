# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-10-01

### 🎉 Major Release - Complete Refactoring & Security Overhaul

This release represents a complete overhaul of the codebase with focus on security, type safety, and code quality.

### Added

#### Security

- **Rate Limiting** on all authentication endpoints (5 attempts/min for login, 3 for register)
- **Security Headers Middleware** with 10+ security headers (CSP, HSTS, X-Frame-Options, etc.)
- **SQL Injection Protection** - Fixed vulnerable `whereRaw()` usage
- **Strong Password Validation** - Mixed case, numbers, symbols required

#### Type Safety & Enums

- **OrderStatus Enum** - Type-safe order status with state machine (6 states)
- **UserRole Enum** - RBAC system with 4 roles and permission management
- **NotificationStatus Enum** - Type-safe notification statuses (4 states)
- **PHPStan Level 8** - Strictest static analysis enabled
- **Strict Types** - `declare(strict_types=1)` in all PHP files

#### Authentication & Authorization

- **AuthController** - Centralized authentication logic (8 methods)
- **EmailVerificationController** - Email verification flow
- **CheckUserRole Middleware** - Role-based access control
- **CheckPermission Middleware** - Permission-based access control
- **5 Form Requests** - RegisterRequest, ForgotPasswordRequest, ResetPasswordRequest, etc.

#### Events & Listeners

- **OrderStatusChanged Event** - Fired when order status changes
- **SendOrderStatusNotification Listener** - Automatic notifications on status change

#### API Resources

- **OrderResource** - Clean order API responses with status labels/colors
- **OrderItemResource** - Order item transformations
- **UserResource** - User data with role information
- **ProductResource** - Product data transformations

#### Helpers & Utilities

- **OrderHelper** - 10+ utility methods for orders (status badges, calculations, formatting)
- **ValidOrderStatus Rule** - Custom validation for order statuses
- **ValidOrderStatusTransition Rule** - Validates status transitions

#### Testing

- **OrderStatusTest** - 15 comprehensive enum tests
- **UserRoleTest** - 14 role and permission tests
- **AuthControllerTest** - 12 authentication tests
- **CartControllerTest** - 12 cart operation tests
- **OrderServiceTest** - Service layer tests

#### Documentation

- **Enhanced README.md** - Professional documentation with badges
- **CONTRIBUTING.md** - Comprehensive contribution guidelines
- **COMPLETION_REPORT.md** - Detailed implementation report
- **CHANGELOG.md** - This file

#### CI/CD

- **Composer Scripts** - `format`, `analyse`, `test`, `quality` commands
- **GitHub Actions** - Automated testing and quality checks

### Changed

#### Models

- **Order Model** - Cast `status` to OrderStatus enum
- **User Model** - Cast `role` to UserRole enum
- **Notification Model** - Cast `status` to NotificationStatus enum
- **All Models** - Removed `@phpstan-ignore` comments, added proper type hints

#### Services

- **OrderService** - Updated to use OrderStatus enum and fire events
- **OrderService::updateOrderStatus()** - Now accepts enum or string, fires OrderStatusChanged event

#### Controllers

- **CartController** - Uses UpdateCartRequest for validation
- **Api\ProductController** - Uses ProductIndexRequest for validation
- **UserController** - Fixed SQL injection vulnerability

#### Routes

- **web.php** - Moved authentication logic to controllers, added rate limiting
- **api.php** - Added rate limiting to authentication endpoints

#### Configuration

- **bootstrap/app.php** - Registered new middleware aliases (role, permission)
- **phpstan.neon** - Raised level from 5 to 8
- **composer.json** - Added quality check scripts

### Fixed

- **SQL Injection** in UserController (line 42) - Replaced `whereRaw()` with safe `where()`
- **Weak Password Hashing** - Replaced `bcrypt()` with `Hash::make()`
- **Missing Rate Limiting** - Added to all authentication endpoints
- **Inactive Security Headers** - Activated SecurityHeadersMiddleware globally
- **PHPStan Errors** - Fixed all type-related issues, removed ignore comments

### Security

- 🔒 **6 Critical Security Issues Fixed**
    - SQL Injection vulnerability
    - Missing rate limiting
    - Weak password hashing
    - Inactive security headers
    - Authentication in route closures
    - Missing CSRF protection on some routes

### Performance

- ⚡ **Database Optimization**
    - Verified 20+ indexes on critical tables
    - Confirmed eager loading usage throughout
    - No N+1 queries detected

### Deprecated

- ❌ **String-based Status Fields** - Use enums instead
- ❌ **bcrypt() Function** - Use `Hash::make()` instead
- ❌ **Route Closures for Auth** - Use dedicated controllers

### Removed

- ❌ All `@phpstan-ignore` comments from Models
- ❌ Unsafe `whereRaw()` usage with user input
- ❌ Authentication logic from route files

---

## [1.0.0] - 2025-09-15

### Initial Release

- Basic Laravel 12 setup
- E-commerce functionality
- User authentication
- Product management
- Order system
- Shopping cart
- Livewire components
- Basic testing

---

## Version History

- **2.0.0** (2025-10-01) - Major security and quality overhaul
- **1.0.0** (2025-09-15) - Initial release

---

## Upgrade Guide

### Upgrading from 1.x to 2.0

#### Breaking Changes

1. **Order Status Field**

    ```php
    // Before (1.x)
    $order->status = 'pending';

    // After (2.0)
    use App\Enums\OrderStatus;
    $order->status = OrderStatus::PENDING;
    ```

2. **User Role Field**

    ```php
    // Before (1.x)
    if ($user->role === 'admin') { }

    // After (2.0)
    use App\Enums\UserRole;
    if ($user->role === UserRole::ADMIN) { }
    // or
    if ($user->role->isAdmin()) { }
    ```

3. **Password Hashing**

    ```php
    // Before (1.x)
    $password = bcrypt($request->password);

    // After (2.0)
    use Illuminate\Support\Facades\Hash;
    $password = Hash::make($request->password);
    ```

4. **Validation**

    ```php
    // Before (1.x) - In controller
    $request->validate([...]);

    // After (2.0) - Use Form Request
    public function store(StoreOrderRequest $request) { }
    ```

#### Migration Steps

1. **Update Dependencies**

    ```bash
    composer update
    ```

2. **Run Migrations**

    ```bash
    php artisan migrate
    ```

3. **Update Enum Values**
    - Update any code that uses string statuses to use enums
    - Update database seeders to use enum values

4. **Clear Caches**

    ```bash
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    ```

5. **Run Tests**
    ```bash
    composer test
    ```

---

## Support

For questions or issues, please:

- Open an issue on GitHub
- Check the documentation in README.md
- Review CONTRIBUTING.md for development guidelines

---

**Maintained by:** Coprra Development Team  
**License:** MIT
