<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchDailyPriceUpdates implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        Product::query()->chunk(100, function ($products): void {
            foreach ($products as $product) {
                try {
                    // Assume Product has getCurrentPrice() implemented
                    $currentPrice = $product->getCurrentPrice();
                    PriceHistory::create([
                        'product_id' => $product->id,
                        'price' => $currentPrice,
                        'effective_date' => now(),
                    ]);
                } catch (\Throwable $e) {
                    logger()->warning('Failed to capture price for product '.$product->id.': '.$e->getMessage());
                }
            }
        });
    }
}
