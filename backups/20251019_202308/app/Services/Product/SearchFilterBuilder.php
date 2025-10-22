<?php

declare(strict_types=1);

namespace App\Services\Product;

use Illuminate\Http\Request;

final class SearchFilterBuilder
{
    /**
     * @return array<string, string|int|float>
     */
    public function buildFromRequest(Request $request): array
    {
        $filters = $request->only(['category', 'brand', 'sort', 'order']);
        $filters = is_array($filters) ? $filters : [];

        $this->buildSortBy($filters);
        $this->buildCategory($filters);
        $this->buildBrand($filters);

        return $filters;
    }

    /**
     * @param  array<string, string|int|float>  $filters
     */
    private function buildSortBy(array &$filters): void
    {
        if (isset($filters['sort']) && isset($filters['order'])) {
            $sort = is_string($filters['sort']) ? $filters['sort'] : '';
            $order = is_string($filters['order']) ? $filters['order'] : '';
            $filters['sort_by'] = $sort.'_'.$order;
            unset($filters['sort'], $filters['order']);
        }
    }

    /**
     * @param  array<string, string|int|float>  $filters
     */
    private function buildCategory(array &$filters): void
    {
        if (isset($filters['category'])) {
            $filters['category_id'] = $filters['category'];
            unset($filters['category']);
        }
    }

    /**
     * @param  array<string, string|int|float>  $filters
     */
    private function buildBrand(array &$filters): void
    {
        if (isset($filters['brand'])) {
            $filters['brand_id'] = $filters['brand'];
            unset($filters['brand']);
        }
    }
}
