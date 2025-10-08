<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    /** @use HasFactory<\Database\Factories\PriceHistoryFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['product_id', 'price', 'effective_date'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, PriceHistory>
     */
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
