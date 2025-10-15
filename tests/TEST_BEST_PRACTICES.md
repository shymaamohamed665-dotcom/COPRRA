# Test Best Practices for COPRRA

## 🎯 Purpose

This document outlines best practices to prevent **interdependent tests** and ensure reliable, isolated test execution in the COPRRA project.

---

## ⚠️ The Problem: Interdependent Tests

**Symptoms:**
- A test passes in isolation but fails when run with other tests
- A previously passing test starts failing after working on unrelated tests
- Tests pass/fail based on execution order
- Flaky tests that sometimes pass and sometimes fail

**Root Causes:**
- Shared mutable state (database, files, cache, globals)
- Tests relying on side effects from other tests
- No proper cleanup between tests
- External dependencies not mocked

---

## ✅ Solutions Implemented

### 1. **Database Transaction Isolation**

**What it does:**
Each test runs inside a database transaction that is automatically rolled back after the test completes.

**Implementation:**
```php
// tests/TestCase.php
protected array $connectionsToTransact = ['sqlite'];
```

**Result:** Database changes in one test don't affect other tests.

---

### 2. **Randomized Test Execution**

**What it does:**
Tests run in random order to expose hidden dependencies.

**Implementation:**
```xml
<!-- phpunit.xml -->
<phpunit executionOrder="random" resolveDependencies="true">
```

**Result:** Order-dependent tests are immediately exposed.

---

### 3. **Global State Monitoring**

**What it does:**
PHPUnit monitors and backs up global variables between tests.

**Implementation:**
```xml
<!-- phpunit.xml -->
<phpunit backupGlobals="true" beStrictAboutChangesToGlobalState="true">
```

**Result:** Tests that modify globals are detected and warned.

---

### 4. **Proper Cleanup (tearDown)**

**What it does:**
Clears caches and resets state after each test.

**Implementation:**
```php
// tests/DatabaseSetup.php - tearDownDatabase()
- Auto rollback transactions
- Flush cache
- Clear application caches
```

**Result:** No residual state persists between tests.

---

## 📋 Writing Isolated Tests: Guidelines

### ✅ DO:

1. **Use RefreshDatabase or database transactions**
   ```php
   use Illuminate\Foundation\Testing\RefreshDatabase;

   class MyTest extends TestCase
   {
       use RefreshDatabase;
   }
   ```

2. **Create all test data within the test**
   ```php
   public function test_user_can_create_product()
   {
       $user = User::factory()->create();
       $this->actingAs($user);

       // Test logic here
   }
   ```

3. **Mock external dependencies**
   ```php
   public function test_api_call()
   {
       Http::fake([
           'api.example.com/*' => Http::response(['data' => 'fake'], 200),
       ]);

       // Test logic here
   }
   ```

4. **Use factories for test data**
   ```php
   $product = Product::factory()->create([
       'name' => 'Test Product',
       'price' => 100,
   ]);
   ```

5. **Clean up in setUp/tearDown if needed**
   ```php
   protected function setUp(): void
   {
       parent::setUp();
       Cache::flush();
   }
   ```

---

### ❌ DON'T:

1. **Don't share test data across tests**
   ```php
   // BAD
   class MyTest extends TestCase
   {
       protected $user; // Shared state!

       public function setUp(): void
       {
           parent::setUp();
           $this->user = User::create([...]); // Persists across tests!
       }
   }
   ```

2. **Don't rely on test execution order**
   ```php
   // BAD
   public function test_1_create_user() { ... }
   public function test_2_update_user() { ... } // Assumes test_1 ran first!
   ```

3. **Don't modify global state without cleanup**
   ```php
   // BAD
   public function test_config()
   {
       config(['app.timezone' => 'UTC']); // Affects other tests!
   }

   // GOOD
   public function test_config()
   {
       $original = config('app.timezone');
       config(['app.timezone' => 'UTC']);

       // Test logic

       config(['app.timezone' => $original]); // Restore
   }
   ```

4. **Don't use real external APIs**
   ```php
   // BAD
   Http::get('https://real-api.com/data'); // Flaky, slow, unreliable

   // GOOD
   Http::fake();
   ```

5. **Don't create files without cleanup**
   ```php
   // BAD
   file_put_contents('/tmp/test.txt', 'data');

   // GOOD
   $file = tempnam(sys_get_temp_dir(), 'test');
   file_put_contents($file, 'data');
   // ... test logic ...
   unlink($file); // Cleanup
   ```

---

## 🔍 Debugging Interdependent Tests

### Finding the Culprit

1. **Run tests with `--order-by=random`** (already enabled)
   ```bash
   php artisan test --order-by=random
   ```

2. **Use `--stop-on-failure`** to catch first failure
   ```bash
   php artisan test --stop-on-failure
   ```

3. **Isolate the failing test**
   ```bash
   php artisan test --filter=test_specific_method
   ```

4. **Check for global state changes**
   - Look for config changes
   - Check for static variables
   - Inspect singletons

5. **Use git bisect** to find the commit that introduced the problem
   ```bash
   git bisect start
   git bisect bad HEAD
   git bisect good <last-known-good-commit>
   ```

---

## 🏗️ Test Structure Best Practices

### Arrange-Act-Assert (AAA) Pattern

```php
public function test_user_can_purchase_product()
{
    // ARRANGE: Set up test data
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100]);

    // ACT: Perform the action
    $this->actingAs($user)
         ->post('/purchase', ['product_id' => $product->id]);

    // ASSERT: Verify the outcome
    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
}
```

---

## 🚀 Running Tests

### Recommended Commands

```bash
# Run all tests with random order
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run single test file
php artisan test tests/Feature/ProductTest.php

# Run single test method
php artisan test --filter=test_user_can_create_product

# Run with coverage
php artisan test --coverage

# Run tests in parallel (faster)
php artisan test --parallel
```

---

## 📚 Additional Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Best Practices](https://phpunit.readthedocs.io/en/latest/writing-tests-for-phpunit.html)
- [Martin Fowler on Test Isolation](https://martinfowler.com/bliki/TestIsolation.html)

---

## 🔐 Summary

**Key Takeaways:**
1. ✅ Each test should be **completely independent**
2. ✅ Tests should work **in any order**
3. ✅ Always **mock external dependencies**
4. ✅ Use **database transactions** for isolation
5. ✅ **Clean up** after each test (auto-handled now)
6. ✅ **Never rely** on test execution order
7. ✅ Use **factories** for test data creation
8. ✅ Run tests with **random order** enabled (default)

---

**Last Updated:** 2025-10-15
**Maintained By:** COPRRA Development Team
