<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductStore extends Pivot
{
    /**
     * @var string
     */
    protected $table = 'product_store';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'is_available' => 'boolean',
        'currency_id' => 'integer',
    ];
}
