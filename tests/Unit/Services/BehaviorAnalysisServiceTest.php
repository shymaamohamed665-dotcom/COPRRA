<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\BehaviorAnalysisService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BehaviorAnalysisServiceTest extends TestCase
{
    private BehaviorAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BehaviorAnalysisService;
    }

    public function test_track_user_behavior_inserts_into_database(): void
    {
        // Arrange
        $user = User::factory()->create();
        $action = 'page_view';
        $data = ['page' => 'home'];

        // Mock DB facade
        DB::shouldReceive('table')
            ->with('user_behaviors')
            ->andReturnSelf();
        DB::shouldReceive('insert')
            ->once()
            ->with([
                'user_id' => $user->id,
                'action' => $action,
                'data' => json_encode($data),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // Act
        $this->service->trackUserBehavior($user, $action, $data);

        // Assert
        // Mockery will verify the calls
    }

    public function test_get_user_analytics_returns_cached_data(): void
    {
        // Arrange
        $user = User::factory()->create();
        $expectedData = ['key' => 'value'];

        Cache::shouldReceive('remember')
            ->once()
            ->with("user_analytics_{$user->id}", 1800, \Mockery::on(function ($callback) {
                // Since it's a closure, we can't easily mock the private methods
                // For unit test, perhaps test the public method and mock Cache
                return true;
            }))
            ->andReturn($expectedData);

        // Act
        $result = $this->service->getUserAnalytics($user);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    public function test_get_site_analytics_returns_cached_data(): void
    {
        // Arrange
        $expectedData = ['total_users' => 100];

        Cache::shouldReceive('remember')
            ->once()
            ->with('site_analytics', 3600, \Mockery::any())
            ->andReturn($expectedData);

        // Act
        $result = $this->service->getSiteAnalytics();

        // Assert
        $this->assertEquals($expectedData, $result);
    }
}
