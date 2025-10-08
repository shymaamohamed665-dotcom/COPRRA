<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PriceHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceHistory>
 */
class PriceHistoryFactory extends Factory
{
    protected $model = PriceHistory::class;

    public function definition()
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'effective_date' => $this->faker->dateTimeThisYear(),
        ];
    }
}
