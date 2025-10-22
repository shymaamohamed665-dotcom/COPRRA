<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_a_review(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'title' => 'Great Product',
            'content' => 'I really liked this product.',
            'rating' => 5,
            'is_verified_purchase' => true,
            'is_approved' => true,
            'helpful_count' => 10,
        ]);

        $this->assertInstanceOf(Review::class, $review);
        $this->assertEquals('Great Product', $review->title);
        $this->assertEquals('I really liked this product.', $review->content);
        $this->assertEquals(5, $review->rating);
        $this->assertTrue($review->is_verified_purchase);
        $this->assertTrue($review->is_approved);
        $this->assertEquals(10, $review->helpful_count);
        $this->assertIsArray($review->helpful_votes);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'title' => 'Great Product',
            'content' => 'I really liked this product.',
            'rating' => 5,
            'is_verified_purchase' => true,
            'is_approved' => true,
            'helpful_count' => 10,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_casts_attributes_correctly(): void
    {
        $review = Review::factory()->create([
            'rating' => '5',
            'is_verified_purchase' => 1,
            'is_approved' => 0,
            'helpful_count' => '10',
            'helpful_votes' => ['user1', 'user2'],
        ]);

        $this->assertIsInt($review->rating);
        $this->assertIsBool($review->is_verified_purchase);
        $this->assertIsBool($review->is_approved);
        $this->assertIsInt($review->helpful_count);
        $this->assertIsArray($review->helpful_votes);
        $this->assertEquals(5, $review->rating);
        $this->assertTrue($review->is_verified_purchase);
        $this->assertFalse($review->is_approved);
        $this->assertEquals(10, $review->helpful_count);
        $this->assertEquals(['user1', 'user2'], $review->helpful_votes);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($user->id, $review->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_product(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $review->product);
        $this->assertEquals($product->id, $review->product->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_review_text_attribute(): void
    {
        $content = 'This is the review content.';
        $review = Review::factory()->create(['content' => $content]);

        $this->assertEquals($content, $review->getReviewTextAttribute());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'user_id',
            'product_id',
            'title',
            'content',
            'rating',
            'is_verified_purchase',
            'is_approved',
            'helpful_votes',
            'helpful_count',
        ];

        $this->assertEquals($fillable, (new Review)->getFillable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_factory_creates_valid_review(): void
    {
        $review = Review::factory()->make();

        $this->assertInstanceOf(Review::class, $review);
        $this->assertNotEmpty($review->title);
        $this->assertNotEmpty($review->content);
        $this->assertIsInt($review->rating);
        $this->assertIsBool($review->is_verified_purchase);
        $this->assertIsBool($review->is_approved);
        $this->assertIsArray($review->helpful_votes);
        $this->assertIsInt($review->helpful_count);
    }
}
