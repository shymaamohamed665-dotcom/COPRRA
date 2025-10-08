<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Language;
use App\Models\User;
use App\Models\UserLocaleSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLocaleSetting>
 */
class UserLocaleSettingFactory extends Factory
{
    protected $model = UserLocaleSetting::class;

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'language_id' => Language::factory(),
            'currency_id' => Currency::factory(),
        ];
    }
}
