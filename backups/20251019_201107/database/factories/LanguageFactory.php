<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    /**
     * @return (int|mixed|string|true)[]
     *
     * @psalm-return array{code: mixed, name: string, native_name: string, direction: mixed, is_active: true, sort_order: int}
     */
    #[\Override]
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['English', 'Arabic', 'French', 'German', 'Spanish', 'Italian', 'Portuguese', 'Russian', 'Japanese', 'Korean', 'Chinese', 'Hindi']);
        $nativeName = $this->faker->unique()->randomElement(['English', 'العربية', 'Français', 'Deutsch', 'Español', 'Italiano', 'Português', 'Русский', '日本語', '한국어', '中文', 'हिन्दी']);

        return [
            'code' => $this->faker->unique()->randomElement(['en', 'ar', 'fr', 'de', 'es', 'it', 'pt', 'ru', 'ja', 'ko', 'zh', 'hi']),
            'name' => (is_string($name) ? $name : '').' Language',
            'native_name' => (is_string($nativeName) ? $nativeName : '').' Native',
            'direction' => $this->faker->randomElement(['ltr', 'rtl']),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
