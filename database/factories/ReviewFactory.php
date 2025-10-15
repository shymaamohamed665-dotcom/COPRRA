<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * @return (ProductFactory|UserFactory|array|bool|int|string)[]
     *
     * @psalm-return array{user_id: UserFactory, product_id: ProductFactory, title: string, content: string, rating: int, is_verified_purchase: bool, is_approved: true, helpful_votes: array<never, never>, helpful_count: int}
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'title' => $this->faker->unique()->sentence(),
            'content' => $this->faker->paragraph(),
            'rating' => $this->faker->numberBetween(1, 5),
            'is_verified_purchase' => $this->faker->boolean(30),
            'is_approved' => true,
            'helpful_votes' => [],
            'helpful_count' => $this->faker->numberBetween(0, 50),
        ];
    }
}
