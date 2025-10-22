<?php

declare(strict_types=1);

namespace App\Services\Validators;

use Illuminate\Support\Facades\Log;

class PriceChangeValidator
{
    public function validate(\App\Models\Product $product, float|int|string $newPrice, int $threshold = 50): void
    {
        $oldPrice = $product instanceof \App\Models\Product ? $product->price : null;

        if ($oldPrice && $newPrice && is_numeric($oldPrice) && $oldPrice > 0) {
            $oldPriceFloat = (float) $oldPrice;
            $newPriceFloat = is_numeric($newPrice) ? (float) $newPrice : 0.0;

            if ($oldPriceFloat > 0) {
                $changePercentage = abs($newPriceFloat - $oldPriceFloat) / $oldPriceFloat * 100;

                if ($changePercentage > $threshold) {
                    Log::warning('Significant price change detected.', [
                        'product_id' => $product->id,
                        'old_price' => $oldPriceFloat,
                        'new_price' => $newPriceFloat,
                        'change_percentage' => round($changePercentage, 2),
                    ]);
                }
            }
        }
    }
}
