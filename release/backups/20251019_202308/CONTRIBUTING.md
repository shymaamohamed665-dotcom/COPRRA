# Contributing to Coprra

Thank you for considering contributing to Coprra! This document outlines the guidelines and best practices for contributing to this project.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Commit Message Guidelines](#commit-message-guidelines)

## 🤝 Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

## 🚀 Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and NPM
- MySQL 8.0 or PostgreSQL 13+
- Git

### Setup Development Environment

```bash
# Clone the repository
git clone https://github.com/your-org/coprra.git
cd coprra

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate --seed

# Build assets
npm run dev

# Start development server
php artisan serve
```

## 🔄 Development Workflow

### 1. Create a Feature Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/your-bug-fix
```

### 2. Make Your Changes

- Write clean, readable code
- Follow Laravel best practices
- Add tests for new features
- Update documentation as needed

### 3. Run Quality Checks

```bash
# Format code
composer format

# Run static analysis
composer analyse

# Run tests
composer test

# Or run all quality checks at once
composer quality
```

### 4. Commit Your Changes

```bash
git add .
git commit -m "feat: add new feature"
```

### 5. Push and Create Pull Request

```bash
git push origin feature/your-feature-name
```

Then create a Pull Request on GitHub.

## 📝 Coding Standards

### PHP Code Style

We use **Laravel Pint** for code formatting. All code must pass Pint checks:

```bash
composer format        # Format code
composer format-test   # Check formatting without changes
```

### Type Safety

- Use **strict types** in all PHP files: `declare(strict_types=1);`
- Add **type hints** for all parameters and return types
- Use **PHPDoc** for complex types and arrays
- Code must pass **PHPStan Level 8**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;

class OrderService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createOrder(array $data): Order
    {
        // Implementation
    }
}
```

### Enums

Use PHP 8.1+ Enums for status fields and constants:

```php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'قيد الانتظار',
            self::PROCESSING => 'قيد المعالجة',
        };
    }
}
```

### Form Requests

Always use Form Requests for validation:

```php
class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
```

### Controllers

Keep controllers thin - delegate business logic to Services:

```php
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(
            $request->user(),
            $request->validated()
        );

        return response()->json(new OrderResource($order), 201);
    }
}
```

### Database

- Use **migrations** for all schema changes
- Add **indexes** for frequently queried columns
- Use **Eloquent relationships** and eager loading
- Avoid N+1 queries

```php
// Good - Eager loading
$orders = Order::with(['items.product', 'user'])->get();

// Bad - N+1 queries
$orders = Order::all();
foreach ($orders as $order) {
    $order->items; // N+1 query
}
```

## 🧪 Testing Guidelines

### Test Coverage

- All new features must have tests
- Aim for 80%+ code coverage
- Write both Unit and Feature tests

### Test Structure

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_order(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
        ]);
    }
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/Feature/OrderTest.php

# Run with coverage
composer test-coverage
```

## 🔀 Pull Request Process

### Before Submitting

1. ✅ All tests pass
2. ✅ Code is formatted (Pint)
3. ✅ Static analysis passes (PHPStan Level 8)
4. ✅ No merge conflicts
5. ✅ Documentation updated
6. ✅ Changelog updated (if applicable)

### PR Title Format

Use conventional commits format:

- `feat: add new feature`
- `fix: resolve bug`
- `docs: update documentation`
- `refactor: improve code structure`
- `test: add tests`
- `chore: update dependencies`

### PR Description Template

```markdown
## Description

Brief description of changes

## Type of Change

- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing

- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] Manual testing completed

## Checklist

- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] No new warnings generated
- [ ] Tests pass locally
```

## 📝 Commit Message Guidelines

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting)
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **chore**: Maintenance tasks

### Examples

```bash
feat(orders): add order status tracking

- Implement OrderStatus enum
- Add status transition validation
- Create OrderStatusChanged event

Closes #123
```

```bash
fix(auth): resolve rate limiting issue

Rate limiting was not working correctly for API routes.
Added throttle middleware to all authentication endpoints.

Fixes #456
```

## 🎯 Best Practices

### Security

- Never commit sensitive data (.env files, keys, passwords)
- Use Laravel's built-in security features
- Validate all user input
- Use parameterized queries (Eloquent does this automatically)
- Implement rate limiting on sensitive endpoints

### Performance

- Use database indexes
- Implement caching where appropriate
- Use eager loading to avoid N+1 queries
- Optimize database queries
- Use queues for long-running tasks

### Code Quality

- Write self-documenting code
- Add comments for complex logic
- Follow SOLID principles
- Keep functions small and focused
- Use meaningful variable names

## 📞 Getting Help

- **Issues**: Open an issue on GitHub
- **Discussions**: Use GitHub Discussions for questions
- **Email**: contact@coprra.com

## 📄 License

By contributing, you agree that your contributions will be licensed under the same license as the project.

---

Thank you for contributing to Coprra! 🎉
