<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * @return (array|bool|float|int|string)[]
     *
     * @psalm-return array{code: string, name: array|string, symbol: string, is_active: true, is_default: false, exchange_rate: float, decimal_places: 2}
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('???'),
            'name' => $this->faker->unique()->words(2, true),
            'symbol' => ['$', '€', '£', '¥', '₹'][array_rand(['$', '€', '£', '¥', '₹'])],
            'is_active' => true,
            'is_default' => false,
            'exchange_rate' => $this->faker->randomFloat(4, 0.1, 10.0),
            'decimal_places' => 2,
        ];
    }
}
