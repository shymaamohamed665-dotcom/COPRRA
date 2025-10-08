<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'event_type' => $this->faker->randomElement([
                AnalyticsEvent::TYPE_PRICE_COMPARISON,
                AnalyticsEvent::TYPE_PRODUCT_VIEW,
                AnalyticsEvent::TYPE_SEARCH,
                AnalyticsEvent::TYPE_STORE_CLICK,
                AnalyticsEvent::TYPE_CATEGORY_VIEW,
                AnalyticsEvent::TYPE_WISHLIST_ADD,
                AnalyticsEvent::TYPE_CART_ADD,
            ]),
            'event_name' => $this->faker->sentence(3),
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'category_id' => null,
            'store_id' => Store::factory(),
            'metadata' => $this->faker->optional()->passthrough(['query' => $this->faker->word]),
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'session_id' => $this->faker->uuid,
        ];
    }
}
