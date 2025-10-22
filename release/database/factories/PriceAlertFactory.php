<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceAlert>
 */
class PriceAlertFactory extends Factory
{
    protected $model = PriceAlert::class;

    /**
     * @return (UserFactory|\Closure|bool|float)[]
     *
     * @psalm-return array{user_id: UserFactory, product_id: \Closure():int, target_price: float, repeat_alert: bool, is_active: true}
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => function () {
                return Product::factory()->create()->id;
            },
            'target_price' => $this->faker->randomFloat(2, 10, 1000),
            'repeat_alert' => $this->faker->boolean(),
            'is_active' => true,
        ];
    }
}
