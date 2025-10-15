<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return (App\Enums\UserRole::USER|\Illuminate\Support\Carbon|bool|null|string)[]
     *
     * @psalm-return array{name: string, email: string, email_verified_at: \Illuminate\Support\Carbon, password: string, phone: null, is_admin: false, is_active: true, is_blocked: false, role: App\Enums\UserRole::USER}
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            // Keep phone null by default to avoid violating strict SQLite CHECK constraint in tests
            // Tests that need a phone value set it explicitly (e.g. '+1234567890')
            'phone' => null,
            'is_admin' => false,
            'is_active' => true,
            'is_blocked' => false,
            'role' => UserRole::USER,
        ];
    }
}
