<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

/**
 * @property string $signature
 * @property string $description
 */
class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rates:update
                        {--provider= : Exchange rate provider (ecb, fixer, exchangerate-api)}
                        {--base=EUR : Base currency for rates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update exchange rates from external API';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateService $service): int
    {
        $this->info('🔄 Updating exchange rates...');

        if ($this->option('seed')) {
            $this->info('📦 Seeding initial rates from config...');
            $count = $service->seedFromConfig();
            $this->info("✅ Seeded {$count} exchange rates from config");

            return self::SUCCESS;
        }

        $startTime = microtime(true);

        $count = $service->fetchAndStoreRates();

        $duration = round(microtime(true) - $startTime, 2);

        if ($count > 0) {
            $this->info("✅ Successfully updated {$count} exchange rates in {$duration}s");

            return self::SUCCESS;
        }

        $this->warn('⚠️  No exchange rates were updated');

        return self::FAILURE;
    }
}
