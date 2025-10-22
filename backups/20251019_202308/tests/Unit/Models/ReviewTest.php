<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the Review model.
 */
#[CoversClass(Review::class)]
class ReviewTest extends TestCase
{
    /**
     * Test that user relation is a BelongsTo instance.
     */
    public function test_user_relation(): void
    {
        $review = new Review;

        $relation = $review->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(User::class, $relation->getRelated()::class);
    }

    /**
     * Test that product relation is a BelongsTo instance.
     */
    public function test_product_relation(): void
    {
        $review = new Review;

        $relation = $review->product();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Product::class, $relation->getRelated()::class);
    }

    /**
     * Test getReviewTextAttribute returns content.
     */
    public function test_get_review_text_attribute(): void
    {
        $content = 'This is a review';
        $review = new Review(['content' => $content]);

        $this->assertEquals($content, $review->getReviewTextAttribute());
    }
}
