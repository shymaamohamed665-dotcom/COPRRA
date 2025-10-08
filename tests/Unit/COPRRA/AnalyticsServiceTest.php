<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @covers \App\Services\AnalyticsService
 * @covers \App\Models\AnalyticsEvent
 */
class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_tracks_price_comparison_event(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $event = $analyticsService->trackPriceComparison($product->id, $user->id);

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertEquals(AnalyticsEvent::TYPE_PRICE_COMPARISON, $event->event_type);
        $this->assertEquals($product->id, $event->product_id);
        $this->assertEquals($user->id, $event->user_id);
    }

    /** @test */
    public function it_tracks_product_view_event(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();

        $event = $analyticsService->trackProductView($product->id);

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertEquals(AnalyticsEvent::TYPE_PRODUCT_VIEW, $event->event_type);
        $this->assertEquals($product->id, $event->product_id);
    }

    /** @test */
    public function it_tracks_search_event(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $query = 'laptop';
        $filters = ['category' => 'electronics'];

        $event = $analyticsService->trackSearch($query, null, $filters);

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertEquals(AnalyticsEvent::TYPE_SEARCH, $event->event_type);
        $this->assertEquals($query, $event->metadata['query']);
        $this->assertEquals($filters, $event->metadata['filters']);
    }

    /** @test */
    public function it_tracks_store_click_event(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();
        $store = Store::factory()->create();

        $event = $analyticsService->trackStoreClick($store->id, $product->id);

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertEquals(AnalyticsEvent::TYPE_STORE_CLICK, $event->event_type);
        $this->assertEquals($store->id, $event->store_id);
        $this->assertEquals($product->id, $event->product_id);
    }

    /** @test */
    public function it_gets_most_viewed_products(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        // Create view events
        for ($i = 0; $i < 5; $i++) {
            $analyticsService->trackProductView($product1->id);
        }

        for ($i = 0; $i < 3; $i++) {
            $analyticsService->trackProductView($product2->id);
        }

        $mostViewed = $analyticsService->getMostViewedProducts(10, 30);

        $this->assertCount(2, $mostViewed);
        $this->assertEquals($product1->id, $mostViewed[0]['product_id']);
        $this->assertEquals(5, $mostViewed[0]['view_count']);
    }

    /** @test */
    public function it_gets_most_searched_queries(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $analyticsService->trackSearch('laptop');
        $analyticsService->trackSearch('laptop');
        $analyticsService->trackSearch('phone');

        $mostSearched = $analyticsService->getMostSearchedQueries(10, 30);

        $this->assertArrayHasKey('laptop', $mostSearched);
        $this->assertEquals(2, $mostSearched['laptop']);
        $this->assertArrayHasKey('phone', $mostSearched);
        $this->assertEquals(1, $mostSearched['phone']);
    }

    /** @test */
    public function it_gets_most_popular_stores(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();

        // Create click events
        for ($i = 0; $i < 4; $i++) {
            $analyticsService->trackStoreClick($store1->id, $product->id);
        }

        for ($i = 0; $i < 2; $i++) {
            $analyticsService->trackStoreClick($store2->id, $product->id);
        }

        $mostPopular = $analyticsService->getMostPopularStores(10, 30);

        $this->assertCount(2, $mostPopular);
        $this->assertEquals($store1->id, $mostPopular[0]['store_id']);
        $this->assertEquals(4, $mostPopular[0]['click_count']);
    }

    /** @test */
    public function it_gets_price_comparison_statistics(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $analyticsService->trackPriceComparison($product->id, $user->id);
        }

        $stats = $analyticsService->getPriceComparisonStats(30);

        $this->assertEquals(5, $stats['total_comparisons']);
        $this->assertEquals(1, $stats['unique_products']);
        $this->assertEquals(1, $stats['unique_users']);
        $this->assertIsFloat($stats['average_per_day']);
    }

    /** @test */
    public function it_gets_dashboard_data(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();

        $analyticsService->trackProductView($product->id);
        $analyticsService->trackPriceComparison($product->id);
        $analyticsService->trackSearch('laptop');

        $dashboardData = $analyticsService->getDashboardData(30);

        $this->assertArrayHasKey('overview', $dashboardData);
        $this->assertArrayHasKey('price_comparisons', $dashboardData);
        $this->assertArrayHasKey('most_viewed_products', $dashboardData);
        $this->assertArrayHasKey('most_searched_queries', $dashboardData);
        $this->assertArrayHasKey('most_popular_stores', $dashboardData);
    }

    /** @test */
    public function it_cleans_old_analytics_data(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        // Create old and recent events
        $oldEvent = AnalyticsEvent::factory()->create([
            'created_at' => now()->subDays(100),
        ]);

        $recentEvent = AnalyticsEvent::factory()->create([
            'created_at' => now()->subDays(10),
        ]);

        $cleanedCount = $analyticsService->cleanOldData(90);

        $this->assertEquals(1, $cleanedCount);
        $this->assertDatabaseMissing('analytics_events', ['id' => $oldEvent->id]);
        $this->assertDatabaseHas('analytics_events', ['id' => $recentEvent->id]);
    }

    /** @test */
    public function it_does_not_track_when_disabled(): void
    {
        // Disable tracking
        config(['coprra.analytics.track_user_behavior' => false]);

        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();

        $event = $analyticsService->trackProductView($product->id);

        $this->assertNull($event);
        $this->assertDatabaseCount('analytics_events', 0);
    }

    /** @test */
    public function it_handles_null_user_id(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();

        $event = $analyticsService->trackPriceComparison($product->id, null);

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertNull($event->user_id);
    }

    /** @test */
    public function it_handles_empty_metadata(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();

        $event = $analyticsService->trackPriceComparison($product->id, null, []);

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertEquals([], $event->metadata);
    }
}
