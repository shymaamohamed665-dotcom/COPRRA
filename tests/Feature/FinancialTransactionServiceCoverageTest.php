<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Store;
use App\Services\AuditService;
use App\Services\FinancialTransactionService;
use Exception;
use Mockery;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class FinancialTransactionServiceCoverageTest extends TestCase
{
    public function test_create_price_offer_updates_product_price_from_new_offer(): void
    {
        $product = Product::factory()->create(['price' => 100.00]);
        $store = Store::factory()->create();

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('logCreated')->once();
        $audit->shouldReceive('logUpdated')->atLeast()->once(); // when product price updates
        $service = new FinancialTransactionService($audit);

        $offer = $service->createPriceOffer([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'new_price' => 79.99,
            'is_available' => true,
        ]);

        $this->assertInstanceOf(PriceOffer::class, $offer);
        $product->refresh();
        $this->assertSame(79.99, (float) $product->price);
    }

    public function test_update_product_price_rejects_negative_value(): void
    {
        $product = Product::factory()->create(['price' => 50.00]);
        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('logUpdated')->never();
        $service = new FinancialTransactionService($audit);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Price cannot be negative');
        $service->updateProductPrice($product, -5.0, 'Invalid test');
    }

    public function test_update_price_offer_changes_price_and_updates_product_to_lowest_available(): void
    {
        $product = Product::factory()->create(['price' => 120.00]);
        $store = Store::factory()->create();

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('logCreated')->atLeast()->once();
        $audit->shouldReceive('logUpdated')->atLeast()->once();
        $service = new FinancialTransactionService($audit);

        // Create initial offer at 100
        $offer = $service->createPriceOffer([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'new_price' => 100.00,
            'is_available' => true,
        ]);

        $product->refresh();
        $this->assertSame(100.00, (float) $product->price);

        // Create another existing offer lower than current price (95)
        $lower = PriceOffer::query()->create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'price' => 95.00,
            'is_available' => true,
        ]);

        // Update the first offer even lower (90), service should map and then re-evaluate lowest
        $updated = $service->updatePriceOffer($offer, [
            'new_price' => 90.00,
        ]);

        $this->assertInstanceOf(PriceOffer::class, $updated);

        $product->refresh();
        // Lowest available offer should now be 90
        $this->assertSame(90.00, (float) $product->price);

        // Sanity: ensure the other offer still exists
        $this->assertTrue($lower->exists);
    }

    public function test_update_price_offer_unavailable_recalculates_to_next_lowest_available_offer(): void
    {
        $product = Product::factory()->create(['price' => 120.00]);
        $store = Store::factory()->create();

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('logCreated')->atLeast()->once();
        $audit->shouldReceive('logUpdated')->atLeast()->once();
        $service = new FinancialTransactionService($audit);

        // Create lowest offer via service at 80 (available)
        $lowest = $service->createPriceOffer([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'new_price' => 80.00,
            'is_available' => true,
        ]);

        $product->refresh();
        $this->assertSame(80.00, (float) $product->price);

        // Create another available offer at 90 (second-lowest)
        $second = PriceOffer::query()->create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'price' => 90.00,
            'is_available' => true,
            'status' => 'active',
        ]);

        // Mark the lowest offer unavailable; product price should update to 90
        $updated = $service->updatePriceOffer($lowest, [
            'is_available' => false,
        ]);

        $this->assertInstanceOf(PriceOffer::class, $updated);

        $product->refresh();
        $this->assertSame(90.00, (float) $product->price);

        // Sanity check: the second offer still exists and is available
        $this->assertTrue($second->exists);
        $this->assertTrue((bool) $second->is_available);
    }

    public function test_delete_price_offer_returns_true(): void
    {
        $product = Product::factory()->create(['price' => 60.00]);
        $store = Store::factory()->create();
        $offer = PriceOffer::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'price' => 55.00,
            'is_available' => true,
        ]);

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('logDeleted')->once();
        $service = new FinancialTransactionService($audit);

        $this->assertTrue($service->deletePriceOffer($offer));
    }
}
