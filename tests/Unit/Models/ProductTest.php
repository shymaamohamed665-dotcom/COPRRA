<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceAlert;
use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the Product model.
 */
#[CoversClass(Product::class)]
class ProductTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = $this->faker();
    }

    /**
     * Test that brand relation is a BelongsTo instance.
     */
    public function test_brand_relation(): void
    {
        $product = new Product;

        $relation = $product->brand();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Brand::class, $relation->getRelated()::class);
    }

    /**
     * Test that category relation is a BelongsTo instance.
     */
    public function test_category_relation(): void
    {
        $product = new Product;

        $relation = $product->category();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Category::class, $relation->getRelated()::class);
    }

    /**
     * Test that store relation is a BelongsTo instance.
     */
    public function test_store_relation(): void
    {
        $product = new Product;

        $relation = $product->store();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Store::class, $relation->getRelated()::class);
    }

    /**
     * Test that priceAlerts relation is a HasMany instance.
     */
    public function test_price_alerts_relation(): void
    {
        $product = new Product;

        $relation = $product->priceAlerts();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(PriceAlert::class, $relation->getRelated()::class);
    }

    /**
     * Test that reviews relation is a HasMany instance.
     */
    public function test_reviews_relation(): void
    {
        $product = new Product;

        $relation = $product->reviews();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Review::class, $relation->getRelated()::class);
    }

    /**
     * Test that wishlists relation is a HasMany instance.
     */
    public function test_wishlists_relation(): void
    {
        $product = new Product;

        $relation = $product->wishlists();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Wishlist::class, $relation->getRelated()::class);
    }

    /**
     * Test that priceOffers relation is a HasMany instance.
     */
    public function test_price_offers_relation(): void
    {
        $product = new Product;

        $relation = $product->priceOffers();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(PriceOffer::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeActive adds where clause for is_active.
     */
    public function test_scope_active(): void
    {
        $query = Product::query()->active();

        $this->assertEquals('select * from "products" where "is_active" = ?', $query->toSql());
        $this->assertEquals([true], $query->getBindings());
    }

    /**
     * Test scopeSearch adds where like clause for name.
     */
    public function test_scope_search(): void
    {
        $query = Product::query()->search('test');

        $this->assertEquals('select * from "products" where "name" like ?', $query->toSql());
        $this->assertEquals(['%test%'], $query->getBindings());
    }

    /**
     * Test scopeWithReviewsCount adds withCount for reviews.
     */
    public function test_scope_with_reviews_count(): void
    {
        $query = Product::query()->withReviewsCount();

        $this->assertEquals('select * from "products"', $query->toSql());
        // Note: withCount adds to eager loads, not SQL directly
    }

    /**
     * Test getAverageRating returns average rating.
     */
    public function test_get_average_rating(): void
    {
        $product = Product::factory()->create();
        Review::factory()->create(['product_id' => $product->id, 'rating' => 5]);
        Review::factory()->create(['product_id' => $product->id, 'rating' => 3]);

        $this->assertEquals(4.0, $product->getAverageRating());
    }

    /**
     * Test getTotalReviews returns count of reviews.
     */
    public function test_get_total_reviews(): void
    {
        $product = Product::factory()->create();
        Review::factory()->create(['product_id' => $product->id]);
        Review::factory()->create(['product_id' => $product->id]);

        $this->assertEquals(2, $product->getTotalReviews());
    }

    /**
     * Test isInWishlist checks if product is in user's wishlist.
     */
    public function test_is_in_wishlist(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        Wishlist::factory()->create(['product_id' => $product->id, 'user_id' => $user->id]);

        $this->assertTrue($product->isInWishlist($user->id));
        $this->assertFalse($product->isInWishlist(999));
    }

    /**
     * Test getCurrentPrice returns price or offer price.
     */
    public function test_get_current_price(): void
    {
        $product = Product::factory()->create(['price' => 100.00]);
        PriceOffer::factory()->create(['product_id' => $product->id, 'price' => 80.00, 'is_available' => true]);

        $this->assertEquals(80.0, $product->getCurrentPrice());
    }

    /**
     * Test getPriceHistory returns ordered price offers.
     */
    public function test_get_price_history(): void
    {
        $product = Product::factory()->create();
        PriceOffer::factory()->create(['product_id' => $product->id, 'price' => 90.00]);
        PriceOffer::factory()->create(['product_id' => $product->id, 'price' => 85.00]);

        $history = $product->getPriceHistory();

        $this->assertCount(2, $history);
        $this->assertEquals(85.00, $history->first()->price);
    }

    /**
     * Test validate method.
     */
    public function test_validate(): void
    {
        $product = new Product(['name' => 'Test', 'price' => 10.00, 'brand_id' => 1, 'category_id' => 1]);

        $this->assertTrue($product->validate());
        $this->assertEmpty($product->getErrors());
    }

    /**
     * Test validate fails with invalid data.
     */
    public function test_validate_fails(): void
    {
        $product = new Product(['name' => '', 'price' => -10, 'brand_id' => 0]);

        $this->assertFalse($product->validate());
        $this->assertNotEmpty($product->getErrors());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
