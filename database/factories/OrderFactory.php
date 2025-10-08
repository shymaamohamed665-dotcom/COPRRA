<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Order>
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.$this->faker->unique()->numberBetween(100000, 999999),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(OrderStatus::cases()),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'subtotal' => $this->faker->randomFloat(2, 10, 1000),
            'tax_amount' => $this->faker->randomFloat(2, 0, 100),
            'shipping_amount' => $this->faker->randomFloat(2, 0, 50),
            'discount_amount' => $this->faker->randomFloat(2, 0, 100),
            'currency' => 'USD',
            'shipping_address' => json_encode([
                'street' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->word,
                'zip' => $this->faker->postcode,
                'country' => $this->faker->country,
            ]),
            'billing_address' => json_encode([
                'street' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->word,
                'zip' => $this->faker->postcode,
                'country' => $this->faker->country,
            ]),
            'notes' => $this->faker->optional()->sentence,
            'shipped_at' => $this->faker->optional()->dateTime,
            'delivered_at' => $this->faker->optional()->dateTime,
        ];
    }
}
