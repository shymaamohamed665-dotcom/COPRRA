<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PriceUpdate\PriceQueryBuilderService;
use App\Services\PriceUpdate\PriceUpdateDisplayService;
use App\Services\PriceUpdate\PriceUpdateProcessorService;
use Illuminate\Console\Command;

/**
 * @property string $signature
 * @property string $description
 */
final class UpdatePricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'prices:update {--store= : Update prices for specific store} {--product= : Update prices for specific product} {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Update price offers from external APIs or manual sources';

    private PriceQueryBuilderService $queryBuilderService;

    private PriceUpdateProcessorService $priceProcessor;

    private PriceUpdateDisplayService $displayService;

    /**
     * Execute the console command.
     *
     * @psalm-return 0|1
     */
    public function handle(): int
    {
        $this->initializeServices();
        $this->info('🔄 Starting price update process...');

        $options = $this->getOptions();
        $dryRun = is_bool($options['dryRun']) ? $options['dryRun'] : (bool) $options['dryRun'];
        $this->displayService->displayDryRunWarning($dryRun);

        $results = $this->runPriceUpdate($options, $dryRun);

        return $results['errorCount'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Get command options.
     *
     * @return array{storeId: string|null, productId: string|null, dryRun: bool}
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            'storeId' => $this->option('store'),
            'productId' => $this->option('product'),
            'dryRun' => (bool) $this->option('dry-run'),
        ];
    }

    /**
     * Initialize service dependencies.
     */
    private function initializeServices(): void
    {
        $this->displayService = new PriceUpdateDisplayService($this);
        $this->queryBuilderService = new PriceQueryBuilderService();
        $this->priceProcessor = new PriceUpdateProcessorService($this->displayService);
    }

    /**
     * Run the price update process.
     *
     * @param  array{storeId: string|null, productId: string|null, dryRun: bool}  $options
     *
     * @return array{updatedCount: int, errorCount: int}
     */
    private function runPriceUpdate(array $options, bool $dryRun): array
    {
        $priceOffers = $this->fetchPriceOffers($options);
        $results = $this->processPriceOffers($priceOffers, $dryRun);
        $this->displayService->displayResults($priceOffers->count(), $results);

        return $results;
    }

    /**
     * Fetch price offers based on options.
     *
     * @param  array{storeId: string|null, productId: string|null, dryRun: bool}  $options
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\PriceOffer>
     */
    private function fetchPriceOffers(array $options): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->queryBuilderService->buildQuery($options);
        $priceOffers = $query->get();
        $this->displayService->displayFoundPriceOffers($priceOffers->count());

        return $priceOffers;
    }

    /**
     * Process all price offers.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\PriceOffer>  $priceOffers
     *
     * @return array{updatedCount: int, errorCount: int}
     */
    private function processPriceOffers(\Illuminate\Database\Eloquent\Collection $priceOffers, bool $dryRun): array
    {
        $results = [
            'updatedCount' => 0,
            'errorCount' => 0,
        ];

        $progressBar = $this->output->createProgressBar($priceOffers->count());
        $progressBar->start();

        foreach ($priceOffers as $priceOffer) {
            $results = $this->priceProcessor->processIndividualOffer($priceOffer, $dryRun, $results);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        return $results;
    }
}
