<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_a_notification(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'price_drop',
            'title' => 'Price Drop Alert',
            'message' => 'The price has dropped!',
            'data' => ['product_id' => 1, 'old_price' => 100, 'new_price' => 80],
            'priority' => 3,
            'channel' => 'email',
            'status' => 'pending',
            'metadata' => ['source' => 'system'],
            'tags' => ['price', 'alert'],
        ]);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertNotNull($notification->user_id);
        $this->assertEquals('price_drop', $notification->type);
        $this->assertEquals('Price Drop Alert', $notification->title);
        $this->assertEquals('The price has dropped!', $notification->message);
        $this->assertEquals(['product_id' => 1, 'old_price' => 100, 'new_price' => 80], $notification->data);
        $this->assertEquals(3, $notification->priority);
        $this->assertEquals('email', $notification->channel);
        $this->assertEquals('pending', $notification->status);
        $this->assertEquals(['source' => 'system'], $notification->metadata);
        $this->assertEquals(['price', 'alert'], $notification->tags);

        // Assert that the notification was actually saved to the database
        $this->assertDatabaseHas('custom_notifications', [
            'user_id' => $user->id,
            'type' => 'price_drop',
            'title' => 'Price Drop Alert',
            'message' => 'The price has dropped!',
            'priority' => 3,
            'channel' => 'email',
            'status' => 'pending',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_casts_attributes_correctly(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'data' => ['key' => 'value'],
            'read_at' => now(),
            'sent_at' => now(),
            'priority' => 2,
            'metadata' => ['source' => 'web'],
            'tags' => ['urgent', 'system'],
        ]);

        $this->assertIsArray($notification->data);
        $this->assertInstanceOf(Carbon::class, $notification->read_at);
        $this->assertInstanceOf(Carbon::class, $notification->sent_at);
        $this->assertIsInt($notification->priority);
        $this->assertIsArray($notification->metadata);
        $this->assertIsArray($notification->tags);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertEquals($user->id, $notification->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_unread_filters_unread_notifications(): void
    {
        Notification::factory()->create(['read_at' => null]);
        Notification::factory()->create(['read_at' => now()]);
        Notification::factory()->create(['read_at' => null]);

        $unreadNotifications = Notification::unread()->get();

        $this->assertCount(2, $unreadNotifications);
        $this->assertTrue($unreadNotifications->every(fn ($n) => $n->read_at === null));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_read_filters_read_notifications(): void
    {
        Notification::factory()->create(['read_at' => null]);
        Notification::factory()->create(['read_at' => now()]);
        Notification::factory()->create(['read_at' => now()->subHour()]);

        $readNotifications = Notification::read()->get();

        $this->assertCount(2, $readNotifications);
        $this->assertTrue($readNotifications->every(fn ($n) => $n->read_at !== null));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_of_type_filters_by_type(): void
    {
        Notification::factory()->create(['type' => 'price_drop']);
        Notification::factory()->create(['type' => 'new_product']);
        Notification::factory()->create(['type' => 'price_drop']);

        $priceDropNotifications = Notification::ofType('price_drop')->get();
        $newProductNotifications = Notification::ofType('new_product')->get();

        $this->assertCount(2, $priceDropNotifications);
        $this->assertCount(1, $newProductNotifications);
        $this->assertTrue($priceDropNotifications->every(fn ($n) => $n->type === 'price_drop'));
        $this->assertTrue($newProductNotifications->every(fn ($n) => $n->type === 'new_product'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_of_priority_filters_by_priority(): void
    {
        Notification::factory()->create(['priority' => 1]);
        Notification::factory()->create(['priority' => 2]);
        Notification::factory()->create(['priority' => 1]);

        $lowPriorityNotifications = Notification::ofPriority(1)->get();
        $normalPriorityNotifications = Notification::ofPriority(2)->get();

        $this->assertCount(2, $lowPriorityNotifications);
        $this->assertCount(1, $normalPriorityNotifications);
        $this->assertTrue($lowPriorityNotifications->every(fn ($n) => $n->priority === 1));
        $this->assertTrue($normalPriorityNotifications->every(fn ($n) => $n->priority === 2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_of_status_filters_by_status(): void
    {
        Notification::factory()->create(['status' => 'pending']);
        Notification::factory()->create(['status' => 'sent']);
        Notification::factory()->create(['status' => 'pending']);

        $pendingNotifications = Notification::ofStatus('pending')->get();
        $sentNotifications = Notification::ofStatus('sent')->get();

        $this->assertCount(2, $pendingNotifications);
        $this->assertCount(1, $sentNotifications);
        $this->assertTrue($pendingNotifications->every(fn ($n) => $n->status === 'pending'));
        $this->assertTrue($sentNotifications->every(fn ($n) => $n->status === 'sent'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_sent_filters_sent_notifications(): void
    {
        Notification::factory()->create(['sent_at' => now()]);
        Notification::factory()->create(['sent_at' => null]);
        Notification::factory()->create(['sent_at' => now()->subHour()]);

        $sentNotifications = Notification::sent()->get();

        $this->assertCount(2, $sentNotifications);
        $this->assertTrue($sentNotifications->every(fn ($n) => $n->sent_at !== null));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_pending_filters_pending_notifications(): void
    {
        Notification::factory()->create(['sent_at' => null, 'status' => 'pending']);
        Notification::factory()->create(['sent_at' => now(), 'status' => 'sent']);
        Notification::factory()->create(['sent_at' => null, 'status' => 'failed']);

        $pendingNotifications = Notification::pending()->get();

        $this->assertCount(1, $pendingNotifications);
        $this->assertTrue($pendingNotifications->every(fn ($n) => $n->sent_at === null && $n->status === 'pending'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_failed_filters_failed_notifications(): void
    {
        Notification::factory()->create(['status' => 'failed']);
        Notification::factory()->create(['status' => 'sent']);
        Notification::factory()->create(['status' => 'failed']);

        $failedNotifications = Notification::failed()->get();

        $this->assertCount(2, $failedNotifications);
        $this->assertTrue($failedNotifications->every(fn ($n) => $n->status === 'failed'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_for_user_filters_by_user_id(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        Notification::factory()->create(['user_id' => $user1->id]);
        Notification::factory()->create(['user_id' => $user2->id]);
        Notification::factory()->create(['user_id' => $user1->id]);

        $user1Notifications = Notification::forUser($user1->id)->get();
        $user2Notifications = Notification::forUser($user2->id)->get();

        $this->assertCount(2, $user1Notifications);
        $this->assertCount(1, $user2Notifications);
        $this->assertTrue($user1Notifications->every(fn ($n) => $n->user_id === $user1->id));
        $this->assertTrue($user2Notifications->every(fn ($n) => $n->user_id === $user2->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_after_filters_notifications_after_date(): void
    {
        $now = now();
        $yesterday = $now->copy()->subDay();
        $tomorrow = $now->copy()->addDay();

        Notification::factory()->create(['created_at' => $yesterday]);
        Notification::factory()->create(['created_at' => $now]);
        Notification::factory()->create(['created_at' => $tomorrow]);

        $notificationsAfter = Notification::after($yesterday)->get();

        $this->assertCount(2, $notificationsAfter);
        $this->assertTrue($notificationsAfter->every(fn ($n) => $n->created_at->gt($yesterday)));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_before_filters_notifications_before_date(): void
    {
        $now = now();
        $yesterday = $now->copy()->subDay();
        $tomorrow = $now->copy()->addDay();

        Notification::factory()->create(['created_at' => $yesterday]);
        Notification::factory()->create(['created_at' => $now]);
        Notification::factory()->create(['created_at' => $tomorrow]);

        $notificationsBefore = Notification::before($tomorrow)->get();

        $this->assertCount(2, $notificationsBefore);
        $this->assertTrue($notificationsBefore->every(fn ($n) => $n->created_at->lt($tomorrow)));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_between_filters_notifications_between_dates(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $now = now();
        $yesterday = $now->copy()->subDay();
        $tomorrow = $now->copy()->addDay();
        $dayAfterTomorrow = $now->copy()->addDays(2);

        Notification::factory()->create(['user_id' => $user->id, 'created_at' => $yesterday]);
        Notification::factory()->create(['user_id' => $user->id, 'created_at' => $now]);
        Notification::factory()->create(['user_id' => $user->id, 'created_at' => $tomorrow]);
        Notification::factory()->create(['user_id' => $user->id, 'created_at' => $dayAfterTomorrow]);

        $notificationsBetween = Notification::between($yesterday, $tomorrow)->get();

        $this->assertCount(3, $notificationsBetween);
        // Check that we have the expected notifications
        $this->assertTrue($notificationsBetween->count() >= 3);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_mark_as_read(): void
    {
        $notification = Notification::factory()->create(['read_at' => null]);

        $result = $notification->markAsRead();

        $this->assertTrue($result);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_mark_as_unread(): void
    {
        $notification = Notification::factory()->create(['read_at' => now()]);

        $result = $notification->markAsUnread();

        $this->assertTrue($result);
        $this->assertNull($notification->fresh()->read_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_mark_as_sent(): void
    {
        $notification = Notification::factory()->create(['sent_at' => null, 'status' => 'pending']);

        $result = $notification->markAsSent();

        $this->assertTrue($result);
        $this->assertNotNull($notification->fresh()->sent_at);
        $this->assertEquals('sent', $notification->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_mark_as_failed(): void
    {
        $notification = Notification::factory()->create(['status' => 'pending']);

        $result = $notification->markAsFailed('Connection timeout');

        $this->assertTrue($result);
        $this->assertEquals('failed', $notification->fresh()->status);
        $this->assertEquals('Connection timeout', $notification->fresh()->getData('failure_reason'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_read(): void
    {
        $readNotification = Notification::factory()->create(['read_at' => now()]);
        $unreadNotification = Notification::factory()->create(['read_at' => null]);

        $this->assertTrue($readNotification->isRead());
        $this->assertFalse($unreadNotification->isRead());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_unread(): void
    {
        $readNotification = Notification::factory()->create(['read_at' => now()]);
        $unreadNotification = Notification::factory()->create(['read_at' => null]);

        $this->assertFalse($readNotification->isUnread());
        $this->assertTrue($unreadNotification->isUnread());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_sent(): void
    {
        $sentNotification = Notification::factory()->create(['sent_at' => now()]);
        $unsentNotification = Notification::factory()->create(['sent_at' => null]);

        $this->assertTrue($sentNotification->isSent());
        $this->assertFalse($unsentNotification->isSent());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_pending(): void
    {
        $pendingNotification = Notification::factory()->create(['sent_at' => null, 'status' => 'pending']);
        $sentNotification = Notification::factory()->create(['sent_at' => now(), 'status' => 'sent']);
        $failedNotification = Notification::factory()->create(['sent_at' => null, 'status' => 'failed']);

        $this->assertTrue($pendingNotification->isPending());
        $this->assertFalse($sentNotification->isPending());
        $this->assertFalse($failedNotification->isPending());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_failed(): void
    {
        $failedNotification = Notification::factory()->create(['status' => 'failed']);
        $sentNotification = Notification::factory()->create(['status' => 'sent']);

        $this->assertTrue($failedNotification->isFailed());
        $this->assertFalse($sentNotification->isFailed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_priority_level(): void
    {
        $lowPriority = Notification::factory()->create(['priority' => 1]);
        $normalPriority = Notification::factory()->create(['priority' => 2]);
        $highPriority = Notification::factory()->create(['priority' => 3]);
        $urgentPriority = Notification::factory()->create(['priority' => 4]);
        $unknownPriority = Notification::factory()->create(['priority' => 5]);

        $this->assertEquals('low', $lowPriority->getPriorityLevel());
        $this->assertEquals('normal', $normalPriority->getPriorityLevel());
        $this->assertEquals('high', $highPriority->getPriorityLevel());
        $this->assertEquals('urgent', $urgentPriority->getPriorityLevel());
        $this->assertEquals('normal', $unknownPriority->getPriorityLevel());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_type_display_name(): void
    {
        $priceDrop = Notification::factory()->create(['type' => 'price_drop']);
        $newProduct = Notification::factory()->create(['type' => 'new_product']);
        $system = Notification::factory()->create(['type' => 'system']);
        $custom = Notification::factory()->create(['type' => 'custom_type']);

        $this->assertEquals('Price Drop Alert', $priceDrop->getTypeDisplayName());
        $this->assertEquals('New Product', $newProduct->getTypeDisplayName());
        $this->assertEquals('System Notification', $system->getTypeDisplayName());
        $this->assertEquals('Custom type', $custom->getTypeDisplayName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_channel_display_name(): void
    {
        $email = Notification::factory()->create(['channel' => 'email']);
        $sms = Notification::factory()->create(['channel' => 'sms']);
        $push = Notification::factory()->create(['channel' => 'push']);
        $custom = Notification::factory()->create(['channel' => 'custom_channel']);

        $this->assertEquals('Email', $email->getChannelDisplayName());
        $this->assertEquals('SMS', $sms->getChannelDisplayName());
        $this->assertEquals('Push Notification', $push->getChannelDisplayName());
        $this->assertEquals('Custom_channel', $custom->getChannelDisplayName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_status_display_name(): void
    {
        $pending = Notification::factory()->create(['status' => 'pending']);
        $sent = Notification::factory()->create(['status' => 'sent']);
        $failed = Notification::factory()->create(['status' => 'failed']);
        $custom = Notification::factory()->create(['status' => 'custom_status']);

        $this->assertEquals('Pending', $pending->getStatusDisplayName());
        $this->assertEquals('Sent', $sent->getStatusDisplayName());
        $this->assertEquals('Failed', $failed->getStatusDisplayName());
        $this->assertEquals('Custom_status', $custom->getStatusDisplayName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_time_ago(): void
    {
        $notification = Notification::factory()->create(['created_at' => now()->subHour()]);

        $this->assertStringContainsString('1 hour ago', $notification->getTimeAgo());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_read_time_ago(): void
    {
        $readNotification = Notification::factory()->create(['read_at' => now()->subMinutes(30)]);
        $unreadNotification = Notification::factory()->create(['read_at' => null]);

        $this->assertStringContainsString('30 minutes ago', $readNotification->getReadTimeAgo());
        $this->assertNull($unreadNotification->getReadTimeAgo());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_sent_time_ago(): void
    {
        $sentNotification = Notification::factory()->create(['sent_at' => now()->subMinutes(15)]);
        $unsentNotification = Notification::factory()->create(['sent_at' => null]);

        $this->assertStringContainsString('15 minutes ago', $sentNotification->getSentTimeAgo());
        $this->assertNull($unsentNotification->getSentTimeAgo());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_data(): void
    {
        $notification = Notification::factory()->create([
            'data' => ['product_id' => 1, 'price' => 100, 'nested' => ['key' => 'value']],
        ]);

        $this->assertEquals(['product_id' => 1, 'price' => 100, 'nested' => ['key' => 'value']], $notification->getData());
        $this->assertEquals(1, $notification->getData('product_id'));
        $this->assertEquals(100, $notification->getData('price'));
        $this->assertEquals('value', $notification->getData('nested.key'));
        $this->assertEquals('default', $notification->getData('nonexistent', 'default'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_set_data(): void
    {
        $notification = Notification::factory()->create(['data' => []]);

        $result = $notification->setData('new_key', 'new_value');

        $this->assertTrue($result);
        $this->assertEquals('new_value', $notification->fresh()->getData('new_key'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_icon(): void
    {
        $priceDrop = Notification::factory()->create(['type' => 'price_drop']);
        $newProduct = Notification::factory()->create(['type' => 'new_product']);
        $system = Notification::factory()->create(['type' => 'system']);
        $custom = Notification::factory()->create(['type' => 'custom_type']);

        $this->assertEquals('💰', $priceDrop->getIcon());
        $this->assertEquals('🆕', $newProduct->getIcon());
        $this->assertEquals('⚙️', $system->getIcon());
        $this->assertEquals('📢', $custom->getIcon());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_color(): void
    {
        $lowPriority = Notification::factory()->create(['priority' => 1]);
        $normalPriority = Notification::factory()->create(['priority' => 2]);
        $highPriority = Notification::factory()->create(['priority' => 3]);
        $urgentPriority = Notification::factory()->create(['priority' => 4]);

        $this->assertEquals('gray', $lowPriority->getColor());
        $this->assertEquals('blue', $normalPriority->getColor());
        $this->assertEquals('orange', $highPriority->getColor());
        $this->assertEquals('red', $urgentPriority->getColor());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_badge_text(): void
    {
        $unread = Notification::factory()->create(['read_at' => null]);
        $readFailed = Notification::factory()->create(['read_at' => now(), 'status' => 'failed']);
        $readPending = Notification::factory()->create(['read_at' => now(), 'sent_at' => null, 'status' => 'pending']);
        $readSent = Notification::factory()->create(['read_at' => now(), 'sent_at' => now(), 'status' => 'sent']);

        $this->assertEquals('New', $unread->getBadgeText());
        $this->assertEquals('Failed', $readFailed->getBadgeText());
        $this->assertEquals('Pending', $readPending->getBadgeText());
        $this->assertEquals('', $readSent->getBadgeText());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_summary(): void
    {
        $longMessage = str_repeat('This is a very long message that should be truncated. ', 10);
        $notification = Notification::factory()->create(['message' => $longMessage]);

        $summary = $notification->getSummary(50);
        $this->assertLessThanOrEqual(53, strlen($summary)); // 50 + '...'
        $this->assertStringEndsWith('...', $summary);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_url(): void
    {
        $notificationWithUrl = Notification::factory()->create(['data' => ['url' => 'https://example.com']]);
        $notificationWithoutUrl = Notification::factory()->create(['data' => []]);

        $this->assertEquals('https://example.com', $notificationWithUrl->getUrl());
        $this->assertNull($notificationWithoutUrl->getUrl());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_action_text(): void
    {
        $notificationWithAction = Notification::factory()->create(['data' => ['action_text' => 'View Product']]);
        $notificationWithoutAction = Notification::factory()->create(['data' => []]);

        $this->assertEquals('View Product', $notificationWithAction->getActionText());
        $this->assertEquals('View Details', $notificationWithoutAction->getActionText());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_has_action(): void
    {
        $notificationWithAction = Notification::factory()->create(['data' => ['url' => 'https://example.com']]);
        $notificationWithoutAction = Notification::factory()->create(['data' => []]);

        $this->assertTrue($notificationWithAction->hasAction());
        $this->assertFalse($notificationWithoutAction->hasAction());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_expiration_date(): void
    {
        $notificationWithExpiration = Notification::factory()->create([
            'data' => ['expiration_days' => 7],
            'created_at' => now(),
        ]);
        $notificationWithoutExpiration = Notification::factory()->create(['data' => []]);

        $this->assertInstanceOf(Carbon::class, $notificationWithExpiration->getExpirationDate());
        $this->assertNull($notificationWithoutExpiration->getExpirationDate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_expired(): void
    {
        $expiredNotification = Notification::factory()->create([
            'data' => ['expiration_days' => 1],
            'created_at' => now()->subDays(2),
        ]);
        $validNotification = Notification::factory()->create([
            'data' => ['expiration_days' => 7],
            'created_at' => now(),
        ]);

        $this->assertTrue($expiredNotification->isExpired());
        $this->assertFalse($validNotification->isExpired());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_retry_count(): void
    {
        $notificationWithRetries = Notification::factory()->create(['data' => ['retry_count' => 3]]);
        $notificationWithoutRetries = Notification::factory()->create(['data' => []]);

        $this->assertEquals(3, $notificationWithRetries->getRetryCount());
        $this->assertEquals(0, $notificationWithoutRetries->getRetryCount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_increment_retry_count(): void
    {
        $notification = Notification::factory()->create(['data' => ['retry_count' => 2]]);

        $result = $notification->incrementRetryCount();

        $this->assertTrue($result);
        $this->assertEquals(3, $notification->fresh()->getRetryCount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_retry(): void
    {
        $failedNotification = Notification::factory()->create(['status' => 'failed', 'data' => ['retry_count' => 2]]);
        $maxRetriesReached = Notification::factory()->create(['status' => 'failed', 'data' => ['retry_count' => 3]]);
        $sentNotification = Notification::factory()->create(['status' => 'sent']);

        $this->assertTrue($failedNotification->canRetry(3));
        $this->assertFalse($maxRetriesReached->canRetry(3));
        $this->assertFalse($sentNotification->canRetry(3));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_failure_reason(): void
    {
        $notificationWithReason = Notification::factory()->create(['data' => ['failure_reason' => 'Connection timeout']]);
        $notificationWithoutReason = Notification::factory()->create(['data' => []]);

        $this->assertEquals('Connection timeout', $notificationWithReason->getFailureReason());
        $this->assertNull($notificationWithoutReason->getFailureReason());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_metadata(): void
    {
        $notification = Notification::factory()->create(['data' => ['metadata' => ['source' => 'web', 'version' => '1.0']]]);

        $this->assertEquals(['source' => 'web', 'version' => '1.0'], $notification->getMetadata());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_set_metadata(): void
    {
        $notification = Notification::factory()->create(['data' => []]);

        $result = $notification->setMetadata(['source' => 'api', 'version' => '2.0']);

        $this->assertTrue($result);
        $this->assertEquals(['source' => 'api', 'version' => '2.0'], $notification->fresh()->getMetadata());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_tags(): void
    {
        $notification = Notification::factory()->create(['data' => ['tags' => ['urgent', 'system', 'price']]]);

        $this->assertEquals(['urgent', 'system', 'price'], $notification->getTags());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_set_tags(): void
    {
        $notification = Notification::factory()->create(['data' => []]);

        $result = $notification->setTags(['urgent', 'system']);

        $this->assertTrue($result);
        $this->assertEquals(['urgent', 'system'], $notification->fresh()->getTags());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_add_tag(): void
    {
        $notification = Notification::factory()->create(['data' => ['tags' => ['urgent']]]);

        $result = $notification->addTag('system');

        $this->assertTrue($result);
        $this->assertEquals(['urgent', 'system'], $notification->fresh()->getTags());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_add_existing_tag(): void
    {
        $notification = Notification::factory()->create(['data' => ['tags' => ['urgent']]]);

        $result = $notification->addTag('urgent');

        $this->assertTrue($result);
        $this->assertEquals(['urgent'], $notification->fresh()->getTags());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_remove_tag(): void
    {
        $notification = Notification::factory()->create(['data' => ['tags' => ['urgent', 'system']]]);

        $result = $notification->removeTag('urgent');

        $this->assertTrue($result);
        $this->assertEquals(['system'], $notification->fresh()->getTags());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_has_tag(): void
    {
        $notification = Notification::factory()->create(['data' => ['tags' => ['urgent', 'system']]]);

        $this->assertTrue($notification->hasTag('urgent'));
        $this->assertTrue($notification->hasTag('system'));
        $this->assertFalse($notification->hasTag('price'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_factory_creates_valid_notification(): void
    {
        $notification = Notification::factory()->make();

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertNotEmpty($notification->type);
        $this->assertNotEmpty($notification->title);
        $this->assertNotEmpty($notification->message);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_hidden_attributes(): void
    {
        $hidden = ['data'];

        $this->assertEquals($hidden, (new Notification)->getHidden());
    }
}
