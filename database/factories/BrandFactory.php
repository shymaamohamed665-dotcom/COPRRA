<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * @return (string|true)[]
     *
     * @psalm-return array{name: string, slug: string, description: string, logo_url: string, website_url: string, is_active: true}
     */
    #[\Override]
    public function definition(): array
    {
        $name = $this->faker->company.' Brand';

        return [
            'name' => $name,
            'slug' => $this->faker->unique()->slug(2).'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'description' => $this->faker->sentence(),
            'logo_url' => $this->faker->imageUrl(200, 200),
            'website_url' => $this->faker->url(),
            'is_active' => true,
        ];
    }
}
