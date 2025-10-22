<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPoint>
 */
class UserPointFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<UserPoint>
     */
    protected $model = UserPoint::class;

    /**
     * Define the model's default state.
     *
     * @return (UserFactory|\DateTime|int|mixed|null|string)[]
     *
     * @psalm-return array{user_id: UserFactory, points: int, type: mixed, source: mixed, order_id: null, description: string, expires_at: \DateTime}
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'points' => $this->faker->numberBetween(1, 200),
            'type' => $this->faker->randomElement(['earned', 'redeemed', 'expired', 'bonus']),
            'source' => $this->faker->randomElement(['purchase', 'manual', 'reward', 'referral']),
            'order_id' => null,
            'description' => $this->faker->optional()->sentence(),
            'expires_at' => $this->faker->optional()->dateTimeBetween('+1 day', '+2 years'),
        ];
    }
}
