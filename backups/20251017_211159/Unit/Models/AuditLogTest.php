<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Tests\TestCase;

/**
 * Unit tests for the AuditLog model.
 *
 * @covers \App\Models\AuditLog
 */
class AuditLogTest extends TestCase
{
    /**
     * Test fillable attributes.
     */
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

    /**
     * Test casts.
     */
    public function test_casts(): void
    {
        $casts = [
            'id' => 'int',
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];

        $this->assertEquals($casts, (new AuditLog)->getCasts());
    }

    /**
     * Test user relation is a BelongsTo instance.
     */
    public function test_user_relation(): void
    {
        $auditLog = new AuditLog;

        $relation = $auditLog->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(User::class, $relation->getRelated()::class);
    }

    /**
     * Test auditable relation is a MorphTo instance.
     */
    public function test_auditable_relation(): void
    {
        $auditLog = new AuditLog;

        $relation = $auditLog->auditable();

        $this->assertInstanceOf(MorphTo::class, $relation);
    }

    /**
     * Test scopeEvent applies correct where clause.
     */
    public function test_scope_event(): void
    {
        $query = AuditLog::query()->event('created');

        $this->assertEquals('select * from "audit_logs" where "event" = ?', $query->toSql());
        $this->assertEquals(['created'], $query->getBindings());
    }

    /**
     * Test scopeForUser applies correct where clause.
     */
    public function test_scope_for_user(): void
    {
        $query = AuditLog::query()->forUser(1);

        $this->assertEquals('select * from "audit_logs" where "user_id" = ?', $query->toSql());
        $this->assertEquals([1], $query->getBindings());
    }

    /**
     * Test scopeForModel applies correct where clause.
     */
    public function test_scope_for_model(): void
    {
        $query = AuditLog::query()->forModel('App\\Models\\User');

        $this->assertEquals('select * from "audit_logs" where "auditable_type" = ?', $query->toSql());
        $this->assertEquals(['App\\Models\\User'], $query->getBindings());
    }

    /**
     * Test scopeDateRange applies correct where clause.
     */
    public function test_scope_date_range(): void
    {
        $startDate = '2023-01-01';
        $endDate = '2023-12-31';

        $query = AuditLog::query()->dateRange($startDate, $endDate);

        $this->assertEquals('select * from "audit_logs" where "created_at" between ? and ?', $query->toSql());
        $this->assertEquals([$startDate, $endDate], $query->getBindings());
    }

    /**
     * Test getFormattedEventAttribute formats event correctly.
     */
    public function test_get_formatted_event_attribute(): void
    {
        $auditLog = new AuditLog(['event' => 'user_created']);

        $this->assertEquals('User created', $auditLog->formatted_event);
    }

    /**
     * Test getChangesSummaryAttribute returns 'No changes recorded' when no values.
     */
    public function test_get_changes_summary_attribute_no_changes(): void
    {
        $auditLog = new AuditLog;

        $this->assertEquals('No changes recorded', $auditLog->changes_summary);
    }

    /**
     * Test getChangesSummaryAttribute returns 'No changes recorded' when only old_values.
     */
    public function test_get_changes_summary_attribute_only_old_values(): void
    {
        $auditLog = new AuditLog(['old_values' => ['name' => 'old']]);

        $this->assertEquals('No changes recorded', $auditLog->changes_summary);
    }

    /**
     * Test getChangesSummaryAttribute returns 'No changes recorded' when only new_values.
     */
    public function test_get_changes_summary_attribute_only_new_values(): void
    {
        $auditLog = new AuditLog(['new_values' => ['name' => 'new']]);

        $this->assertEquals('No changes recorded', $auditLog->changes_summary);
    }

    /**
     * Test getChangesSummaryAttribute returns summary of changes.
     */
    public function test_get_changes_summary_attribute_with_changes(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['name' => 'old', 'email' => 'old@example.com'],
            'new_values' => ['name' => 'new', 'email' => 'new@example.com'],
        ]);

        $summary = $auditLog->changes_summary;

        $this->assertStringContainsString('name: old → new', $summary);
        $this->assertStringContainsString('email: old@example.com → new@example.com', $summary);
    }

    /**
     * Test getChangesSummaryAttribute handles boolean values.
     */
    public function test_get_changes_summary_attribute_boolean_values(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['active' => false],
            'new_values' => ['active' => true],
        ]);

        $summary = $auditLog->changes_summary;

        $this->assertStringContainsString('active: 0 → 1', $summary);
    }

    /**
     * Test getChangesSummaryAttribute handles null values.
     */
    public function test_get_changes_summary_attribute_null_values(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['description' => 'old'],
            'new_values' => ['description' => null],
        ]);

        $summary = $auditLog->changes_summary;

        $this->assertStringContainsString('description: old → null', $summary);
    }

    /**
     * Test getChangesSummaryAttribute returns empty string when no actual changes.
     */
    public function test_get_changes_summary_attribute_no_actual_changes(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['name' => 'same'],
            'new_values' => ['name' => 'same'],
        ]);

        $this->assertEquals('', $auditLog->changes_summary);
    }
}
