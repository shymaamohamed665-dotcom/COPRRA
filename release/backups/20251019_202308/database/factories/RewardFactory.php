<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Reward;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Reward>
 */
class RewardFactory extends Factory
{
    /** @var class-string<Reward> */
    protected $model = Reward::class;

    #[\Override]
    public function definition(): array
    {
        $now = Carbon::now();

        return [
            'name' => $this->faker->randomElement(['Discount Coupon', 'Free Shipping', 'Bonus Points']),
            'description' => $this->faker->sentence(6),
            'points_required' => $this->faker->numberBetween(10, 500),
            'type' => $this->faker->randomElement(['discount', 'free_shipping', 'gift', 'cashback']),
            'value' => [
                'percentage' => $this->faker->numberBetween(5, 20),
            ],
            'is_active' => true,
            'usage_limit' => $this->faker->numberBetween(1, 1000),
            'valid_from' => $now->copy()->subDays(1),
            'valid_until' => $now->copy()->addDays(7),
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn () => [
            'valid_until' => Carbon::now()->subDay(),
        ]);
    }

    public function future(): self
    {
        return $this->state(fn () => [
            'valid_from' => Carbon::now()->addDay(),
        ]);
    }
}
