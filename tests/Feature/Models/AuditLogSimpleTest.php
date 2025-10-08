<?php

namespace Tests\Feature\Models;

use App\Models\AuditLog;
use Tests\TestCase;

class AuditLogSimpleTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_correct_fillable_attributes(): void
    {
        $auditLog = new AuditLog;

        $expectedFillable = [
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

        $this->assertEquals($expectedFillable, $auditLog->getFillable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_correct_casts(): void
    {
        $auditLog = new AuditLog;

        $expectedCasts = [
            'id' => 'int',
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];

        $this->assertEquals($expectedCasts, $auditLog->getCasts());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_correct_table_name(): void
    {
        $auditLog = new AuditLog;

        $this->assertEquals('audit_logs', $auditLog->getTable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_uses_timestamps(): void
    {
        $auditLog = new AuditLog;

        $this->assertTrue($auditLog->usesTimestamps());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_formatted_event_attribute(): void
    {
        $auditLog = new AuditLog(['event' => 'user_created']);

        $this->assertEquals('User created', $auditLog->getFormattedEventAttribute());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_with_changes(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['name' => 'Old Name', 'price' => 100],
            'new_values' => ['name' => 'New Name', 'price' => 150],
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        $this->assertStringContainsString('name: Old Name → New Name', $summary);
        $this->assertStringContainsString('price: 100 → 150', $summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_without_changes(): void
    {
        $auditLog = new AuditLog([
            'old_values' => null,
            'new_values' => null,
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        $this->assertEquals('No changes recorded', $summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_with_no_old_values(): void
    {
        $auditLog = new AuditLog([
            'old_values' => null,
            'new_values' => ['name' => 'New Name'],
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        $this->assertStringContainsString('No changes recorded', $summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_with_no_new_values(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['name' => 'Old Name'],
            'new_values' => null,
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        $this->assertStringContainsString('No changes recorded', $summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_with_unchanged_values(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['name' => 'Same Name', 'price' => 100],
            'new_values' => ['name' => 'Same Name', 'price' => 100],
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        $this->assertEquals('', $summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_changes_summary_attribute_with_mixed_data_types(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['name' => 'Old Name', 'active' => true, 'count' => 5],
            'new_values' => ['name' => 'New Name', 'active' => false, 'count' => 10],
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        $this->assertStringContainsString('name: Old Name → New Name', $summary);
        $this->assertStringContainsString('active: 1 → 0', $summary);
        $this->assertStringContainsString('count: 5 → 10', $summary);
    }

    /**
     * Test that AuditLogSimpleTest can be instantiated.
     */
    public function test_can_be_instantiated(): void
    {
        $this->assertInstanceOf(self::class, $this);
    }
}
