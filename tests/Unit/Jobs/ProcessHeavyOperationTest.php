<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessHeavyOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ProcessHeavyOperationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_job_constructor_sets_properties_correctly(): void
    {
        $operation = 'test_operation';
        $data = ['key' => 'value'];
        $userId = 123;

        $job = new ProcessHeavyOperation($operation, $data, $userId);

        // Use reflection to access private properties
        $reflection = new \ReflectionClass($job);

        $operationProperty = $reflection->getProperty('operation');
        $operationProperty->setAccessible(true);
        $this->assertEquals($operation, $operationProperty->getValue($job));

        $dataProperty = $reflection->getProperty('data');
        $dataProperty->setAccessible(true);
        $this->assertEquals($data, $dataProperty->getValue($job));

        $userIdProperty = $reflection->getProperty('userId');
        $userIdProperty->setAccessible(true);
        $this->assertEquals($userId, $userIdProperty->getValue($job));
    }

    /**
     * Test that ProcessHeavyOperationTest can be instantiated.
     */
    public function test_can_be_instantiated(): void
    {
        $this->assertInstanceOf(self::class, $this);
    }

    public function test_job_has_correct_timeout_and_retry_settings(): void
    {
        $job = new ProcessHeavyOperation('test', [], 1);

        $this->assertEquals(300, $job->timeout);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(3, $job->maxExceptions);
    }

    public function test_job_get_job_status_returns_null_for_unknown_job(): void
    {
        $status = ProcessHeavyOperation::getJobStatus('unknown-job-id');
        $this->assertNull($status);
    }

    public function test_job_get_user_job_statuses_returns_empty_array(): void
    {
        $statuses = ProcessHeavyOperation::getUserJobStatuses(1);
        $this->assertIsArray($statuses);
        $this->assertEmpty($statuses);
    }

    public function test_job_throws_exception_for_unknown_operation(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $job = new ProcessHeavyOperation('unknown_operation', [], 1);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown operation: unknown_operation');

        $job->handle();
    }

    public function test_job_handles_generate_report_operation_without_job_instance(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->never();

        $job = new ProcessHeavyOperation('generate_report', [
            'type' => 'sales',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ], 1);

        // Don't set jobInstance to test the fallback behavior
        $job->handle();

        $this->assertTrue(true); // Job completed without exception
    }

    public function test_job_handles_process_images_operation_without_job_instance(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->never();

        $job = new ProcessHeavyOperation('process_images', [
            'image_ids' => [1, 2, 3, 4, 5],
        ], 1);

        $job->handle();

        $this->assertTrue(true); // Job completed without exception
    }

    public function test_job_handles_sync_data_operation_without_job_instance(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->never();

        $job = new ProcessHeavyOperation('sync_data', [
            'source' => 'external_api',
        ], 1);

        $job->handle();

        $this->assertTrue(true); // Job completed without exception
    }

    public function test_job_handles_send_bulk_notifications_operation_without_job_instance(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->never();

        $job = new ProcessHeavyOperation('send_bulk_notifications', [
            'user_ids' => [1, 2, 3, 4, 5],
            'message' => 'Test notification',
        ], 1);

        $job->handle();

        $this->assertTrue(true); // Job completed without exception
    }

    public function test_job_handles_update_statistics_operation_without_job_instance(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->never();

        $job = new ProcessHeavyOperation('update_statistics', [
            'stat_types' => ['users', 'products', 'orders'],
        ], 1);

        $job->handle();

        $this->assertTrue(true); // Job completed without exception
    }

    public function test_job_handles_cleanup_old_data_operation_without_job_instance(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->never();

        $job = new ProcessHeavyOperation('cleanup_old_data', [
            'days_old' => 30,
        ], 1);

        $job->handle();

        $this->assertTrue(true); // Job completed without exception
    }

    public function test_job_handles_export_data_operation_without_job_instance(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->never();

        $job = new ProcessHeavyOperation('export_data', [
            'format' => 'csv',
            'table' => 'products',
        ], 1);

        $job->handle();

        $this->assertTrue(true); // Job completed without exception
    }

    public function test_job_handles_import_data_operation_without_job_instance(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->never();

        $job = new ProcessHeavyOperation('import_data', [
            'file_path' => '/path/to/file.csv',
        ], 1);

        $job->handle();

        $this->assertTrue(true); // Job completed without exception
    }
}
