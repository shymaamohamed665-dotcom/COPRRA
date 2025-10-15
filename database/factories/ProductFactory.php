<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return (BrandFactory|CategoryFactory|float|int|null|string|true)[]
     *
     * @psalm-return array{name: string, slug: string, description: string, price: float, image: string, brand_id: BrandFactory, category_id: CategoryFactory, store_id: null, is_active: true, stock_quantity: int}
     */
    #[\Override]
    public function definition(): array
    {
        $faker = $this->faker;

        $words = $faker->words(3, true);

        return [
            'name' => (is_string($words) ? $words : '').' Product',
            'slug' => $faker->unique()->slug(3).'-'.$faker->unique()->numberBetween(1000, 9999),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'image' => $faker->imageUrl(400, 400),
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'store_id' => null,
            'is_active' => true,
            'stock_quantity' => $faker->numberBetween(0, 100),
        ];
    }
}
