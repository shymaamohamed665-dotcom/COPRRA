<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class StoreModelTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_store_has_fillable_attributes(): void
    {
        // Arrange
        $attributes = [
            'name' => 'Test Store',
            'slug' => 'test-store',
            'description' => 'A test store',
            'logo_url' => 'https://example.com/logo.png',
            'website_url' => 'https://example.com',
            'country_code' => 'US',
            'supported_countries' => ['US', 'CA'],
            'is_active' => true,
            'priority' => 1,
            'affiliate_base_url' => 'https://affiliate.com/{AFFILIATE_CODE}?url={URL}',
            'affiliate_code' => 'TEST123',
            'api_config' => ['key' => 'value'],
            'currency_id' => null,
        ];

        // Act
        $store = Store::create($attributes);

        // Assert
        $this->assertInstanceOf(Store::class, $store);
        $this->assertEquals('Test Store', $store->name);
        $this->assertEquals('test-store', $store->slug);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_store_casts_attributes_correctly(): void
    {
        // Arrange
        $store = Store::factory()->create([
            'is_active' => '1', // string
            'priority' => '5', // string
            'supported_countries' => json_encode(['US', 'CA']),
            'api_config' => json_encode(['key' => 'value']),
        ]);

        // Act & Assert
        $this->assertIsBool($store->is_active);
        $this->assertTrue($store->is_active);
        $this->assertIsInt($store->priority);
        $this->assertEquals(5, $store->priority);
        $this->assertIsArray($store->supported_countries);
        $this->assertEquals(['US', 'CA'], $store->supported_countries);
        $this->assertIsArray($store->api_config);
        $this->assertEquals(['key' => 'value'], $store->api_config);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_store_relationships(): void
    {
        // Arrange
        $store = Store::factory()->create();
        $currency = Currency::factory()->create();
        $store->currency_id = $currency->id;
        $store->save();

        Product::factory()->create(['store_id' => $store->id]);
        PriceOffer::factory()->create(['store_id' => $store->id]);

        // Act
        $store->refresh();

        // Assert
        $this->assertInstanceOf(Currency::class, $store->currency);
        $this->assertEquals($currency->id, $store->currency->id);
        $this->assertCount(1, $store->products);
        $this->assertCount(1, $store->priceOffers);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_active_scope(): void
    {
        // Arrange
        Store::factory()->create(['is_active' => true]);
        Store::factory()->create(['is_active' => false]);

        // Act
        $activeStores = Store::active()->get();

        // Assert
        $this->assertCount(1, $activeStores);
        $this->assertTrue($activeStores->first()->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_search_scope(): void
    {
        // Arrange
        Store::factory()->create(['name' => 'Apple Store']);
        Store::factory()->create(['name' => 'Google Store']);
        Store::factory()->create(['name' => 'Microsoft Store']);

        // Act
        $results = Store::search('Apple')->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Apple Store', $results->first()->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_generate_affiliate_url(): void
    {
        // Arrange
        $store = Store::factory()->create([
            'affiliate_base_url' => 'https://affiliate.com/{AFFILIATE_CODE}?url={URL}',
            'affiliate_code' => 'TEST123',
        ]);

        // Act
        $affiliateUrl = $store->generateAffiliateUrl('https://product.com/item');

        // Assert
        $expected = 'https://affiliate.com/TEST123?url=https%3A//product.com/item';
        $this->assertEquals($expected, $affiliateUrl);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_generate_affiliate_url_without_config(): void
    {
        // Arrange
        $store = Store::factory()->create([
            'affiliate_base_url' => null,
            'affiliate_code' => null,
        ]);

        // Act
        $affiliateUrl = $store->generateAffiliateUrl('https://product.com/item');

        // Assert
        $this->assertEquals('https://product.com/item', $affiliateUrl);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_slug_generation_on_create(): void
    {
        // Arrange & Act
        $store = Store::create(['name' => 'Test Store Name']);

        // Assert
        $this->assertEquals('test-store-name', $store->slug);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation_rules(): void
    {
        // Arrange
        $store = new Store;

        // Act
        $rules = $store->getRules();

        // Assert
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertEquals('required|string|max:255', $rules['name']);
    }
}
