<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    public function updated(Product $product): void
    {
        Cache::tags(['products', 'product:'.$product->id])->flush();
    }

    public function deleted(Product $product): void
    {
        Cache::tags(['products', 'product:'.$product->id])->flush();
    }
}
