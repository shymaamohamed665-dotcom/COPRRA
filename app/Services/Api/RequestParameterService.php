<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Http\Request;

/**
 * Service for handling API request parameters
 */
class RequestParameterService
{
    /**
     * Enhanced filtering with v2 features
     *
     * @return array<string, string>
     */
    public function getFilteringParams(Request $request): array
    {
        $filters = $request->except(['page', 'per_page', 'sort_by', 'sort_order', 'search', 'include', 'fields']);

        // Remove empty values
        $filters = array_filter($filters, static fn ($value): bool => $value !== null && $value !== '');

        // Add v2 specific filters
        if ($request->has('date_from')) {
            $filters['date_from'] = $request->get('date_from');
        }

        if ($request->has('date_to')) {
            $filters['date_to'] = $request->get('date_to');
        }

        return $filters;
    }

    /**
     * Get include parameters for relationships
     *
     * @return int[]
     *
     * @psalm-return array<string, int<0, max>>
     */
    public function getIncludeParams(Request $request): array
    {
        $include = $request->get('include', '');

        if ($include === null || $include === '') {
            return [];
        }

        $params = is_string($include) ? explode(',', $include) : [];

        return array_flip($params);
    }

    /**
     * Get fields parameter for field selection
     *
     * @return int[]
     *
     * @psalm-return array<string, int<0, max>>
     */
    public function getFieldsParams(Request $request): array
    {
        $fields = $request->get('fields', '');

        if ($fields === null || $fields === '') {
            return [];
        }

        $params = is_string($fields) ? explode(',', $fields) : [];

        return array_flip($params);
    }

    /**
     * Enhanced search with v2 features
     *
     * @return array<string, string|array>
     */
    public function getSearchParams(Request $request): array
    {
        $search = $request->get('search');
        $searchFields = $request->get('search_fields', []);
        $searchMode = $request->get('search_mode', 'contains'); // contains, exact, starts_with, ends_with

        return [
            'search' => $search,
            'search_fields' => $searchFields,
            'search_mode' => $searchMode,
        ];
    }

    /**
     * Get sorting parameters with v2 enhancements
     *
     * @return array<string, string>
     */
    public function getSortingParams(Request $request): array
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $sortMode = $request->get('sort_mode', 'default'); // default, natural, custom

        // Validate sort order
        if (! in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        return [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'sort_mode' => $sortMode,
        ];
    }

    /**
     * Get rate limit information for v2
     *
     * @return (float|int|string)[]
     *
     * @psalm-return array{limit: 2000, remaining: 1999, reset: float|int|string, version: '2.0'}
     */
    public function getRateLimitInfo(): array
    {
        return [
            'limit' => 2000, // Increased limit for v2
            'remaining' => 1999,
            'reset' => now()->addHour()->timestamp,
            'version' => '2.0',
        ];
    }
}
