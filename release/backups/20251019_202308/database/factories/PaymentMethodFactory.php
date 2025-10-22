<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /** @var class-string<PaymentMethod> */
    protected $model = PaymentMethod::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Credit Card', 'PayPal', 'Bank Transfer']),
            'gateway' => $this->faker->randomElement(['stripe', 'paypal', 'manual']),
            'type' => $this->faker->randomElement(['card', 'wallet', 'bank']),
            'config' => [
                'api_key' => $this->faker->sha256(),
                'webhook_secret' => $this->faker->sha256(),
            ],
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function default(): self
    {
        return $this->state(fn () => [
            'is_default' => true,
        ]);
    }
}
