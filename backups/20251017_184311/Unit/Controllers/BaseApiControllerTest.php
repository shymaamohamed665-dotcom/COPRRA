<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Api\V2\BaseApiController;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ConcreteBaseApiController extends BaseApiController
{
    public function testMethod(): JsonResponse
    {
        return $this->successResponse(['test' => 'data']);
    }

    public function successResponsePublic(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return $this->successResponse($data, $message, $statusCode, $meta);
    }

    public function errorResponsePublic(
        string $message = 'Error',
        int $statusCode = 400,
        mixed $errors = null,
        array $meta = []
    ): JsonResponse {
        return $this->errorResponse($message, $statusCode, $errors, $meta);
    }

    public function paginatedResponsePublic(
        mixed $data,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        return $this->paginatedResponse($data, $message, $meta);
    }

    public function getRateLimitInfoPublic(): array
    {
        return $this->getRateLimitInfo();
    }
}

class BaseApiControllerTest extends TestCase
{
    private ConcreteBaseApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ConcreteBaseApiController;
    }

    /**
     * Test successResponse method returns correct JsonResponse.
     */
    public function test_success_response(): void
    {
        // Arrange
        $data = ['key' => 'value'];
        $message = 'Success message';
        $statusCode = 200;

        // Act
        $response = $this->controller->successResponsePublic($data, $message, $statusCode);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($statusCode, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($data, $responseData['data']);
        $this->assertEquals($message, $responseData['message']);
        $this->assertEquals('2.0', $responseData['version']);
        $this->assertArrayHasKey('timestamp', $responseData);
    }

    /**
     * Test errorResponse method returns correct JsonResponse.
     */
    public function test_error_response(): void
    {
        // Arrange
        $message = 'Error message';
        $statusCode = 400;
        $errors = ['field' => 'error'];

        // Act
        $response = $this->controller->errorResponsePublic($message, $statusCode, $errors);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($statusCode, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals($message, $responseData['message']);
        $this->assertEquals($errors, $responseData['errors']);
        $this->assertEquals('2.0', $responseData['version']);
        $this->assertArrayHasKey('timestamp', $responseData);
    }

    /**
     * Test paginatedResponse method returns correct JsonResponse.
     */
    public function test_paginated_response(): void
    {
        // Arrange
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            [['id' => 1], ['id' => 2]],
            2,
            15,
            1
        );
        $message = 'Paginated data';

        // Act
        $response = $this->controller->paginatedResponsePublic($paginator, $message);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals([['id' => 1], ['id' => 2]], $responseData['data']);
        $this->assertEquals($message, $responseData['message']);
        $this->assertEquals('2.0', $responseData['version']);
        $this->assertArrayHasKey('timestamp', $responseData);
        $this->assertArrayHasKey('pagination', $responseData);
    }

    /**
     * Test getRateLimitInfo method returns correct data.
     */
    public function test_get_rate_limit_info(): void
    {
        // Act
        $rateLimit = $this->controller->getRateLimitInfoPublic();

        // Assert
        $this->assertIsArray($rateLimit);
        $this->assertArrayHasKey('limit', $rateLimit);
        $this->assertArrayHasKey('remaining', $rateLimit);
        $this->assertArrayHasKey('reset', $rateLimit);
        $this->assertArrayHasKey('version', $rateLimit);
        $this->assertEquals(2000, $rateLimit['limit']);
        $this->assertEquals('2.0', $rateLimit['version']);
    }
}
