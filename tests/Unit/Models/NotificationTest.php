<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the Notification model.
 */
#[CoversClass(Notification::class)]
class NotificationTest extends TestCase
{
    /**
     * Test table name.
     */
    public function test_table_name(): void
    {
        $this->assertEquals('custom_notifications', (new Notification)->getTable());
    }

    /**
     * Test fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $fillable = [
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

        $this->assertEquals($fillable, (new Notification)->getFillable());
    }

    /**
     * Test casts.
     */
    public function test_casts(): void
    {
        $casts = [
            'data' => 'array',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
            'priority' => 'integer',
            'metadata' => 'array',
            'tags' => 'array',
        ];

        $this->assertEquals($casts, (new Notification)->getCasts());
    }

    /**
     * Test hidden attributes.
     */
    public function test_hidden_attributes(): void
    {
        $hidden = ['data'];

        $this->assertEquals($hidden, (new Notification)->getHidden());
    }

    /**
     * Test user relation is a BelongsTo instance.
     */
    public function test_user_relation(): void
    {
        $notification = new Notification;

        $relation = $notification->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(User::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeUnread applies correct where clause.
     */
    public function test_scope_unread(): void
    {
        $query = Notification::query()->unread();

        $this->assertEquals('select * from "custom_notifications" where "read_at" is null', $query->toSql());
    }

    /**
     * Test scopeRead applies correct where clause.
     */
    public function test_scope_read(): void
    {
        $query = Notification::query()->read();

        $this->assertEquals('select * from "custom_notifications" where "read_at" is not null', $query->toSql());
    }

    /**
     * Test scopeOfType applies correct where clause.
     */
    public function test_scope_of_type(): void
    {
        $query = Notification::query()->ofType('price_drop');

        $this->assertEquals('select * from "custom_notifications" where "type" = ?', $query->toSql());
        $this->assertEquals(['price_drop'], $query->getBindings());
    }

    /**
     * Test scopeOfPriority applies correct where clause.
     */
    public function test_scope_of_priority(): void
    {
        $query = Notification::query()->ofPriority(3);

        $this->assertEquals('select * from "custom_notifications" where "priority" = ?', $query->toSql());
        $this->assertEquals([3], $query->getBindings());
    }

    /**
     * Test scopeOfStatus applies correct where clause.
     */
    public function test_scope_of_status(): void
    {
        $query = Notification::query()->ofStatus('sent');

        $this->assertEquals('select * from "custom_notifications" where "status" = ?', $query->toSql());
        $this->assertEquals(['sent'], $query->getBindings());
    }

    /**
     * Test scopeSent applies correct where clause.
     */
    public function test_scope_sent(): void
    {
        $query = Notification::query()->sent();

        $this->assertEquals('select * from "custom_notifications" where "sent_at" is not null', $query->toSql());
    }

    /**
     * Test scopePending applies correct where clause.
     */
    public function test_scope_pending(): void
    {
        $query = Notification::query()->pending();

        $this->assertEquals('select * from "custom_notifications" where "sent_at" is null and "status" = ?', $query->toSql());
        $this->assertEquals(['pending'], $query->getBindings());
    }

    /**
     * Test scopeFailed applies correct where clause.
     */
    public function test_scope_failed(): void
    {
        $query = Notification::query()->failed();

        $this->assertEquals('select * from "custom_notifications" where "status" = ?', $query->toSql());
        $this->assertEquals(['failed'], $query->getBindings());
    }

    /**
     * Test scopeForUser applies correct where clause.
     */
    public function test_scope_for_user(): void
    {
        $query = Notification::query()->forUser(1);

        $this->assertEquals('select * from "custom_notifications" where "user_id" = ?', $query->toSql());
        $this->assertEquals([1], $query->getBindings());
    }

    /**
     * Test scopeAfter applies correct where clause.
     */
    public function test_scope_after(): void
    {
        $date = Carbon::now();

        $query = Notification::query()->after($date);

        $this->assertEquals('select * from "custom_notifications" where "created_at" > ?', $query->toSql());
        $this->assertEquals([$date], $query->getBindings());
    }

    /**
     * Test scopeBefore applies correct where clause.
     */
    public function test_scope_before(): void
    {
        $date = Carbon::now();

        $query = Notification::query()->before($date);

        $this->assertEquals('select * from "custom_notifications" where "created_at" < ?', $query->toSql());
        $this->assertEquals([$date], $query->getBindings());
    }

    /**
     * Test scopeBetween applies correct where clause.
     */
    public function test_scope_between(): void
    {
        $start = Carbon::now()->subDay();
        $end = Carbon::now();

        $query = Notification::query()->between($start, $end);

        $this->assertEquals('select * from "custom_notifications" where "created_at" between ? and ?', $query->toSql());
        $this->assertEquals([$start, $end], $query->getBindings());
    }

    /**
     * Test isRead returns true when read_at is set.
     */
    public function test_is_read_returns_true_when_read_at_set(): void
    {
        $notification = new Notification(['read_at' => Carbon::now()]);

        $this->assertTrue($notification->isRead());
    }

    /**
     * Test isRead returns false when read_at is null.
     */
    public function test_is_read_returns_false_when_read_at_null(): void
    {
        $notification = new Notification;

        $this->assertFalse($notification->isRead());
    }

    /**
     * Test isUnread returns true when read_at is null.
     */
    public function test_is_unread_returns_true_when_read_at_null(): void
    {
        $notification = new Notification;

        $this->assertTrue($notification->isUnread());
    }

    /**
     * Test isUnread returns false when read_at is set.
     */
    public function test_is_unread_returns_false_when_read_at_set(): void
    {
        $notification = new Notification(['read_at' => Carbon::now()]);

        $this->assertFalse($notification->isUnread());
    }

    /**
     * Test isSent returns true when sent_at is set.
     */
    public function test_is_sent_returns_true_when_sent_at_set(): void
    {
        $notification = new Notification;
        $notification->setAttribute('sent_at', Carbon::now());

        $this->assertTrue($notification->isSent());
    }

    /**
     * Test isSent returns false when sent_at is null.
     */
    public function test_is_sent_returns_false_when_sent_at_null(): void
    {
        $notification = new Notification;

        $this->assertFalse($notification->isSent());
    }

    /**
     * Test isPending returns true when sent_at is null and status is pending.
     */
    public function test_is_pending_returns_true(): void
    {
        $notification = new Notification;
        $notification->setAttribute('status', 'pending');

        $this->assertTrue($notification->isPending());
    }

    /**
     * Test isPending returns false when sent_at is set.
     */
    public function test_is_pending_returns_false_when_sent_at_set(): void
    {
        $notification = new Notification;
        $notification->setAttribute('sent_at', Carbon::now());
        $notification->setAttribute('status', 'pending');

        $this->assertFalse($notification->isPending());
    }

    /**
     * Test isFailed returns true when status is failed.
     */
    public function test_is_failed_returns_true(): void
    {
        $notification = new Notification;
        $notification->setAttribute('status', 'failed');

        $this->assertTrue($notification->isFailed());
    }

    /**
     * Test isFailed returns false when status is not failed.
     */
    public function test_is_failed_returns_false(): void
    {
        $notification = new Notification;
        $notification->setAttribute('status', 'sent');

        $this->assertFalse($notification->isFailed());
    }

    /**
     * Test getPriorityLevel returns correct level.
     */
    public function test_get_priority_level(): void
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

    /**
     * Test getTypeDisplayName returns correct name.
     */
    public function test_get_type_display_name(): void
    {
        $notification = new Notification(['type' => 'price_drop']);
        $this->assertEquals('Price Drop Alert', $notification->getTypeDisplayName());

        $notification->type = 'unknown_type';
        $this->assertEquals('Unknown type', $notification->getTypeDisplayName());
    }

    /**
     * Test getChannelDisplayName returns correct name.
     */
    public function test_get_channel_display_name(): void
    {
        $notification = new Notification;
        $notification->setAttribute('channel', 'email');
        $this->assertEquals('Email', $notification->getChannelDisplayName());

        $notification->setAttribute('channel', 'unknown');
        $this->assertEquals('Unknown', $notification->getChannelDisplayName());
    }

    /**
     * Test getStatusDisplayName returns correct name.
     */
    public function test_get_status_display_name(): void
    {
        $notification = new Notification;
        $notification->setAttribute('status', 'pending');
        $this->assertEquals('Pending', $notification->getStatusDisplayName());

        $notification->setAttribute('status', 'unknown');
        $this->assertEquals('Unknown', $notification->getStatusDisplayName());
    }

    /**
     * Test getIcon returns correct icon.
     */
    public function test_get_icon(): void
    {
        $notification = new Notification(['type' => 'price_drop']);
        $this->assertEquals('💰', $notification->getIcon());

        $notification->type = 'unknown';
        $this->assertEquals('📢', $notification->getIcon());
    }

    /**
     * Test getColor returns correct color.
     */
    public function test_get_color(): void
    {
        $notification = new Notification;
        $notification->setAttribute('priority', 1);
        $this->assertEquals('gray', $notification->getColor());

        $notification->setAttribute('priority', 2);
        $this->assertEquals('blue', $notification->getColor());
    }

    /**
     * Test getBadgeText returns correct text.
     */
    public function test_get_badge_text(): void
    {
        $notification = new Notification;
        $this->assertEquals('New', $notification->getBadgeText());

        $notification->setAttribute('read_at', Carbon::now());
        $this->assertEquals('', $notification->getBadgeText());

        $notification->setAttribute('status', 'failed');
        $this->assertEquals('Failed', $notification->getBadgeText());
    }

    /**
     * Test getSummary returns truncated message.
     */
    public function test_get_summary(): void
    {
        $notification = new Notification(['message' => 'This is a long message that should be truncated']);
        $this->assertEquals('This is a long message that should be truncated', $notification->getSummary(100));

        $this->assertEquals('This is a long...', $notification->getSummary(15));
    }

    /**
     * Test hasAction returns true when url is set.
     */
    public function test_has_action(): void
    {
        $notification = new Notification(['data' => ['url' => 'http://example.com']]);
        $this->assertTrue($notification->hasAction());

        $notification->data = [];
        $this->assertFalse($notification->hasAction());
    }

    /**
     * Test isExpired returns true when expired.
     */
    public function test_is_expired(): void
    {
        $notification = new Notification([
            'data' => ['expiration_days' => 1],
            'created_at' => Carbon::now()->subDays(2),
        ]);
        $this->assertTrue($notification->isExpired());

        $notification->data = [];
        $this->assertFalse($notification->isExpired());
    }

    /**
     * Test canRetry returns true for failed notification with retries left.
     */
    public function test_can_retry(): void
    {
        $notification = new Notification;
        $notification->setAttribute('status', 'failed');
        $notification->data = ['retry_count' => 1];
        $this->assertTrue($notification->canRetry(3));

        $notification->data = ['retry_count' => 3];
        $this->assertFalse($notification->canRetry(3));
    }

    /**
     * Test hasTag returns true when tag exists.
     */
    public function test_has_tag(): void
    {
        $notification = new Notification(['data' => ['tags' => ['urgent', 'important']]]);
        $this->assertTrue($notification->hasTag('urgent'));
        $this->assertFalse($notification->hasTag('normal'));
    }
}
