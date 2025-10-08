<?php

declare(strict_types=1);

namespace App\Services\PriceUpdate;

use App\Models\PriceOffer;

/**
 * Service responsible for processing price updates and handling validation.
 */
final class PriceUpdateProcessorService
{
    private PriceUpdateDisplayService $displayService;

    public function __construct(PriceUpdateDisplayService $displayService)
    {
        $this->displayService = $displayService;
    }

    /**
     * Process an individual price offer.
     *
     * @param  array{updatedCount: int, errorCount: int}  $results
     * @return array{updatedCount: int, errorCount: int}
     */
    public function processIndividualOffer(PriceOffer $priceOffer, bool $dryRun, array $results): array
    {
        try {
            $result = $this->processSinglePriceOffer($priceOffer, $dryRun);
            $results['updatedCount'] += $result['updated'] ? 1 : 0;
            $results['errorCount'] += $result['error'] ? 1 : 0;
        } catch (\Exception $e) {
            $results['errorCount']++;
            $this->displayService->displayError($priceOffer, $e);
        }

        return $results;
    }

    /**
     * Process a single price offer.
     *
     * @return array{updated: bool, error: bool}
     */
    private function processSinglePriceOffer(PriceOffer $priceOffer, bool $dryRun): array
    {
        try {
            return $this->attemptPriceUpdate($priceOffer, $dryRun);
        } catch (\Exception $e) {
            return ['updated' => false, 'error' => true];
        }
    }

    /**
     * Attempt to update price if changed.
     *
     * @return array{updated: bool, error: bool}
     */
    private function attemptPriceUpdate(PriceOffer $priceOffer, bool $dryRun): array
    {
        $priceFetcher = new PriceFetcherService;
        $newPrice = $this->getNewPriceIfChanged($priceOffer, $priceFetcher);

        if ($newPrice !== null) {
            $this->handlePriceUpdate($priceOffer, $newPrice, $dryRun);

            return ['updated' => true, 'error' => false];
        }

        return ['updated' => false, 'error' => false];
    }

    /**
     * Get new price if it has changed.
     */
    private function getNewPriceIfChanged(PriceOffer $priceOffer, PriceFetcherService $priceFetcher): ?float
    {
        $newPrice = $priceFetcher->fetchPriceFromAPI($priceOffer);
        $currentPrice = is_numeric($priceOffer->price) ? (float) $priceOffer->price : 0.0;

        if ($newPrice && $newPrice !== $currentPrice) {
            return $newPrice;
        }

        return null;
    }

    /**
     * Handle price update.
     */
    private function handlePriceUpdate(PriceOffer $priceOffer, float $newPrice, bool $dryRun): void
    {
        $currentPrice = is_numeric($priceOffer->price) ? (float) $priceOffer->price : 0.0;
        $this->updatePriceOffer($priceOffer, $newPrice, $dryRun);
        $this->displayService->displayPriceUpdate($priceOffer, $currentPrice, $newPrice);
    }

    /**
     * Update the price offer.
     */
    private function updatePriceOffer(PriceOffer $priceOffer, float $newPrice, bool $dryRun): void
    {
        if (! $dryRun) {
            $priceOffer->update([
                'price' => $newPrice,
                'updated_at' => now(),
            ]);
        }
    }
}
