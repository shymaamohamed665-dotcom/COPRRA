<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $from_currency
 * @property string $to_currency
 * @property float $rate
 * @property string $source
 * @property Carbon|null $fetched_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ExchangeRate extends Model
{
    /** @use HasFactory<\Database\Factories\ExchangeRateFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'source',
        'fetched_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate' => 'decimal:10',
        'fetched_at' => 'datetime',
    ];

    /**
     * Get exchange rate for a currency pair.
     */
    public static function getRate(string $fromCurrency, string $toCurrency): ?float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $rate = self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->latest('updated_at')
            ->first();

        return $rate ? (float) $rate->rate : null;
    }

    /**
     * Check if the exchange rate is stale (older than 24 hours).
     */
    public function isStale(): bool
    {
        return $this->fetched_at === null || $this->fetched_at->addHours(24)->isPast();
    }

    /**
     * Scope for fresh exchange rates (not stale).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ExchangeRate>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ExchangeRate>
     */
    public function scopeFresh(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q): void {
            $q->whereNull('fetched_at')
                ->orWhere('fetched_at', '>', now()->subHours(24));
        });
    }

    /**
     * Scope for stale exchange rates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ExchangeRate>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ExchangeRate>
     */
    public function scopeStale(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('fetched_at', '<=', now()->subHours(24));
    }
}
