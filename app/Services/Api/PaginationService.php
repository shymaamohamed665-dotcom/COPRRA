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
     * @return ((null|string)[]|bool|int|null|string)[]
     *
     * @psalm-return array{current_page: bool|int|null|string, per_page: bool|int|null|string, total: bool|int|null|string, last_page: bool|int|null|string, from: bool|int|null|string, to: bool|int|null|string, has_more_pages: bool|int|null|string, links: array<string, null|string>}
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
     * @return (bool|int|mixed|null|string)[]
     *
     * @psalm-return array{first: mixed|null|string, last: mixed|null|string, prev: bool|int|null|string, next: bool|int|null|string}
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

        $hasUrlMethod = is_object($data) && method_exists($data, 'url');
        $lastPage = $this->getMethodValue($data, 'lastPage', null);

        return [
            'first' => $hasUrlMethod ? $data->url(1) : null,
            'last' => $hasUrlMethod && $lastPage ? $data->url((int) $lastPage) : null,
            'prev' => $this->getMethodValue($data, 'previousPageUrl', null),
            'next' => $this->getMethodValue($data, 'nextPageUrl', null),
        ];
    }
}
