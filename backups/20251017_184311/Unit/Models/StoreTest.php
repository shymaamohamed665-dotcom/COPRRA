<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Currency;
use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for the Store model.
 *
 * @covers \App\Models\Store
 */
class StoreTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = $this->faker();
    }

    /**
     * Test that priceOffers relation is a HasMany instance.
     */
    public function test_price_offers_relation(): void
    {
        $store = new Store;

        $relation = $store->priceOffers();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(PriceOffer::class, $relation->getRelated()::class);
    }

    /**
     * Test that products relation is a HasMany instance.
     */
    public function test_products_relation(): void
    {
        $store = new Store;

        $relation = $store->products();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Product::class, $relation->getRelated()::class);
    }

    /**
     * Test that currency relation is a BelongsTo instance.
     */
    public function test_currency_relation(): void
    {
        $store = new Store;

        $relation = $store->currency();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Currency::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeActive adds where clause for is_active.
     */
    public function test_scope_active(): void
    {
        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('where')->once()->with('is_active', true)->andReturnSelf();

        $store = new Store;
        $result = $store->scopeActive($query);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * Test scopeSearch adds where like clause for name.
     */
    public function test_scope_search(): void
    {
        $search = 'test';
        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('where')->once()->with('name', 'like', "%{$search}%")->andReturnSelf();

        $store = new Store;
        $result = $store->scopeSearch($query, $search);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * Test that slug is auto-generated on creating.
     */
    public function test_slug_auto_generated_on_creating(): void
    {
        $store = new Store(['name' => 'Test Store']);
        $store->save();

        $this->assertEquals('test-store', $store->slug);
        $store->delete();
    }

    /**
     * Test that slug is updated on name change.
     */
    public function test_slug_updated_on_name_change(): void
    {
        $store = new Store(['name' => 'Old Name']);
        $store->save();

        $store->name = 'New Name';
        $store->save();

        $this->assertEquals('new-name', $store->slug);
        $store->delete();
    }

    /**
     * Test generateAffiliateUrl returns productUrl if no affiliate data.
     */
    public function test_generate_affiliate_url_no_affiliate_data(): void
    {
        $store = new Store;
        $productUrl = 'https://example.com/product';

        $result = $store->generateAffiliateUrl($productUrl);

        $this->assertEquals($productUrl, $result);
    }

    /**
     * Test generateAffiliateUrl generates affiliate URL.
     */
    public function test_generate_affiliate_url(): void
    {
        $store = new Store([
            'affiliate_base_url' => 'https://affiliate.com/{AFFILIATE_CODE}?url={URL}',
            'affiliate_code' => 'CODE123',
        ]);
        $productUrl = 'https://example.com/product';

        $result = $store->generateAffiliateUrl($productUrl);

        $this->assertEquals('https://affiliate.com/CODE123?url=https%3A//example.com/product', $result);
    }

    /**
     * Test getRules returns the validation rules.
     */
    public function test_get_rules(): void
    {
        $store = new Store;
        $rules = $store->getRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('priority', $rules);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
