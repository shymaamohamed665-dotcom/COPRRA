<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        $faker = $this->faker;

        $words = $faker->words(3, true);

        return [
            'name' => (is_string($words) ? $words : '').' Product',
            'slug' => $faker->slug(3),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'image' => $faker->imageUrl(400, 400),
            'brand_id' => 1,
            'category_id' => 1,
            'store_id' => 1,
            'is_active' => true,
        ];
    }
}
