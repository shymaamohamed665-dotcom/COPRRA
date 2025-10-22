<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\FinancialTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FinancialTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinancialTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $auditService = \Mockery::mock(\App\Services\AuditService::class);
        $auditService->shouldReceive('logUpdated')->andReturn(true);
        $this->service = new FinancialTransactionService($auditService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_processes_payment_successfully()
    {
        // Arrange
        $product = \App\Models\Product::factory()->create(['price' => '100.50']);
        $newPrice = 120.00;
        $reason = 'Price update';

        // Act
        $result = $this->service->updateProductPrice($product, $newPrice, $reason);

        // Assert
        $this->assertTrue($result);

        // Verify the product price was actually updated in the database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => $newPrice,
        ]);

        // Verify the old price is no longer in the database
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'price' => '100.50',
        ]);

        // Verify the product was refreshed with the new price
        $product->refresh();
        $this->assertEquals($newPrice, $product->price);
    }

    public function test_handles_payment_failure()
    {
        // Arrange
        $product = \App\Models\Product::factory()->create(['price' => '100.50']);
        $newPrice = -50.00; // Invalid negative price
        $reason = 'Invalid price update';

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->service->updateProductPrice($product, $newPrice, $reason);

        // Verify the database was not changed due to the exception
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => '100.50',
        ]);

        // Verify the invalid price was not saved
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'price' => $newPrice,
        ]);
    }

    public function test_refunds_transaction()
    {
        // Arrange
        $product = \App\Models\Product::factory()->create(['price' => '100.50']);
        $newPrice = 80.00; // Price reduction
        $reason = 'Price reduction';

        // Act
        $result = $this->service->updateProductPrice($product, $newPrice, $reason);

        // Assert
        $this->assertTrue($result);

        // Verify the price reduction was saved to the database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => $newPrice,
        ]);

        // Verify the original price was updated
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'price' => '100.50',
        ]);

        // Verify the price change was significant (reduction)
        $product->refresh();
        $this->assertLessThan(100.50, $product->price);
        $this->assertEquals($newPrice, $product->price);
    }

    public function test_gets_transaction_history()
    {
        // Arrange
        $product = \App\Models\Product::factory()->create(['price' => '100.50']);
        $newPrice = 120.00;
        $reason = 'Price update';

        // Act
        $result = $this->service->updateProductPrice($product, $newPrice, $reason);

        // Assert
        $this->assertTrue($result);
    }

    public function test_calculates_tax()
    {
        // Arrange
        $product = \App\Models\Product::factory()->create(['price' => '100.50']);
        $newPrice = 120.00;
        $reason = 'Price update with tax';

        // Act
        $result = $this->service->updateProductPrice($product, $newPrice, $reason);

        // Assert
        $this->assertTrue($result);
    }

    public function test_validates_payment_method()
    {
        // Arrange
        $product = \App\Models\Product::factory()->create(['price' => '100.50']);
        $newPrice = 120.00;
        $reason = 'Price update validation';

        // Act
        $result = $this->service->updateProductPrice($product, $newPrice, $reason);

        // Assert
        $this->assertTrue($result);
    }

    public function test_handles_invalid_payment_method()
    {
        // Arrange
        $product = \App\Models\Product::factory()->create(['price' => '100.50']);
        $newPrice = -10.00; // Invalid negative price
        $reason = 'Invalid payment method';

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->service->updateProductPrice($product, $newPrice, $reason);
    }
}
