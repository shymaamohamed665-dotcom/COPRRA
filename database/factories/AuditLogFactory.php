<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'event' => $this->faker->randomElement(['created', 'updated', 'deleted', 'viewed']),
            'auditable_type' => Product::class,
            'auditable_id' => 1,
            'user_id' => 1,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'old_values' => $this->faker->optional()->randomElements([
                'name' => $this->faker->sentence(),
                'price' => $this->faker->randomFloat(2, 10, 1000),
                'description' => $this->faker->paragraph(),
            ]),
            'new_values' => $this->faker->optional()->randomElements([
                'name' => $this->faker->sentence(),
                'price' => $this->faker->randomFloat(2, 10, 1000),
                'description' => $this->faker->paragraph(),
            ]),
            'metadata' => $this->faker->optional()->randomElements([
                'source' => $this->faker->randomElement(['web', 'api', 'admin']),
                'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari']),
                'device' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            ]),
            'url' => $this->faker->optional()->url(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
        ];
    }
}
