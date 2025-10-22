<?php

declare(strict_types=1);

namespace App\Services\PriceUpdate;

use App\Models\PriceOffer;
use Illuminate\Console\Command;

/**
 * Service responsible for displaying price update information and results.
 */
final readonly class PriceUpdateDisplayService
{
    private Command $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Display dry run warning.
     */
    public function displayDryRunWarning(bool $dryRun): void
    {
        if ($dryRun) {
            $this->command->warn('🧪 Running in dry-run mode - no changes will be made');
        }
    }

    /**
     * Display price update message.
     */
    public function displayPriceUpdate(PriceOffer $priceOffer, float $currentPrice, float $newPrice): void
    {
        $productName = is_string($priceOffer->product->name) ? $priceOffer->product->name : 'Unknown Product';
        $storeName = is_string($priceOffer->store->name) ? $priceOffer->store->name : 'Unknown Store';
        $this->command->line("\n💰 Updated {$productName} at {$storeName}: {$currentPrice} → {$newPrice}");
    }

    /**
     * Display error message.
     */
    public function displayError(PriceOffer $priceOffer, \Exception $e): void
    {
        $product = $priceOffer->product;
        $store = $priceOffer->store;
        $productName = $product && is_string($product->name) ? $product->name : 'Unknown Product';
        $storeName = $store && is_string($store->name) ? $store->name : 'Unknown Store';
        $this->command->error("\n❌ Error updating ".$productName.' at '.$storeName.': '.$e->getMessage());
    }

    /**
     * Display final results.
     *
     * @param  array{updatedCount: int, errorCount: int}  $results
     */
    public function displayResults(int $totalCount, array $results): void
    {
        $this->command->info('✅ Price update completed!');
        $this->displaySummaryTable($totalCount, $results);
    }

    /**
     * Display found price offers count.
     */
    public function displayFoundPriceOffers(int $count): void
    {
        $this->command->info("📊 Found {$count} price offers to process");
    }

    /**
     * Log the applied filter.
     */
    public function logAppliedFilter(string $relation, int $id): void
    {
        $filterIcons = [
            'store' => '🏪',
            'product' => '📦',
        ];

        $icon = $filterIcons[$relation] ?? '';
        $this->command->info("{$icon} Filtering by {$relation} ID: {$id}");
    }

    /**
     * Display the summary table.
     *
     * @param  array{updatedCount: int, errorCount: int}  $results
     */
    private function displaySummaryTable(int $totalCount, array $results): void
    {
        $this->command->table(['Metric', 'Count'], [
            ['Total processed', $totalCount],
            ['Updated', $results['updatedCount']],
            ['Errors', $results['errorCount']],
            ['Unchanged', $totalCount - $results['updatedCount'] - $results['errorCount']],
        ]);
    }
}
