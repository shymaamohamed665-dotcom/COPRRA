<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\ExchangeRateService;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ExchangeRateService $service): void
    {
        $this->command->info('🔄 Seeding exchange rates from config...');

        $count = $service->seedFromConfig();

        $this->command->info("✅ Seeded {$count} exchange rates");
    }
}
