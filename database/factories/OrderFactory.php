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
     * @return (UserFactory|\DateTime|float|mixed|string|string[])[]
     *
     * @psalm-return array{order_number: string, user_id: UserFactory, status: mixed, total_amount: float, subtotal: float, tax_amount: float, shipping_amount: float, discount_amount: float, currency: 'USD', shipping_address: array{street: string, city: string, state: string, zip: string, country: string}, billing_address: array{street: string, city: string, state: string, zip: string, country: string}, notes: string, order_date: \DateTime, shipped_at: \DateTime, delivered_at: \DateTime}
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.$this->faker->unique()->numberBetween(100000, 999999),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(OrderStatus::cases()),
            // Initialize monetary values deterministically; recalculated upon item changes
            'total_amount' => 0.00,
            'subtotal' => 0.00,
            'tax_amount' => 51.00,
            'shipping_amount' => 0.00,
            'discount_amount' => 0.00,
            'currency' => 'USD',
            'shipping_address' => [
                'street' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->word,
                'zip' => $this->faker->postcode,
                'country' => $this->faker->country,
            ],
            'billing_address' => [
                'street' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->word,
                'zip' => $this->faker->postcode,
                'country' => $this->faker->country,
            ],
            'notes' => $this->faker->optional()->sentence,
            'order_date' => $this->faker->dateTime,
            'shipped_at' => $this->faker->optional()->dateTime,
            'delivered_at' => $this->faker->optional()->dateTime,
        ];
    }
}
