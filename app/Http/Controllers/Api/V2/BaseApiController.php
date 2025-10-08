<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Api\ApiInfoService;
use App\Services\Api\PaginationService;
use App\Services\Api\RequestParameterService;
use App\Services\Api\ResponseBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="COPRRA API v2",
 *     version="2.0.0",
 *     description="Enhanced API for COPRRA - Price Comparison Platform v2",
 *
 *     @OA\Contact(
 *         email="api@coprra.com",
 *         name="COPRRA API Support"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://api.coprra.com/v2",
 *     description="Production Server v2"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api/v2",
 *     description="Development Server v2"
 * )
 */
abstract class BaseApiController extends Controller
{
    protected int $perPage = 20; // Increased default per page

    protected int $maxPerPage = 200; // Increased max per page

    protected ResponseBuilderService $responseBuilder;

    protected PaginationService $paginationService;

    protected RequestParameterService $requestParameterService;

    protected ApiInfoService $apiInfoService;

    public function __construct()
    {
        $this->responseBuilder = app(ResponseBuilderService::class);
        $this->paginationService = app(PaginationService::class);
        $this->requestParameterService = app(RequestParameterService::class);
        $this->apiInfoService = app(ApiInfoService::class);
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|array<int, array|object>  $data
     * @return array<string, int|null>
     */
    protected function getPaginationData(\Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|array $data): array
    {
        return $this->paginationService->getPaginationData($data);
    }

    /**
     * Get method value if exists on object
     *
     * @deprecated Use PaginationService directly
     */
    protected function getMethodValue(array|\Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $object, string $method, string|int|bool|null $default): string|int|bool|null
    {
        return $this->paginationService->getMethodValue($object, $method, $default);
    }

    /**
     * Get pagination links
     *
     * @deprecated Use PaginationService directly
     */
    protected function getPaginationLinks(array|\Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $data, bool $isPaginator): array
    {
        return $this->paginationService->getPaginationLinks($data, $isPaginator);
    }

    /**
     * Enhanced success response with v2 features.
     */
    protected function successResponse(
        array|object|string|int|null $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return $this->responseBuilder->successResponse($data, $message, $statusCode, $meta);
    }

    /**
     * Enhanced error response with v2 features.
     */
    protected function errorResponse(
        string $message = 'Error',
        array|string|null $errors = null,
        int $statusCode = 400,
        array $meta = []
    ): JsonResponse {
        return $this->responseBuilder->errorResponse($message, $errors, $statusCode, $meta);
    }

    /**
     * Enhanced paginated response with v2 features.
     */
    protected function paginatedResponse(
        \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|array $data,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        return $this->responseBuilder->paginatedResponse($data, $message, $meta);
    }

    /**
     * Get API version from request.
     */
    protected function getApiVersion(): string
    {
        return $this->apiInfoService->getApiVersion();
    }

    /**
     * Check API version compatibility.
     */
    protected function checkApiVersion(): bool
    {
        return $this->apiInfoService->checkApiVersion();
    }

    /**
     * Get rate limit information for v2.
     */
    protected function getRateLimitInfo(): array
    {
        return $this->requestParameterService->getRateLimitInfo();
    }

    /**
     * Enhanced filtering with v2 features.
     */
    protected function getFilteringParams(Request $request): array
    {
        return $this->requestParameterService->getFilteringParams($request);
    }

    /**
     * Get include parameters for relationships.
     */
    protected function getIncludeParams(Request $request): array
    {
        return $this->requestParameterService->getIncludeParams($request);
    }

    /**
     * Get fields parameter for field selection.
     */
    protected function getFieldsParams(Request $request): array
    {
        return $this->requestParameterService->getFieldsParams($request);
    }

    /**
     * Enhanced search with v2 features.
     */
    protected function getSearchParams(Request $request): array
    {
        return $this->requestParameterService->getSearchParams($request);
    }

    /**
     * Get sorting parameters with v2 enhancements.
     */
    protected function getSortingParams(Request $request): array
    {
        return $this->requestParameterService->getSortingParams($request);
    }

    /**
     * Enhanced API documentation URL for v2.
     */
    protected function getApiDocumentationUrl(): string
    {
        return $this->apiInfoService->getApiDocumentationUrl();
    }

    /**
     * Get API changelog URL for v2.
     */
    protected function getApiChangelogUrl(): string
    {
        return $this->apiInfoService->getApiChangelogUrl();
    }

    /**
     * Get API migration guide URL.
     */
    protected function getApiMigrationGuideUrl(): string
    {
        return $this->apiInfoService->getApiMigrationGuideUrl();
    }

    /**
     * Get API deprecation notices.
     */
    protected function getApiDeprecationNotices(): array
    {
        return $this->apiInfoService->getApiDeprecationNotices();
    }

    /**
     * Add deprecation headers to response.
     */
    protected function addDeprecationHeaders(JsonResponse $response): JsonResponse
    {
        return $this->responseBuilder->addDeprecationHeaders($response);
    }

    /**
     * Enhanced logging for v2.
     */
    protected function logApiRequest(Request $request): void
    {
        $this->apiInfoService->logApiRequest($request);
    }
}
