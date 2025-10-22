<?php

declare(strict_types=1);

namespace App\Services\Product\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Service for validating product-related data
 */
final class ProductValidationService
{
    /**
     * Validate search query and filters
     *
     * @param  array<string, string|int|float>  $filters
     *
     * @return array<array<float|int|string>|int|string>
     *
     * @psalm-return array{query: string, filters: array<string, float|int|string>, perPage: int<1, 50>}
     *
     * @throws ValidationException
     * @throws InvalidArgumentException
     */
    public function validateSearchParameters(string $query, array $filters, int $perPage): array
    {
        // Validate and sanitize query
        $sanitizedQuery = strip_tags($query);

        // Validate perPage
        $validatedPerPage = min(50, max(1, $perPage));

        // Validate filters
        $validator = Validator::make($filters, [
            'category_id' => 'sometimes|integer|exists:categories,id',
            'brand_id' => 'sometimes|integer|exists:brands,id',
            'min_price' => 'sometimes|numeric|min:0',
            'max_price' => 'sometimes|numeric|min:0|gte:min_price',
            'sort_by' => 'sometimes|in:price_asc,price_desc,name_asc,name_desc,latest',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return [
            'query' => $sanitizedQuery,
            'filters' => $this->sanitizeFilters($filters),
            'perPage' => $validatedPerPage,
        ];
    }

    /**
     * Validate slug format
     *
     * @throws InvalidArgumentException
     */
    public function validateSlug(string $slug): void
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException('Invalid slug format');
        }
    }

    /**
     * Validate related products limit
     *
     * @throws InvalidArgumentException
     */
    public function validateRelatedLimit(int $limit): void
    {
        if ($limit < 1 || $limit > 20) {
            throw new InvalidArgumentException('Limit must be between 1 and 20');
        }
    }

    /**
     * Validate product price
     *
     * @throws ValidationException
     */
    public function validatePrice(float $price): float
    {
        $validator = Validator::make(
            ['price' => $price],
            ['price' => 'required|numeric|min:0']
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return round($price, 2);
    }

    /**
     * Sanitize filter values
     *
     * @param  array<string, string|int|float>  $filters
     *
     * @return array<float|int|string>
     *
     * @psalm-return array<string, float|int|string>
     */
    private function sanitizeFilters(array $filters): array
    {
        $sanitized = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $sanitized[$key] = match ($key) {
                'category_id', 'brand_id' => is_numeric($value) ? (int) $value : null,
                'min_price', 'max_price' => is_numeric($value) ? (float) $value : null,
                'sort_by' => is_string($value) ? $value : null,
                default => $value,
            };
        }

        return array_filter($sanitized, fn ($value): bool => $value !== null);
    }
}
