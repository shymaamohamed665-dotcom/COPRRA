<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'name' => $this->faker->company.' Store',
            'slug' => $this->faker->slug(2),
            'description' => $this->faker->sentence(),
            'logo_url' => $this->faker->imageUrl(200, 200),
            'website_url' => $this->faker->url(),
            'country_code' => ['EG', 'US', 'UK', 'DE', 'FR'][array_rand(['EG', 'US', 'UK', 'DE', 'FR'])],
            'supported_countries' => array_slice(['US', 'CA', 'UK', 'DE', 'FR', 'IT', 'ES', 'AU'], 0, 3),
            'is_active' => $this->faker->boolean(80),
            'priority' => $this->faker->numberBetween(0, 100),
            'affiliate_base_url' => $this->faker->optional()->url,
            'affiliate_code' => $this->faker->optional()->lexify('AFF????'),
            'api_config' => $this->faker->optional()->passthrough(['key' => $this->faker->uuid, 'secret' => $this->faker->sha256]),
            'currency_id' => Currency::factory(),
        ];
    }
}
