<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Http\JsonResponse;

/**
 * Service for building API responses with consistent structure
 */
class ResponseBuilderService
{
    /**
     * Build API response with common structure
     *
     * @param  array<string|int|float|bool|array|object|null>  $meta
     * @return ((array|mixed|null|object|scalar)[]|bool|int|null|object|string)[]
     *
     * @psalm-return array{success: bool, message: string, version: '2.0', timestamp: null|string, data?: array|int|object|string, meta?: array<array|null|object|scalar>}
     */
    public function buildApiResponse(
        bool $success,
        string $message,
        array|object|string|int|null $data = null,
        array $meta = []
    ): array {
        $response = [
            'success' => $success,
            'message' => $message,
            'version' => '2.0',
            'timestamp' => now()->toISOString(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return $response;
    }

    /**
     * Enhanced success response with v2 features
     *
     * @param  array<string|int|float|bool|array|object|null>  $meta
     */
    public function successResponse(
        array|object|string|int|null $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = $this->buildApiResponse(true, $message, $data, $meta);

        return response()->json($response, $statusCode);
    }

    /**
     * Enhanced error response with v2 features
     *
     * @param  array<string|int|float|bool|array|null>  $meta
     */
    public function errorResponse(
        string $message = 'Error',
        array|string|null $errors = null,
        int $statusCode = 400,
        array $meta = []
    ): JsonResponse {
        $response = $this->buildApiResponse(false, $message, null, $meta);

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Enhanced paginated response with v2 features
     *
     * @param  array<string|int|float|bool|array|null>  $meta
     */
    public function paginatedResponse(
        \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|array $data,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        $paginationService = app(PaginationService::class);
        $pagination = $paginationService->getPaginationData($data);

        $response = [
            'success' => true,
            'message' => $message,
            'data' => is_object($data) && method_exists($data, 'items') ? $data->items() : [],
            'pagination' => $pagination,
            'version' => '2.0',
            'timestamp' => now()->toISOString(),
        ];

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return response()->json($response);
    }

    /**
     * Add deprecation headers to response
     */
    public function addDeprecationHeaders(JsonResponse $response): JsonResponse
    {
        $response->headers->set('X-API-Version', '2.0');
        $response->headers->set('X-API-Deprecation-Notice', 'Some features may be deprecated in future versions');

        return $response;
    }
}
