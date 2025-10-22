<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\AnalyticsController;
use App\Models\User;
use App\Services\BehaviorAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Analytics Controller Test Suite
 *
 * Tests the AnalyticsController functionality including:
 * - User analytics retrieval
 * - Site analytics retrieval
 * - Behavior tracking
 * - Authentication checks
 * - Error handling and exceptions
 *
 * @coversDefaultClass \App\Http\Controllers\AnalyticsController
 *
 * @uses \App\Services\BehaviorAnalysisService
 * @uses \App\Models\User
 */
class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsController $controller;

    private BehaviorAnalysisService|MockInterface $serviceMock;

    private User $user;

    private Request|LegacyMockInterface $requestMock;

    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var BehaviorAnalysisService&MockInterface */
        $this->serviceMock = Mockery::mock(BehaviorAnalysisService::class);
        $this->controller = new AnalyticsController($this->serviceMock);
        $this->user = User::factory()->create();

        /** @var Request&LegacyMockInterface $requestMock */
        $requestMock = Mockery::mock(Request::class)->makePartial();
        $this->requestMock = $requestMock;
    }

    /**
     * Clean up test environment after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that userAnalytics returns analytics for authenticated user
     *
     * @covers ::userAnalytics
     */
    public function test_user_analytics_returns_analytics_for_authenticated_user(): void
    {
        // Arrange
        $analyticsData = ['key' => 'value'];

        /** @phpstan-ignore method.nonObject */
        $this->serviceMock->shouldReceive('getUserAnalytics')
            ->with($this->user)
            ->once()
            ->andReturn($analyticsData);

        /** @phpstan-ignore method.nonObject */
        $this->requestMock->shouldReceive('user')
            ->andReturn($this->user);

        // Act
        $response = $this->controller->userAnalytics($this->requestMock);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['analytics' => $analyticsData], $response->getData(true));
    }

    /**
     * Test that userAnalytics returns unauthorized for unauthenticated user
     *
     * @covers ::userAnalytics
     */
    public function test_user_analytics_returns_unauthorized_for_unauthenticated_user(): void
    {
        // Arrange
        /** @phpstan-ignore method.nonObject */
        $this->requestMock->shouldReceive('user')
            ->andReturn(null);

        // Act
        $response = $this->controller->userAnalytics($this->requestMock);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['error' => 'Unauthorized'], $response->getData(true));
    }

    /**
     * Test that siteAnalytics returns site analytics data
     *
     * @covers ::siteAnalytics
     */
    public function test_site_analytics_returns_site_analytics(): void
    {
        // Arrange
        $analyticsData = ['site_key' => 'site_value'];

        /** @phpstan-ignore method.nonObject */
        $this->serviceMock->shouldReceive('getSiteAnalytics')
            ->once()
            ->andReturn($analyticsData);

        // Act
        $response = $this->controller->siteAnalytics();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['analytics' => $analyticsData], $response->getData(true));
    }

    /**
     * Test that trackBehavior returns success for valid authenticated request
     *
     * @covers ::trackBehavior
     */
    public function test_track_behavior_returns_success_for_valid_authenticated_request(): void
    {
        // Arrange
        $action = 'test_action';
        $data = ['key' => 'value'];
        $validated = ['action' => $action, 'data' => $data];

        /** @phpstan-ignore method.nonObject */
        $this->requestMock->shouldReceive('validate')
            ->with([
                'action' => 'required|string|max:50',
                'data' => 'nullable|array',
            ])
            ->andReturn($validated);

        /** @phpstan-ignore method.nonObject */
        $this->requestMock->shouldReceive('user')
            ->andReturn($this->user);

        /** @phpstan-ignore method.nonObject */
        $this->serviceMock->shouldReceive('trackUserBehavior')
            ->with($this->user, $action, $data)
            ->once();

        // Act
        $response = $this->controller->trackBehavior($this->requestMock);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'success' => true,
            'message' => 'تم تسجيل السلوك بنجاح',
        ], $response->getData(true));
    }

    /**
     * Test that trackBehavior returns unauthorized for unauthenticated user
     *
     * @covers ::trackBehavior
     */
    public function test_track_behavior_returns_unauthorized_for_unauthenticated_user(): void
    {
        // Arrange
        $validated = ['action' => 'test', 'data' => []];

        /** @phpstan-ignore method.nonObject */
        $this->requestMock->shouldReceive('validate')
            ->with([
                'action' => 'required|string|max:50',
                'data' => 'nullable|array',
            ])
            ->andReturn($validated);

        /** @phpstan-ignore method.nonObject */
        $this->requestMock->shouldReceive('user')
            ->andReturn(null);

        // Act
        $response = $this->controller->trackBehavior($this->requestMock);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['error' => 'Unauthorized'], $response->getData(true));
    }

    /**
     * Test that trackBehavior fails validation for invalid action
     *
     * @covers ::trackBehavior
     */
    public function test_track_behavior_fails_validation_for_invalid_action(): void
    {
        // Arrange
        $validator = Validator::make([], ['action' => 'required']);

        /** @phpstan-ignore method.nonObject */
        $this->requestMock->shouldReceive('validate')
            ->with([
                'action' => 'required|string|max:50',
                'data' => 'nullable|array',
            ])
            ->andThrow(new ValidationException($validator));

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->controller->trackBehavior($this->requestMock);
    }

    /**
     * Test that userAnalytics throws exception when service fails
     *
     * @covers ::userAnalytics
     */
    public function test_user_analytics_throws_exception_when_service_fails(): void
    {
        // Arrange
        /** @phpstan-ignore method.nonObject */
        $this->serviceMock->shouldReceive('getUserAnalytics')
            ->with($this->user)
            ->once()
            ->andThrow(new \Exception('Service error'));

        /** @phpstan-ignore method.nonObject */
        $this->requestMock->shouldReceive('user')
            ->andReturn($this->user);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->controller->userAnalytics($this->requestMock);
    }

    /**
     * Test that siteAnalytics throws exception when service fails
     *
     * @covers ::siteAnalytics
     */
    public function test_site_analytics_throws_exception_when_service_fails(): void
    {
        // Arrange
        /** @phpstan-ignore method.nonObject */
        $this->serviceMock->shouldReceive('getSiteAnalytics')
            ->once()
            ->andThrow(new \Exception('Service error'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->controller->siteAnalytics();
    }

    /**
     * Test that trackBehavior throws exception when service fails
     *
     * @covers ::trackBehavior
     */
    public function test_track_behavior_throws_exception_when_service_fails(): void
    {
        // Arrange
        $action = 'test_action';
        $data = ['key' => 'value'];
        $validated = ['action' => $action, 'data' => $data];

        /** @phpstan-ignore method.nonObject */
        $this->requestMock->shouldReceive('validate')
            ->with([
                'action' => 'required|string|max:50',
                'data' => 'nullable|array',
            ])
            ->andReturn($validated);

        /** @phpstan-ignore method.nonObject */
        $this->requestMock->shouldReceive('user')
            ->andReturn($this->user);

        /** @phpstan-ignore method.nonObject */
        $this->serviceMock->shouldReceive('trackUserBehavior')
            ->with($this->user, $action, $data)
            ->once()
            ->andThrow(new \Exception('Service error'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->controller->trackBehavior($this->requestMock);
    }
}
