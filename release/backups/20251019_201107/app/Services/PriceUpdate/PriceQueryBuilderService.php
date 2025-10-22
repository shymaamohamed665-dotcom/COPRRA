<?php

declare(strict_types=1);

namespace App\Services\PriceUpdate;

use App\Models\PriceOffer;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service responsible for building and filtering price offer queries.
 */
final class PriceQueryBuilderService
{
    /**
     * Build the query for price offers.
     *
     * @param  array{storeId: int|string|null, productId: int|string|null, dryRun: bool}  $options
     *
     * @return Builder<PriceOffer>
     */
    public function buildQuery(array $options): Builder
    {
        $query = PriceOffer::with(['store', 'product']);

        $this->applyFilters($query, [
            'store' => $options['storeId'],
            'product' => $options['productId'],
        ]);

        return $query;
    }

    /**
     * Apply multiple filters to query.
     *
     * @param  Builder<PriceOffer>  $query
     * @param  array<string, int|string|null>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $relation => $relationId) {
            $this->applyFilter($query, $relation, $relationId);
        }
    }

    /**
     * Apply a single filter to the query.
     *
     * @param  Builder<PriceOffer>  $query
     */
    private function applyFilter(Builder $query, string $relation, int|string|null $relationId): void
    {
        if ($relationId === null) {
            return;
        }

        $numericId = $this->parseNumericId($relationId);
        if ($numericId === null) {
            return;
        }

        $query->where($relation.'_id', $numericId);
    }

    /**
     * Parse a numeric ID from int or string.
     */
    private function parseNumericId(int|string $rawId): ?int
    {
        return is_int($rawId) ? $rawId : (is_numeric($rawId) ? (int) $rawId : null);
    }
}
