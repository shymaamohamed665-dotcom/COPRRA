<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookLog>
 */
class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    public function definition()
    {
        return [
            'webhook_id' => 1, // Or use a factory
            'action' => $this->faker->word,
            'message' => $this->faker->sentence,
            'metadata' => ['ip' => $this->faker->ipv4],
        ];
    }
}
