<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return (int|null|string|true)[]
     *
     * @psalm-return array{name: string, slug: string, description: string, parent_id: null, level: 0, is_active: true}
     */
    #[\Override]
    public function definition(): array
    {
        $words = $this->faker->words(2, true);

        return [
            'name' => (is_string($words) ? $words : '').' Category',
            'slug' => $this->faker->unique()->slug(2).'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'description' => $this->faker->sentence(),
            'parent_id' => null,
            'level' => 0,
            'is_active' => true,
        ];
    }
}
