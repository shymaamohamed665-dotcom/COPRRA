<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Webhook>
     */
    protected $model = Webhook::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'store_identifier' => fake()->randomElement(['amazon', 'ebay', 'noon']),
            'event_type' => fake()->randomElement([
                Webhook::EVENT_PRICE_UPDATE,
                Webhook::EVENT_STOCK_UPDATE,
                Webhook::EVENT_PRODUCT_UPDATE,
            ]),
            'product_identifier' => fake()->regexify('[A-Z0-9]{10}'),
            'product_id' => null,
            'payload' => [
                'price' => fake()->randomFloat(2, 10, 1000),
                'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'SAR', 'AED']),
                'in_stock' => fake()->boolean(80),
            ],
            'signature' => fake()->sha256(),
            'status' => fake()->randomElement([
                Webhook::STATUS_PENDING,
                Webhook::STATUS_PROCESSING,
                Webhook::STATUS_COMPLETED,
                Webhook::STATUS_FAILED,
            ]),
            'error_message' => null,
            'processed_at' => null,
        ];
    }
}
