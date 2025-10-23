# 1. Use of Service Classes for Business Logic

**Date:** 2025-10-23

## Status

Accepted

## Context

As COPRRA grew from a basic price comparison platform to an enterprise-grade e-commerce system with multiple integrations (Amazon, eBay, Noon), AI-powered features, and complex business workflows, we needed a scalable architecture to manage increasing complexity.

### Key Challenges:
1. **Controller Bloat**: Controllers were becoming too large with business logic mixed with HTTP concerns
2. **Code Reusability**: Similar logic was being duplicated across web, API, and console command contexts
3. **Testing Complexity**: Testing business logic required bootstrapping the entire HTTP layer
4. **Dependency Management**: Growing number of dependencies made controller constructors unwieldy
5. **Separation of Concerns**: No clear boundary between HTTP handling and business operations

### Business Requirements:
- Multi-store price comparison requiring adapter patterns
- AI integration for product classification and recommendations
- Complex order processing with event-driven notifications
- Multi-currency and multi-language support
- Background job processing for heavy operations
- Comprehensive caching strategies

### Technical Constraints:
- Laravel 12 framework
- PHP 8.2+ strict typing requirements
- PHPStan Level max static analysis
- Need for dependency injection and testability
- Support for both web and API interfaces

## Decision

**We will implement a Service Layer Architecture** where all business logic is encapsulated in dedicated service classes located in `app/Services/`.

### Key Principles:

1. **Service Class Organization**:
   - Services organized by domain: `AI/`, `Backup/`, `Product/`, `Performance/`, etc.
   - Each service has a single, well-defined responsibility
   - Services located in `app/Services/` with subdirectories for related services

2. **Controller Responsibility**:
   - Controllers are thin and only handle HTTP concerns (request/response)
   - Controllers delegate all business logic to services
   - Controllers validate input via Form Requests and return responses

3. **Service Registration**:
   - Services registered as singletons in dedicated service providers
   - Dependency injection used throughout (no `new` keyword for services)
   - Contract-based design with interfaces in `app/Contracts/`

4. **Service Composition**:
   - Services can depend on other services
   - Complex workflows composed from multiple smaller services
   - Repository pattern used for data access abstraction

### Example Implementation:

**Controller (Thin)**:
```php
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->searchProducts(
            $request->validated()
        );

        return response()->json($products);
    }
}
```

**Service (Business Logic)**:
```php
class ProductService
{
    public function __construct(
        private ProductRepository $repository,
        private CacheService $cache,
        private AIService $aiService
    ) {}

    public function searchProducts(array $filters): Collection
    {
        return $this->cache->remember("products.{$hash}", function() {
            return $this->repository
                ->search($filters)
                ->with(['category', 'brand'])
                ->get();
        });
    }
}
```

### Alternatives Considered and Rejected:

1. **Fat Controllers (Active Record Pattern)**:
   - ❌ Rejected: Violates Single Responsibility Principle
   - ❌ Testing requires full HTTP stack
   - ❌ Logic cannot be reused outside HTTP context

2. **Domain-Driven Design (DDD) with Aggregates**:
   - ⚠️ Considered but too complex for current needs
   - ⚠️ Would require significant refactoring
   - ⚠️ Team not familiar with full DDD patterns
   - ✅ May revisit as complexity grows

3. **Action Classes (Single-Action Controllers)**:
   - ⚠️ Good for very specific operations
   - ❌ Would create too many files for COPRRA's scope
   - ❌ Harder to group related operations

4. **Repositories Only (No Service Layer)**:
   - ❌ Repositories should only handle data access
   - ❌ Business logic still needs a home
   - ❌ Doesn't solve the complexity problem

## Consequences

### Positive

1. **Separation of Concerns**:
   - Clear boundary between HTTP and business logic
   - Controllers focus on request/response handling
   - Services focus on business operations

2. **Testability**:
   - Services can be unit tested without HTTP layer
   - Easy to mock dependencies
   - Faster test execution
   - **Current Result**: 696 tests, 95%+ coverage

3. **Code Reusability**:
   - Same service used by web, API, and console commands
   - No logic duplication
   - **Example**: `ProductService` used by web controllers, API controllers, and `UpdatePricesCommand`

4. **Maintainability**:
   - Single file to modify for business logic changes
   - Easier to understand codebase structure
   - Services average 200-300 lines (manageable size)

5. **Dependency Injection**:
   - Laravel's service container handles wiring
   - Type-safe constructor injection
   - Easy to swap implementations via interfaces

6. **Scalability**:
   - **50+ services** organized by domain
   - Each service independently maintainable
   - New features don't bloat existing services

### Negative

1. **Additional Abstraction Layer**:
   - More files to navigate (controller → service)
   - New developers need to learn the pattern
   - Slight performance overhead (negligible with OPcache)

2. **Over-Engineering Risk**:
   - Simple CRUD operations might not need services
   - Need discipline to avoid creating unnecessary services
   - **Mitigation**: Use services only for non-trivial business logic

3. **Service Proliferation**:
   - Risk of creating too many single-method services
   - **Mitigation**: Group related operations in same service
   - **Current Status**: 50+ services is manageable

4. **Dependency Chains**:
   - Services depending on services can create deep chains
   - Harder to trace execution flow for complex operations
   - **Mitigation**: PHPStan helps detect circular dependencies

### Neutral

1. **Service Providers Required**:
   - Need to register services (usually in `AppServiceProvider`)
   - **Current Status**: 12+ service providers for modular registration

2. **Learning Curve**:
   - Team needs to understand service layer pattern
   - **Mitigation**: Well-documented in CLAUDE.md

3. **File Organization**:
   - Need consistent naming and directory structure
   - **Current Standard**: `{Domain}Service` naming pattern

## Results After Implementation

**Quantitative Metrics**:
- ✅ 50+ services organized by domain
- ✅ Controllers average 100-150 lines (down from 400+ before)
- ✅ Services average 200-300 lines (well-scoped)
- ✅ 95%+ test coverage with isolated unit tests
- ✅ PHPStan Level max passing (strict type safety)

**Key Services Implemented**:
- **Core**: `ProductService`, `OrderService`, `PriceSearchService`
- **AI**: `AIService`, `ContinuousQualityMonitor`, `StrictQualityAgent`
- **Security**: `FileSecurityService`, `LoginAttemptService`, `PasswordPolicyService`
- **Integration**: Store adapters (`AmazonAdapter`, `EbayAdapter`, `NoonAdapter`)
- **Performance**: `CacheService`, `PerformanceReporter`, `DatabaseOptimizerService`

**Architectural Benefits Realized**:
- Clear separation between HTTP, business, and data layers
- Consistent pattern across all features
- Easy to add new features without touching existing code
- Services can be composed for complex workflows
- Event-driven architecture integrates naturally with services

## References

- [Laravel Service Container Documentation](https://laravel.com/docs/12.x/container)
- [SOLID Principles - Single Responsibility](https://en.wikipedia.org/wiki/Single-responsibility_principle)
- [Architectural Audit Report - Chapter 7: Structural Analysis](../architectural_audit/07_Structural_Analysis.md)
- [CLAUDE.md - Service Layer Architecture](../../CLAUDE.md#1-service-layer-architecture)
- Related ADRs:
  - None (first ADR)
