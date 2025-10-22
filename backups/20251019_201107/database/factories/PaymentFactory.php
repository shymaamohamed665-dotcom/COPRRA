<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Payment>
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return (Factory|false|float|mixed|string)[]
     *
     * @psalm-return array{order_id: Factory, amount: float, currency: 'USD', status: mixed, method: mixed, transaction_id: string, gateway: mixed, metadata: false|string}
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'USD',
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'method' => $this->faker->randomElement(['credit_card', 'paypal', 'bank_transfer', 'cash']),
            'transaction_id' => $this->faker->unique()->uuid(),
            'gateway' => $this->faker->randomElement(['stripe', 'paypal', 'braintree']),
            'metadata' => json_encode(['test' => true]),
        ];
    }
}
