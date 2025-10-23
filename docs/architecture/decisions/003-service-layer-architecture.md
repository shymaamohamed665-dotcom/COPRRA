# ADR-003: Adopt Service Layer Architecture

## Status

Accepted

## Date

2024-01-20

## Context

Laravel applications often fall into the trap of "fat controllers" where business logic accumulates in controller methods, leading to:
- Code duplication across controllers
- Difficulty testing business logic in isolation
- Poor separation of concerns
- Tight coupling between HTTP layer and business logic
- Challenges reusing logic across different entry points (web, API, CLI)

COPRRA has complex business logic including:
- Multi-store price comparison algorithms
- AI-powered product classification
- Point and reward calculations
- Order processing workflows
- External API integrations

This complexity demands a clean separation between HTTP concerns and business logic.

## Decision

We will implement a **Service Layer Architecture** with the following guidelines:

1. **Service Classes**: All business logic resides in dedicated service classes in `app/Services/`
2. **Thin Controllers**: Controllers only handle HTTP concerns (validation, request/response transformation)
3. **Service Registration**: Services registered as singletons in service providers
4. **Dependency Injection**: Services injected via constructor injection
5. **Single Responsibility**: Each service handles one domain area
6. **Testability**: Services can be tested independently of HTTP layer

**Service Organization**:
```
app/Services/
├── ProductService.php              # Product CRUD and search
├── PriceSearchService.php          # Multi-store price comparison
├── OrderService.php                # Order processing
├── AIService.php                   # AI integrations
├── NotificationService.php         # Notifications
├── StoreAdapters/                  # External store integrations
│   ├── AmazonAdapter.php
│   ├── EbayAdapter.php
│   └── NoonAdapter.php
└── ...
```

## Consequences

### Positive

- **Testability**: Services can be unit tested without HTTP layer
- **Reusability**: Same business logic accessible from web, API, CLI, queues
- **Maintainability**: Business logic centralized, not scattered across controllers
- **Separation of Concerns**: Clear boundary between HTTP and business logic
- **Dependency Management**: Explicit dependencies via constructor injection
- **Team Scalability**: Different developers can work on different services without conflicts

### Negative

- **Increased File Count**: More files to maintain (one service per domain)
- **Indirection**: Extra layer between controller and model
- **Learning Curve**: Team must understand service layer pattern
- **Potential Over-engineering**: Simple CRUD operations may not need a service

### Neutral

- **Service Granularity**: Requires thoughtful decisions on service boundaries
- **Testing Strategy**: Must write both controller tests and service tests

## Alternatives Considered

### Alternative 1: Fat Controllers (Laravel Default)

**Pros**: Simpler, fewer files, faster for simple CRUD
**Cons**: Code duplication, difficult testing, tight coupling, poor scalability

**Reason not chosen**: COPRRA's complexity demands better structure. Fat controllers would become unmaintainable as features grow.

### Alternative 2: Repository Pattern Only

**Pros**: Clean data access layer, testable
**Cons**: Doesn't solve business logic organization, can be overly complex

**Reason not chosen**: Repository pattern solves data access but not business logic organization. We implement repositories where needed but don't make it the primary pattern.

### Alternative 3: Action Classes (Single-Action Controllers)

**Pros**: Single responsibility, easy to find logic, testable
**Cons**: Explosion of files, difficult to share logic, over-granular

**Reason not chosen**: Too granular for COPRRA's use cases. Service layer provides better balance between organization and pragmatism.

### Alternative 4: Domain-Driven Design (DDD) with Bounded Contexts

**Pros**: Extremely clean separation, scalable to enterprise level
**Cons**: High complexity, steep learning curve, significant overhead

**Reason not chosen**: DDD is overkill for COPRRA's current scale. Service layer provides 80% of the benefits with 20% of the complexity.

## Implementation Guidelines

### Good Service Example

```php
final readonly class ProductService
{
    public function __construct(
        private ProductRepository $repository,
        private CacheService $cache,
        private AIService $ai
    ) {}

    public function findProduct(int $id): ?Product
    {
        return $this->cache->remember("product.{$id}", 3600, fn() =>
            $this->repository->findById($id)
        );
    }

    public function classifyProduct(Product $product): void
    {
        $classification = $this->ai->classifyProduct($product);
        $product->update(['category_id' => $classification->categoryId]);
    }
}
```

### Bad Controller Example (Don't Do This)

```php
// ❌ BAD: Business logic in controller
public function show(int $id)
{
    $product = Product::find($id);

    // Business logic that belongs in a service
    if (!$product->is_classified) {
        $classification = OpenAI::classify($product);
        $product->category_id = $classification->categoryId;
        $product->save();
    }

    return view('products.show', compact('product'));
}
```

### Good Controller Example

```php
// ✅ GOOD: Delegate to service
public function show(int $id, ProductService $productService)
{
    $product = $productService->findProduct($id);

    if (!$product) {
        abort(404);
    }

    return view('products.show', compact('product'));
}
```

## References

- [Service Layer Pattern - Martin Fowler](https://martinfowler.com/eaaCatalog/serviceLayer.html)
- [Laravel Best Practices - Services](https://github.com/alexeymezenin/laravel-best-practices#business-logic-should-be-in-service-class)
- Project app/Services/ directory structure
- Project app/Providers/AppServiceProvider.php (service bindings)
