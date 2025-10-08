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
     * @param  array{storeId: string|null, productId: string|null, dryRun: bool}  $options
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
     * @param  array<string, string|null>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $relation => $id) {
            $this->applyFilter($query, $relation, $id);
        }
    }

    /**
     * Apply a single filter to the query.
     *
     * @param  Builder<PriceOffer>  $query
     */
    private function applyFilter(Builder $query, string $relation, ?string $id): void
    {
        if ($id === null) {
            return;
        }

        $numericId = $this->parseNumericId($id);
        if ($numericId === null) {
            return;
        }

        $query->where($relation.'_id', $numericId);
    }

    /**
     * Parse a numeric ID from a string.
     */
    private function parseNumericId(string $id): ?int
    {
        return is_numeric($id) ? (int) $id : null;
    }
}
