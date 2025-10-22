<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceAlert;
use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Review;
use App\Models\Store;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_a_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test Description',
            'price' => 99.99,
            'image' => 'test.jpg',
            'is_active' => true,
            'stock_quantity' => 100,
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals('test-product', $product->slug);
        $this->assertEquals('Test Description', $product->description);
        $this->assertEquals(99.99, $product->price);
        $this->assertEquals('test.jpg', $product->image);
        $this->assertTrue($product->is_active);
        $this->assertEquals(100, $product->stock_quantity);

        // Assert that the product was actually saved to the database
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test Description',
            'price' => 99.99,
            'image' => 'test.jpg',
            'is_active' => true,
            'stock_quantity' => 100,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_casts_attributes_correctly(): void
    {
        $product = Product::factory()->create([
            'price' => '99.99',
            'is_active' => 1,
            'stock_quantity' => '100',
        ]);

        $this->assertIsString($product->price);
        $this->assertIsBool($product->is_active);
        $this->assertIsInt($product->stock_quantity);
        $this->assertEquals('99.99', $product->price);
        $this->assertTrue($product->is_active);
        $this->assertEquals(100, $product->stock_quantity);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_brand(): void
    {
        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        $this->assertInstanceOf(Brand::class, $product->brand);
        $this->assertEquals($brand->id, $product->brand->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $this->assertInstanceOf(Store::class, $product->store);
        $this->assertEquals($store->id, $product->store->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_many_price_alerts(): void
    {
        $product = Product::factory()->create();
        $user1 = \App\Models\User::factory()->create(['email' => 'user1@example.com']);
        $user2 = \App\Models\User::factory()->create(['email' => 'user2@example.com']);
        $priceAlert1 = PriceAlert::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        $priceAlert2 = PriceAlert::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);

        $this->assertCount(2, $product->priceAlerts);
        $this->assertTrue($product->priceAlerts->contains($priceAlert1));
        $this->assertTrue($product->priceAlerts->contains($priceAlert2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_many_reviews(): void
    {
        $product = Product::factory()->create();
        $user1 = \App\Models\User::factory()->create(['email' => 'user1@example.com']);
        $user2 = \App\Models\User::factory()->create(['email' => 'user2@example.com']);
        $review1 = Review::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        $review2 = Review::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);

        $this->assertCount(2, $product->reviews);
        $this->assertTrue($product->reviews->contains($review1));
        $this->assertTrue($product->reviews->contains($review2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_many_wishlists(): void
    {
        $product = Product::factory()->create();
        $user1 = \App\Models\User::factory()->create(['email' => 'user1@example.com']);
        $user2 = \App\Models\User::factory()->create(['email' => 'user2@example.com']);
        $wishlist1 = Wishlist::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        $wishlist2 = Wishlist::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);

        $this->assertCount(2, $product->wishlists);
        $this->assertTrue($product->wishlists->contains($wishlist1));
        $this->assertTrue($product->wishlists->contains($wishlist2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_many_price_offers(): void
    {
        $product = Product::factory()->create();
        $store1 = Store::factory()->create(['name' => 'Store 1']);
        $store2 = Store::factory()->create(['name' => 'Store 2']);
        $priceOffer1 = PriceOffer::factory()->create(['product_id' => $product->id, 'store_id' => $store1->id]);
        $priceOffer2 = PriceOffer::factory()->create(['product_id' => $product->id, 'store_id' => $store2->id]);

        $this->assertCount(2, $product->priceOffers);
        $this->assertTrue($product->priceOffers->contains($priceOffer1));
        $this->assertTrue($product->priceOffers->contains($priceOffer2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_active_filters_active_products(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);
        Product::factory()->create(['is_active' => true]);

        $activeProducts = Product::active()->get();

        $this->assertCount(2, $activeProducts);
        $this->assertTrue($activeProducts->every(fn ($product) => $product->is_active === true));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_search_filters_by_name(): void
    {
        Product::factory()->create(['name' => 'iPhone 15']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);
        Product::factory()->create(['name' => 'iPhone 14']);

        $iphoneProducts = Product::search('iPhone')->get();
        $samsungProducts = Product::search('Samsung')->get();

        $this->assertCount(2, $iphoneProducts);
        $this->assertCount(1, $samsungProducts);
        $this->assertTrue($iphoneProducts->every(fn ($product) => str_contains($product->name, 'iPhone')));
        $this->assertTrue($samsungProducts->every(fn ($product) => str_contains($product->name, 'Samsung')));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_with_reviews_count_adds_reviews_count(): void
    {
        $product = Product::factory()->create();
        $user1 = \App\Models\User::factory()->create(['email' => 'user1@example.com']);
        $user2 = \App\Models\User::factory()->create(['email' => 'user2@example.com']);
        $user3 = \App\Models\User::factory()->create(['email' => 'user3@example.com']);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user3->id]);

        $productWithCount = Product::withReviewsCount()->find($product->id);

        $this->assertEquals(3, $productWithCount->reviews_count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_average_rating(): void
    {
        $product = Product::factory()->create();
        $user1 = \App\Models\User::factory()->create(['email' => 'user1@example.com']);
        $user2 = \App\Models\User::factory()->create(['email' => 'user2@example.com']);
        $user3 = \App\Models\User::factory()->create(['email' => 'user3@example.com']);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id, 'rating' => 4]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id, 'rating' => 5]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user3->id, 'rating' => 3]);

        $averageRating = $product->getAverageRating();

        $this->assertEquals(4.0, $averageRating);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_average_rating_returns_zero_when_no_reviews(): void
    {
        $product = Product::factory()->create();

        $averageRating = $product->getAverageRating();

        $this->assertEquals(0.0, $averageRating);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_total_reviews(): void
    {
        $product = Product::factory()->create();
        $user1 = \App\Models\User::factory()->create(['email' => 'user1@example.com']);
        $user2 = \App\Models\User::factory()->create(['email' => 'user2@example.com']);
        $user3 = \App\Models\User::factory()->create(['email' => 'user3@example.com']);
        $user4 = \App\Models\User::factory()->create(['email' => 'user4@example.com']);
        $user5 = \App\Models\User::factory()->create(['email' => 'user5@example.com']);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user3->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user4->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user5->id]);

        $totalReviews = $product->getTotalReviews();

        $this->assertEquals(5, $totalReviews);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_in_wishlist(): void
    {
        $product = Product::factory()->create();
        $user = \App\Models\User::factory()->create(['email' => 'user@example.com']);
        Wishlist::factory()->create(['product_id' => $product->id, 'user_id' => $user->id]);

        $this->assertTrue($product->isInWishlist($user->id));
        $this->assertFalse($product->isInWishlist(999));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_current_price_with_active_offer(): void
    {
        $product = Product::factory()->create(['price' => 100.00]);
        PriceOffer::factory()->create([
            'product_id' => $product->id,
            'price' => 80.00,
            'is_available' => true,
            'created_at' => now(),
        ]);

        $currentPrice = $product->getCurrentPrice();

        $this->assertEquals(80.00, $currentPrice);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_current_price_without_active_offer(): void
    {
        $product = Product::factory()->create(['price' => 100.00]);

        $currentPrice = $product->getCurrentPrice();

        $this->assertEquals(100.00, $currentPrice);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_price_history(): void
    {
        $product = Product::factory()->create();
        $offer1 = PriceOffer::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,
            'created_at' => now()->subDay(),
        ]);
        $offer2 = PriceOffer::factory()->create([
            'product_id' => $product->id,
            'price' => 90.00,
            'created_at' => now(),
        ]);

        $priceHistory = $product->getPriceHistory();

        $this->assertCount(2, $priceHistory);
        $this->assertEquals($offer2->id, $priceHistory->first()->id); // Most recent first
        $this->assertEquals($offer1->id, $priceHistory->last()->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation_passes_with_valid_data(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'name' => 'Valid Product',
            'price' => 99.99,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);

        $this->assertTrue($product->validate());
        $this->assertEmpty($product->getErrors());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation_fails_with_missing_required_fields(): void
    {
        $product = new Product;

        $this->assertFalse($product->validate());
        $errors = $product->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('price', $errors);
        $this->assertArrayHasKey('brand_id', $errors);
        $this->assertArrayHasKey('category_id', $errors);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation_passes_with_string_price(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $product = new Product([
            'name' => 'Test Product',
            'price' => '99.99',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);

        $this->assertTrue($product->validate());
        $errors = $product->getErrors();
        $this->assertEmpty($errors);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation_fails_with_negative_price(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $product = new Product([
            'name' => 'Test Product',
            'price' => -10.00,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);

        $this->assertFalse($product->validate());
        $errors = $product->getErrors();
        $this->assertArrayHasKey('price', $errors);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_soft_deletes(): void
    {
        $product = Product::factory()->create();

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_booted_deletes_related_records(): void
    {
        $product = Product::factory()->create();
        $user1 = \App\Models\User::factory()->create(['email' => 'user1@example.com']);
        $user2 = \App\Models\User::factory()->create(['email' => 'user2@example.com']);
        $user3 = \App\Models\User::factory()->create(['email' => 'user3@example.com']);
        $priceAlert = PriceAlert::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        $review = Review::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);
        $wishlist = Wishlist::factory()->create(['product_id' => $product->id, 'user_id' => $user3->id]);
        $store = Store::factory()->create(['name' => 'Test Store']);
        $priceOffer = PriceOffer::factory()->create(['product_id' => $product->id, 'store_id' => $store->id]);

        $product->forceDelete();

        $this->assertDatabaseMissing('price_alerts', ['id' => $priceAlert->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('wishlists', ['id' => $wishlist->id]);
        $this->assertDatabaseMissing('price_offers', ['id' => $priceOffer->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_factory_creates_valid_product(): void
    {
        $product = Product::factory()->make();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertNotEmpty($product->name);
        $this->assertNotEmpty($product->slug);
        $this->assertNotEmpty($product->description);
        $this->assertIsString($product->price);
        $this->assertIsBool($product->is_active);
        $this->assertIsInt($product->stock_quantity ?? 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'name',
            'slug',
            'description',
            'price',
            'image',
            'is_active',
            'stock_quantity',
            'category_id',
            'brand_id',
            'store_id',
        ];

        $this->assertEquals($fillable, (new Product)->getFillable());
    }
}
