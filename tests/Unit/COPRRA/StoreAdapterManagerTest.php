<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Services\StoreAdapterManager;
use App\Services\StoreAdapters\AmazonAdapter;
use Tests\TestCase;

/**
 * @covers \App\Services\StoreAdapterManager
 */
class StoreAdapterManagerTest extends TestCase
{
    protected StoreAdapterManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new StoreAdapterManager([]);
    }

    /** @test */
    public function it_registers_default_adapters(): void
    {
        $adapters = $this->manager->getAllAdapters();

        $this->assertCount(3, $adapters);
        $this->assertArrayHasKey('amazon', $adapters);
        $this->assertArrayHasKey('ebay', $adapters);
        $this->assertArrayHasKey('noon', $adapters);
    }

    /** @test */
    public function it_gets_adapter_by_identifier(): void
    {
        $adapter = $this->manager->getAdapter('amazon');

        $this->assertInstanceOf(AmazonAdapter::class, $adapter);
        $this->assertEquals('Amazon', $adapter->getStoreName());
    }

    /** @test */
    public function it_returns_null_for_unknown_adapter(): void
    {
        $adapter = $this->manager->getAdapter('unknown_store');

        $this->assertNull($adapter);
    }

    /** @test */
    public function it_checks_if_store_is_supported(): void
    {
        $this->assertTrue($this->manager->isStoreSupported('amazon'));
        $this->assertTrue($this->manager->isStoreSupported('ebay'));
        $this->assertTrue($this->manager->isStoreSupported('noon'));
        $this->assertFalse($this->manager->isStoreSupported('unknown'));
    }

    /** @test */
    public function it_gets_supported_stores(): void
    {
        $stores = $this->manager->getSupportedStores();

        $this->assertIsArray($stores);
        $this->assertContains('amazon', $stores);
        $this->assertContains('ebay', $stores);
        $this->assertContains('noon', $stores);
    }

    /** @test */
    public function it_validates_amazon_identifier(): void
    {
        $this->assertTrue($this->manager->validateIdentifier('amazon', 'B08N5WRWNW'));
        $this->assertFalse($this->manager->validateIdentifier('amazon', 'invalid'));
        $this->assertFalse($this->manager->validateIdentifier('amazon', '123'));
    }

    /** @test */
    public function it_validates_ebay_identifier(): void
    {
        $this->assertTrue($this->manager->validateIdentifier('ebay', '123456789012'));
        $this->assertFalse($this->manager->validateIdentifier('ebay', 'abc'));
        $this->assertFalse($this->manager->validateIdentifier('ebay', '123'));
    }

    /** @test */
    public function it_validates_noon_identifier(): void
    {
        $this->assertTrue($this->manager->validateIdentifier('noon', 'N12345678'));
        $this->assertFalse($this->manager->validateIdentifier('noon', '12345678'));
        $this->assertFalse($this->manager->validateIdentifier('noon', 'ABC123'));
    }

    /** @test */
    public function it_gets_product_url_for_amazon(): void
    {
        $url = $this->manager->getProductUrl('amazon', 'B08N5WRWNW');

        $this->assertStringContainsString('amazon.com', $url);
        $this->assertStringContainsString('B08N5WRWNW', $url);
    }

    /** @test */
    public function it_gets_product_url_for_ebay(): void
    {
        $url = $this->manager->getProductUrl('ebay', '123456789012');

        $this->assertStringContainsString('ebay.com', $url);
        $this->assertStringContainsString('123456789012', $url);
    }

    /** @test */
    public function it_gets_product_url_for_noon(): void
    {
        $url = $this->manager->getProductUrl('noon', 'N12345678');

        $this->assertStringContainsString('noon.com', $url);
        $this->assertStringContainsString('N12345678', $url);
    }

    /** @test */
    public function it_returns_null_for_unknown_store_url(): void
    {
        $url = $this->manager->getProductUrl('unknown', '123');

        $this->assertNull($url);
    }

    /** @test */
    public function it_gets_statistics(): void
    {
        $stats = $this->manager->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_adapters', $stats);
        $this->assertArrayHasKey('available_adapters', $stats);
        $this->assertArrayHasKey('adapters', $stats);
        $this->assertEquals(3, $stats['total_adapters']);
    }

    /** @test */
    public function it_gets_adapter_rate_limits(): void
    {
        $adapter = $this->manager->getAdapter('amazon');
        $limits = $adapter->getRateLimits();

        $this->assertIsArray($limits);
        $this->assertArrayHasKey('requests_per_minute', $limits);
        $this->assertArrayHasKey('requests_per_hour', $limits);
        $this->assertArrayHasKey('requests_per_day', $limits);
    }

    /** @test */
    public function amazon_adapter_has_correct_properties(): void
    {
        $adapter = $this->manager->getAdapter('amazon');

        $this->assertEquals('Amazon', $adapter->getStoreName());
        $this->assertEquals('amazon', $adapter->getStoreIdentifier());
    }

    /** @test */
    public function ebay_adapter_has_correct_properties(): void
    {
        $adapter = $this->manager->getAdapter('ebay');

        $this->assertEquals('eBay', $adapter->getStoreName());
        $this->assertEquals('ebay', $adapter->getStoreIdentifier());
    }

    /** @test */
    public function noon_adapter_has_correct_properties(): void
    {
        $adapter = $this->manager->getAdapter('noon');

        $this->assertEquals('Noon', $adapter->getStoreName());
        $this->assertEquals('noon', $adapter->getStoreIdentifier());
    }
}
