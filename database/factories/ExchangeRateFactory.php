<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition()
    {
        return [
            'from_currency' => $this->faker->currencyCode,
            'to_currency' => $this->faker->currencyCode,
            'rate' => $this->faker->randomFloat(4, 0.5, 1.5),
            'source' => 'test',
            'fetched_at' => now(),
        ];
    }
}
