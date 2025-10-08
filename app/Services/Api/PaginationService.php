<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service for handling pagination data and links
 */
class PaginationService
{
    /**
     * Get pagination data
     *
     * @param  LengthAwarePaginator|Collection|array<int, array|object>  $data
     * @return array<string, int|null>
     */
    public function getPaginationData(LengthAwarePaginator|Collection|array $data): array
    {
        $isPaginator = is_object($data);

        $result = [
            'current_page' => $this->getMethodValue($data, 'currentPage', 1),
            'per_page' => $this->getMethodValue($data, 'perPage', 15),
            'total' => $this->getMethodValue($data, 'total', 0),
            'last_page' => $this->getMethodValue($data, 'lastPage', 1),
            'from' => $this->getMethodValue($data, 'firstItem', null),
            'to' => $this->getMethodValue($data, 'lastItem', null),
            'has_more_pages' => $this->getMethodValue($data, 'hasMorePages', false),
        ];

        $result['links'] = $this->getPaginationLinks($data, $isPaginator);

        return $result;
    }

    /**
     * Get method value if exists on object
     */
    protected function getMethodValue(array|LengthAwarePaginator|Collection $object, string $method, string|int|bool|null $default): string|int|bool|null
    {
        return is_object($object) && method_exists($object, $method) ? $object->$method() : $default;
    }

    /**
     * Get pagination links
     *
     * @return array<string, string|null>
     */
    protected function getPaginationLinks(array|LengthAwarePaginator|Collection $data, bool $isPaginator): array
    {
        if (! $isPaginator) {
            return [
                'first' => null,
                'last' => null,
                'prev' => null,
                'next' => null,
            ];
        }

        return [
            'first' => $this->getMethodValue($data, 'url', null) ? $data->url(1) : null,
            'last' => $this->getMethodValue($data, 'url', null) && $this->getMethodValue($data, 'lastPage', null) ? $data->url($data->lastPage()) : null,
            'prev' => $this->getMethodValue($data, 'previousPageUrl', null),
            'next' => $this->getMethodValue($data, 'nextPageUrl', null),
        ];
    }
}
