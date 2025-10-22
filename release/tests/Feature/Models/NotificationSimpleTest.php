<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class NotificationSimpleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock config to prevent "Target class [config] does not exist" error
        $config = Mockery::mock();
        $config->shouldReceive('get')->with('app.timezone', Mockery::any())->andReturn('UTC');
        $config->shouldReceive('get')->with(Mockery::any())->andReturn(null);
        $this->app->instance('config', $config);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_correct_fillable_attributes(): void
    {
        $notification = new Notification;

        $expectedFillable = [
            'user_id',
            'type',
            'title',
            'message',
            'data',
            'read_at',
            'sent_at',
            'priority',
            'channel',
            'status',
            'metadata',
            'tags',
        ];

        $this->assertEquals($expectedFillable, $notification->getFillable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_correct_casts(): void
    {
        $notification = new Notification;

        $expectedCasts = [
            'data' => 'array',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
            'priority' => 'integer',
            'metadata' => 'array',
            'tags' => 'array',
            'id' => 'int',
        ];

        $this->assertEquals($expectedCasts, $notification->getCasts());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_correct_table_name(): void
    {
        $notification = new Notification;

        $this->assertEquals('custom_notifications', $notification->getTable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_uses_timestamps(): void
    {
        $notification = new Notification;

        $this->assertTrue($notification->usesTimestamps());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_correct_hidden_attributes(): void
    {
        $notification = new Notification;

        $expectedHidden = ['data'];

        $this->assertEquals($expectedHidden, $notification->getHidden());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_read_returns_true_when_read_at_is_set(): void
    {
        $notification = new Notification(['read_at' => now()]);

        $this->assertTrue($notification->isRead());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_read_returns_false_when_read_at_is_null(): void
    {
        $notification = new Notification(['read_at' => null]);

        $this->assertFalse($notification->isRead());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_unread_returns_true_when_read_at_is_null(): void
    {
        $notification = new Notification(['read_at' => null]);

        $this->assertTrue($notification->isUnread());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_unread_returns_false_when_read_at_is_set(): void
    {
        $notification = new Notification(['read_at' => now()]);

        $this->assertFalse($notification->isUnread());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_sent_returns_true_when_sent_at_is_set(): void
    {
        $notification = new Notification;
        $notification->setAttribute('sent_at', now());

        $this->assertTrue($notification->isSent());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_sent_returns_false_when_sent_at_is_null(): void
    {
        $notification = new Notification;
        $notification->setAttribute('sent_at', null);

        $this->assertFalse($notification->isSent());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_pending_returns_true_when_sent_at_is_null_and_status_is_pending(): void
    {
        $notification = new Notification;
        $notification->setAttribute('sent_at', null);
        $notification->setAttribute('status', 'pending');

        $this->assertTrue($notification->isPending());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_pending_returns_false_when_sent_at_is_set(): void
    {
        $notification = new Notification;
        $notification->setAttribute('sent_at', now());
        $notification->setAttribute('status', 'pending');

        $this->assertFalse($notification->isPending());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_failed_returns_true_when_status_is_failed(): void
    {
        $notification = new Notification;
        $notification->setAttribute('status', 'failed');

        $this->assertTrue($notification->isFailed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_failed_returns_false_when_status_is_not_failed(): void
    {
        $notification = new Notification;
        $notification->setAttribute('status', 'pending');

        $this->assertFalse($notification->isFailed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_priority_level_returns_correct_levels(): void
    {
        $notification = new Notification;

        $notification->setAttribute('priority', 1);
        $this->assertEquals('low', $notification->getPriorityLevel());

        $notification->setAttribute('priority', 2);
        $this->assertEquals('normal', $notification->getPriorityLevel());

        $notification->setAttribute('priority', 3);
        $this->assertEquals('high', $notification->getPriorityLevel());

        $notification->setAttribute('priority', 4);
        $this->assertEquals('urgent', $notification->getPriorityLevel());

        $notification->setAttribute('priority', 5);
        $this->assertEquals('normal', $notification->getPriorityLevel());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_type_display_name_returns_correct_names(): void
    {
        $notification = new Notification(['type' => 'price_drop']);
        $this->assertEquals('Price Drop Alert', $notification->getTypeDisplayName());

        $notification = new Notification(['type' => 'new_product']);
        $this->assertEquals('New Product', $notification->getTypeDisplayName());

        $notification = new Notification(['type' => 'system']);
        $this->assertEquals('System Notification', $notification->getTypeDisplayName());

        $notification = new Notification(['type' => 'unknown_type']);
        $this->assertEquals('Unknown type', $notification->getTypeDisplayName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_channel_display_name_returns_correct_names(): void
    {
        $notification = new Notification;

        $notification->setAttribute('channel', 'email');
        $this->assertEquals('Email', $notification->getChannelDisplayName());

        $notification->setAttribute('channel', 'sms');
        $this->assertEquals('SMS', $notification->getChannelDisplayName());

        $notification->setAttribute('channel', 'push');
        $this->assertEquals('Push Notification', $notification->getChannelDisplayName());

        $notification->setAttribute('channel', 'database');
        $this->assertEquals('Database', $notification->getChannelDisplayName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_status_display_name_returns_correct_names(): void
    {
        $notification = new Notification;

        $notification->setAttribute('status', 'pending');
        $this->assertEquals('Pending', $notification->getStatusDisplayName());

        $notification->setAttribute('status', 'sent');
        $this->assertEquals('Sent', $notification->getStatusDisplayName());

        $notification->setAttribute('status', 'failed');
        $this->assertEquals('Failed', $notification->getStatusDisplayName());

        $notification->setAttribute('status', 'cancelled');
        $this->assertEquals('Cancelled', $notification->getStatusDisplayName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_icon_returns_correct_icons(): void
    {
        $notification = new Notification(['type' => 'price_drop']);
        $this->assertEquals('💰', $notification->getIcon());

        $notification = new Notification(['type' => 'new_product']);
        $this->assertEquals('🆕', $notification->getIcon());

        $notification = new Notification(['type' => 'system']);
        $this->assertEquals('⚙️', $notification->getIcon());

        $notification = new Notification(['type' => 'unknown_type']);
        $this->assertEquals('📢', $notification->getIcon());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_color_returns_correct_colors(): void
    {
        $notification = new Notification;

        $notification->setAttribute('priority', 1);
        $this->assertEquals('gray', $notification->getColor());

        $notification->setAttribute('priority', 2);
        $this->assertEquals('blue', $notification->getColor());

        $notification->setAttribute('priority', 3);
        $this->assertEquals('orange', $notification->getColor());

        $notification->setAttribute('priority', 4);
        $this->assertEquals('red', $notification->getColor());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_badge_text_returns_correct_text(): void
    {
        $notification = new Notification(['read_at' => null]);
        $this->assertEquals('New', $notification->getBadgeText());

        $notification = new Notification(['read_at' => now()]);
        $notification->setAttribute('status', 'failed');
        $this->assertEquals('Failed', $notification->getBadgeText());

        $notification = new Notification(['read_at' => now()]);
        $notification->setAttribute('sent_at', null);
        $notification->setAttribute('status', 'pending');
        $this->assertEquals('Pending', $notification->getBadgeText());

        $notification = new Notification(['read_at' => now()]);
        $notification->setAttribute('status', 'sent');
        $this->assertEquals('', $notification->getBadgeText());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_summary_truncates_long_messages(): void
    {
        $longMessage = str_repeat('This is a very long message. ', 10);
        $notification = new Notification(['message' => $longMessage]);

        $summary = $notification->getSummary(50);

        $this->assertLessThanOrEqual(53, strlen($summary)); // 50 + "..."
        $this->assertStringEndsWith('...', $summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_summary_returns_full_message_when_short(): void
    {
        $shortMessage = 'Short message';
        $notification = new Notification(['message' => $shortMessage]);

        $summary = $notification->getSummary(50);

        $this->assertEquals($shortMessage, $summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_data_returns_data_array(): void
    {
        $data = ['key' => 'value', 'number' => 123];
        $notification = new Notification(['data' => $data]);

        $this->assertEquals($data, $notification->getData());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_data_returns_specific_key(): void
    {
        $data = ['key' => 'value', 'number' => 123];
        $notification = new Notification(['data' => $data]);

        $this->assertEquals('value', $notification->getData('key'));
        $this->assertEquals(123, $notification->getData('number'));
        $this->assertEquals('default', $notification->getData('nonexistent', 'default'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_metadata_returns_metadata_array(): void
    {
        $metadata = ['source' => 'system', 'version' => '1.0'];
        $notification = new Notification(['data' => ['metadata' => $metadata]]);

        $this->assertEquals($metadata, $notification->getMetadata());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_tags_returns_tags_array(): void
    {
        $tags = ['urgent', 'system', 'maintenance'];
        $notification = new Notification(['data' => ['tags' => $tags]]);

        $this->assertEquals($tags, $notification->getTags());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_has_tag_returns_correct_boolean(): void
    {
        $tags = ['urgent', 'system'];
        $notification = new Notification(['data' => ['tags' => $tags]]);

        $this->assertTrue($notification->hasTag('urgent'));
        $this->assertTrue($notification->hasTag('system'));
        $this->assertFalse($notification->hasTag('maintenance'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_retry_count_returns_correct_count(): void
    {
        $notification = new Notification(['data' => ['retry_count' => 3]]);

        $this->assertEquals(3, $notification->getRetryCount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_retry_count_returns_zero_when_not_set(): void
    {
        $notification = new Notification;

        $this->assertEquals(0, $notification->getRetryCount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_retry_returns_correct_boolean(): void
    {
        $notification = new Notification;
        $notification->setAttribute('status', 'failed');
        $notification->setAttribute('data', ['retry_count' => 2]);

        $this->assertTrue($notification->canRetry(3));
        $this->assertFalse($notification->canRetry(2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_retry_returns_false_when_not_failed(): void
    {
        $notification = new Notification;
        $notification->setAttribute('status', 'pending');
        $notification->setAttribute('data', ['retry_count' => 1]);

        $this->assertFalse($notification->canRetry(3));
    }
}
