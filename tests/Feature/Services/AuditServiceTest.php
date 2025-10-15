<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditService;

        // Mock request for all tests
        $mockRequest = Mockery::mock(Request::class)->makePartial();
        $mockRequest->shouldReceive('ip')->andReturn('127.0.0.1');
        $mockRequest->shouldReceive('userAgent')->andReturn('TestAgent/1.0');
        $mockRequest->shouldReceive('fullUrl')->andReturn('http://example.com/test');
        $mockRequest->shouldReceive('method')->andReturn('GET');
        $mockRequest->shouldReceive('setUserResolver')->andReturnSelf();
        $mockRequest->shouldReceive('getUserResolver')->andReturn(null);
        $mockRequest->shouldReceive('hasUserResolver')->andReturn(false);
        app()->instance(Request::class, $mockRequest);
        app()->instance('request', $mockRequest);
    }

    public function test_can_be_instantiated(): void
    {
        $this->assertInstanceOf(AuditService::class, $this->service);
    }

    public function test_logs_created_event(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->service->logCreated($user);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'url' => 'http://example.com/test',
            'method' => 'GET',
        ]);

        $log = AuditLog::first();
        $this->assertEquals($user->getAttributes(), $log->new_values);
        $this->assertNull($log->old_values);
    }

    public function test_logs_updated_event(): void
    {
        $user = User::factory()->create();
        $oldValues = ['name' => 'Old Name'];

        $this->actingAs($user);

        $this->service->logUpdated($user, $oldValues);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);

        $log = AuditLog::first();
        $this->assertEquals($oldValues, $log->old_values);
        $this->assertEquals($user->getChanges(), $log->new_values);
    }

    public function test_logs_deleted_event(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->service->logDeleted($user);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deleted',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);

        $log = AuditLog::first();
        $this->assertEquals($user->getAttributes(), $log->old_values);
        $this->assertNull($log->new_values);
    }

    public function test_logs_viewed_event(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->service->logViewed($user);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'viewed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);

        $log = AuditLog::first();
        $this->assertNull($log->old_values);
        $this->assertNull($log->new_values);
    }

    public function test_logs_sensitive_operation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->service->logSensitiveOperation('password_change', $user);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'password_change',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_logs_auth_event_with_user_id(): void
    {
        $performer = User::factory()->create(['email' => 'performer@example.com']);
        $targetUser = User::factory()->create(['email' => 'target@example.com']);

        $this->actingAs($performer);

        $this->service->logAuthEvent('login', $targetUser->id);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'login',
            'auditable_type' => User::class,
            'auditable_id' => $targetUser->id,
            'user_id' => $performer->id,
        ]);
    }

    public function test_logs_auth_event_without_user_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->service->logAuthEvent('logout');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'logout',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_logs_api_access(): void
    {
        $performer = User::factory()->create(['email' => 'performer2@example.com']);
        $targetUser = User::factory()->create(['email' => 'target2@example.com']);

        $this->actingAs($performer);

        $this->service->logApiAccess('/api/test', 'GET', $targetUser->id, ['response_time' => 150]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'api_access',
            'auditable_type' => User::class,
            'auditable_id' => $targetUser->id,
            'user_id' => $performer->id,
        ]);

        $log = AuditLog::first();
        $this->assertEquals(['endpoint' => '/api/test', 'method' => 'GET', 'response_time' => 150], $log->metadata);
    }

    public function test_gets_model_logs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->service->logCreated($user);
        usleep(1000000); // Ensure timestamp difference for ordering
        $this->service->logViewed($user);

        $logs = $this->service->getModelLogs($user);

        $this->assertCount(2, $logs);
        $this->assertEquals('viewed', $logs->first()->event);
        $this->assertEquals('created', $logs->last()->event);
    }

    public function test_gets_user_logs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->service->logCreated($user);

        $logs = $this->service->getUserLogs($user->id);

        $this->assertCount(1, $logs);
        $this->assertEquals('created', $logs->first()->event);
    }

    public function test_gets_event_logs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->service->logCreated($user);

        $logs = $this->service->getEventLogs('created');

        $this->assertCount(1, $logs);
        $this->assertEquals('created', $logs->first()->event);
    }

    public function test_cleans_old_logs(): void
    {
        $user = User::factory()->create();

        // Create old log
        AuditLog::factory()->create([
            'created_at' => now()->subDays(100),
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);

        $deleted = $this->service->cleanOldLogs(90);

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseMissing('audit_logs', ['auditable_id' => $user->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
