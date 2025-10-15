<?php

declare(strict_types=1);

namespace App\Services\Product\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling product price updates
 */
final class ProductPriceService
{
    /**
     * Update product price with validation, locking, and logging
     *
     * @throws \RuntimeException
     */
    public function updatePrice(Product $product, float $newPrice): bool
    {
        try {
            return DB::transaction(/**
             * @return true
             */
                function () use ($product, $newPrice): bool {
                    $oldPrice = $product->price;

                    // Lock the row for update
                    $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

                    $updated = $lockedProduct->update(['price' => $newPrice]);

                    if (! $updated) {
                        throw new \RuntimeException('Failed to update product price');
                    }

                    $userId = auth()->id();

                    // Log price change
                    Log::info('Product price updated', [
                        'product_id' => $lockedProduct->id,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                        'user_id' => $userId,
                        'ip' => request()->ip(),
                    ]);

                    // Create price history record (if priceHistory method exists)
                    $this->createPriceHistory($lockedProduct, $oldPrice, $newPrice, $userId);

                    return true;
                });
        } catch (\Exception $e) {
            Log::error('Price update failed', [
                'product_id' => $product->id,
                'price' => $newPrice,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create price history record
     */
    private function createPriceHistory(Product $product, float $oldPrice, float $newPrice, ?int $userId): void
    {
        if (! method_exists($product, 'priceHistory')) {
            return;
        }

        $priceHistory = $product->priceHistory();
        if (is_object($priceHistory) && method_exists($priceHistory, 'create')) {
            $priceHistory->create([
                'price' => $newPrice,
                'effective_date' => now(),
            ]);
        }
    }
}
