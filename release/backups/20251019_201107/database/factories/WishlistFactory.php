<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wishlist>
 */
class WishlistFactory extends Factory
{
    protected $model = Wishlist::class;

    /**
     * @return (ProductFactory|UserFactory|string)[]
     *
     * @psalm-return array{user_id: UserFactory, product_id: ProductFactory, notes: string}
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'notes' => $this->faker->sentence(),
        ];
    }
}
