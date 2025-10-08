<?php

namespace Tests\Feature\Models;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_an_audit_log(): void
    {
        $auditLog = AuditLog::factory()->create([
            'event' => 'created',
            'auditable_type' => Product::class,
            'auditable_id' => 1,
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
            'metadata' => ['source' => 'web'],
            'url' => '/products/1',
            'method' => 'POST',
        ]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('created', $auditLog->event);
        $this->assertEquals(Product::class, $auditLog->auditable_type);
        $this->assertEquals(1, $auditLog->auditable_id);
        $this->assertEquals(1, $auditLog->user_id);
        $this->assertEquals('127.0.0.1', $auditLog->ip_address);
        $this->assertEquals('Mozilla/5.0', $auditLog->user_agent);
        $this->assertEquals(['name' => 'Old Name'], $auditLog->old_values);
        $this->assertEquals(['name' => 'New Name'], $auditLog->new_values);
        $this->assertEquals(['source' => 'web'], $auditLog->metadata);
        $this->assertEquals('/products/1', $auditLog->url);
        $this->assertEquals('POST', $auditLog->method);

        // Assert that the audit log was actually saved to the database
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'auditable_type' => Product::class,
            'auditable_id' => 1,
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'url' => '/products/1',
            'method' => 'POST',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_casts_attributes_correctly(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Old Name', 'price' => 100],
            'new_values' => ['name' => 'New Name', 'price' => 150],
            'metadata' => ['source' => 'web', 'timestamp' => '2023-01-01'],
        ]);

        $this->assertIsArray($auditLog->old_values);
        $this->assertIsArray($auditLog->new_values);
        $this->assertIsArray($auditLog->metadata);
        $this->assertEquals(['name' => 'Old Name', 'price' => 100], $auditLog->old_values);
        $this->assertEquals(['name' => 'New Name', 'price' => 150], $auditLog->new_values);
        $this->assertEquals(['source' => 'web', 'timestamp' => '2023-01-01'], $auditLog->metadata);

        // Assert that the JSON attributes were properly cast and saved
        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
        ]);

        // Verify the JSON data was properly stored
        $savedAuditLog = AuditLog::find($auditLog->id);
        $this->assertIsArray($savedAuditLog->old_values);
        $this->assertIsArray($savedAuditLog->new_values);
        $this->assertIsArray($savedAuditLog->metadata);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $auditLog->user);
        $this->assertEquals($user->id, $auditLog->user->id);

        // Assert that the audit log was saved with the correct user_id
        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
            'user_id' => $user->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_morph_to_auditable(): void
    {
        $product = Product::factory()->create();
        $auditLog = AuditLog::factory()->create([
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
        ]);

        $this->assertInstanceOf(Product::class, $auditLog->auditable);
        $this->assertEquals($product->id, $auditLog->auditable->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_event_filters_by_event(): void
    {
        AuditLog::factory()->create(['event' => 'created']);
        AuditLog::factory()->create(['event' => 'updated']);
        AuditLog::factory()->create(['event' => 'deleted']);

        $createdLogs = AuditLog::event('created')->get();
        $updatedLogs = AuditLog::event('updated')->get();
        $deletedLogs = AuditLog::event('deleted')->get();

        $this->assertCount(1, $createdLogs);
        $this->assertCount(1, $updatedLogs);
        $this->assertCount(1, $deletedLogs);
        $this->assertEquals('created', $createdLogs->first()->event);
        $this->assertEquals('updated', $updatedLogs->first()->event);
        $this->assertEquals('deleted', $deletedLogs->first()->event);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_for_user_filters_by_user_id(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        AuditLog::factory()->create(['user_id' => $user1->id]);
        AuditLog::factory()->create(['user_id' => $user2->id]);
        AuditLog::factory()->create(['user_id' => null]);

        $user1Logs = AuditLog::forUser($user1->id)->get();
        $user2Logs = AuditLog::forUser($user2->id)->get();

        $this->assertCount(1, $user1Logs);
        $this->assertCount(1, $user2Logs);
        $this->assertEquals($user1->id, $user1Logs->first()->user_id);
        $this->assertEquals($user2->id, $user2Logs->first()->user_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_for_model_filters_by_auditable_type(): void
    {
        AuditLog::factory()->create(['auditable_type' => Product::class]);
        AuditLog::factory()->create(['auditable_type' => User::class]);
        AuditLog::factory()->create(['auditable_type' => Product::class]);

        $productLogs = AuditLog::forModel(Product::class)->get();
        $userLogs = AuditLog::forModel(User::class)->get();

        $this->assertCount(2, $productLogs);
        $this->assertCount(1, $userLogs);
        $this->assertTrue($productLogs->every(fn ($log) => $log->auditable_type === Product::class));
        $this->assertTrue($userLogs->every(fn ($log) => $log->auditable_type === User::class));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_date_range_filters_by_created_at(): void
    {
        $now = now();
        $yesterday = $now->copy()->subDay();
        $tomorrow = $now->copy()->addDay();

        AuditLog::factory()->create(['created_at' => $yesterday]);
        AuditLog::factory()->create(['created_at' => $now]);
        AuditLog::factory()->create(['created_at' => $tomorrow]);

        $logsInRange = AuditLog::dateRange($yesterday, $tomorrow)->get();
        $logsBefore = AuditLog::dateRange($yesterday->copy()->subDay(), $yesterday)->get();
        $logsAfter = AuditLog::dateRange($tomorrow, $tomorrow->copy()->addDay())->get();

        $this->assertCount(3, $logsInRange);
        $this->assertCount(1, $logsBefore);
        $this->assertCount(1, $logsAfter);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_formatted_event_attribute(): void
    {
        $auditLog = AuditLog::factory()->create(['event' => 'user_created']);

        $this->assertEquals('User created', $auditLog->formatted_event);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_with_changes(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Old Name', 'price' => 100],
            'new_values' => ['name' => 'New Name', 'price' => 150],
        ]);

        $summary = $auditLog->changes_summary;
        $this->assertStringContainsString('name: Old Name → New Name', $summary);
        $this->assertStringContainsString('price: 100 → 150', $summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_without_changes(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => null,
            'new_values' => null,
        ]);

        $this->assertEquals('No changes recorded', $auditLog->changes_summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_with_no_old_values(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => null,
            'new_values' => ['name' => 'New Name'],
        ]);

        $this->assertEquals('No changes recorded', $auditLog->changes_summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_with_no_new_values(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Old Name'],
            'new_values' => null,
        ]);

        $this->assertEquals('No changes recorded', $auditLog->changes_summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_with_unchanged_values(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Same Name', 'price' => 100],
            'new_values' => ['name' => 'Same Name', 'price' => 100],
        ]);

        $this->assertEquals('', $auditLog->changes_summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_with_mixed_data_types(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Old Name', 'active' => true, 'count' => 5],
            'new_values' => ['name' => 'New Name', 'active' => false, 'count' => 10],
        ]);

        $summary = $auditLog->changes_summary;
        $this->assertStringContainsString('name: Old Name → New Name', $summary);
        $this->assertStringContainsString('active: 1 → 0', $summary);
        $this->assertStringContainsString('count: 5 → 10', $summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_factory_creates_valid_audit_log(): void
    {
        $auditLog = AuditLog::factory()->make();

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertNotEmpty($auditLog->event);
        $this->assertNotEmpty($auditLog->auditable_type);
        $this->assertNotNull($auditLog->auditable_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'event',
            'auditable_type',
            'auditable_id',
            'user_id',
            'ip_address',
            'user_agent',
            'old_values',
            'new_values',
            'metadata',
            'url',
            'method',
        ];

        $this->assertEquals($fillable, (new AuditLog)->getFillable());
    }
}
