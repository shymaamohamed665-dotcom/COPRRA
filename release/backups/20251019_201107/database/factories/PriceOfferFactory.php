<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceOffer>
 */
class PriceOfferFactory extends Factory
{
    protected $model = PriceOffer::class;

    /**
     * @return (ProductFactory|StoreFactory|scalar|string[])[]
     *
     * @psalm-return array{product_id: ProductFactory, product_sku: string, store_id: StoreFactory, price: float, currency: string, product_url: string, affiliate_url: string, in_stock: bool, stock_quantity: int, condition: 'new'|'refurbished'|'used', rating: float, reviews_count: int, image_url: string, specifications: array{brand: string, model: string, color: string, weight: string}}
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_sku' => 'SKU-'.$this->faker->unique()->numberBetween(1000, 9999),
            'store_id' => Store::factory(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => ['USD', 'EUR', 'GBP', 'SAR', 'AED'][array_rand(['USD', 'EUR', 'GBP', 'SAR', 'AED'])],
            'product_url' => $this->faker->url,
            'affiliate_url' => $this->faker->url,
            'in_stock' => $this->faker->boolean(80),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'condition' => ['new', 'used', 'refurbished'][array_rand(['new', 'used', 'refurbished'])],
            'rating' => $this->faker->randomFloat(1, 1.0, 5.0),
            'reviews_count' => $this->faker->numberBetween(0, 1000),
            'image_url' => $this->faker->imageUrl(300, 300, 'products'),
            'specifications' => [
                'brand' => 'Brand '.$this->faker->randomNumber(3),
                'model' => 'Model '.$this->faker->randomNumber(3),
                'color' => 'Color '.$this->faker->randomNumber(3),
                'weight' => $this->faker->numberBetween(100, 5000).'g',
            ],
        ];
    }
}
