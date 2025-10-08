<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="COPRRA API",
 *     version="1.0.0",
 *     description="API for COPRRA - Price Comparison Platform",
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
 *     url="https://api.coprra.com",
 *     description="Production Server"
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="apiKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-API-Key"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization"
 * )
 * @OA\Tag(
 *     name="Products",
 *     description="Product management and search"
 * )
 * @OA\Tag(
 *     name="Categories",
 *     description="Product categories"
 * )
 * @OA\Tag(
 *     name="Brands",
 *     description="Product brands"
 * )
 * @OA\Tag(
 *     name="Stores",
 *     description="Store management"
 * )
 * @OA\Tag(
 *     name="Reviews",
 *     description="Product reviews"
 * )
 * @OA\Tag(
 *     name="Wishlist",
 *     description="User wishlist management"
 * )
 * @OA\Tag(
 *     name="Price Alerts",
 *     description="Price alert management"
 * )
 * @OA\Tag(
 *     name="Statistics",
 *     description="Platform statistics and analytics"
 * )
 * @OA\Tag(
 *     name="Reports",
 *     description="Report generation"
 * )
 */
abstract class BaseApiController extends Controller
{
    protected int $perPage = 15;

    protected int $maxPerPage = 100;

    /**
     * Success response.
     *
     * @param  array<string, string|int|float|bool|array|object|null>|null  $data
     * @param  array<string, string|int|float|bool|array|object|null>  $meta
     */
    protected function successResponse(
        array|object|null $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response.
     *
     * @param  array<string, string|int|float|bool|array|null>  $meta
     *
     * @psalm-param array<string, string|int|float|bool|array|null>|null $errors
     */
    protected function errorResponse(
        string $message = 'Error',
        int $statusCode = 400,
        ?array $errors = null,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response.
     *
     * @param  array<string, string|int|float|bool|array|null>  $errors
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Not found response.
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->errorResponse($message, 404);
    }

    /**
     * Unauthorized response.
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->errorResponse($message, 401);
    }

    /**
     * Forbidden response.
     */
    protected function forbiddenResponse(
        string $message = 'Forbidden'
    ): JsonResponse {
        return $this->errorResponse($message, 403);
    }

    /**
     * Server error response.
     */
    protected function serverErrorResponse(
        string $message = 'Internal server error'
    ): JsonResponse {
        return $this->errorResponse($message, 500);
    }

    /**
     * Get filtering parameters.
     */

    /**
     * Get search parameters.
     */

    /**
     * Get API version from request.
     */
    protected function getApiVersion(Request $request): string
    {
        return (string) $request->header('API-Version', '1.0');
    }

    /**
     * Get rate limit information.
     */
    /**
     * @return array<string, int>
     */
    protected function getRateLimitInfo(): array
    {
        return [
            'limit' => 1000,
            'remaining' => 999,
            'reset' => now()->addHour()->timestamp,
        ];
    }
}
