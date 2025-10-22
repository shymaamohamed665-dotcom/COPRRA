<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\User;
use App\Notifications\PriceDropNotification;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    private \Mockery\MockInterface $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditService = Mockery::mock(AuditService::class);
        $this->service = new NotificationService($this->auditService);

        Notification::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sends_price_drop_notification_to_active_alerts(): void
    {
        // Arrange
        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);
        $user = User::factory()->create(['id' => 1, 'email' => 'test@example.com']);
        $oldPrice = 100.0;
        $newPrice = 80.0;
        $targetPrice = 90.0;

        $alert = PriceAlert::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'target_price' => $targetPrice,
            'is_active' => true,
        ]);

        $this->auditService->shouldReceive('logSensitiveOperation')
            ->once()
            ->with('price_drop_notification', Mockery::on(function ($passedUser) use ($user) {
                return $passedUser instanceof \App\Models\User && $passedUser->id === $user->id;
            }), Mockery::type('array'));

        // Act
        try {
            $this->service->sendPriceDropNotification($product, $oldPrice, $newPrice);
        } catch (\Exception $e) {
            $this->fail('Service threw exception: '.$e->getMessage());
        }

        // Assert
        Notification::assertSentTo($user, PriceDropNotification::class);
    }

    public function test_does_not_send_notification_when_no_active_alerts(): void
    {
        // Arrange
        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);
        $oldPrice = 100.0;
        $newPrice = 80.0;

        // No alerts created - should not send notification

        // Act
        $this->service->sendPriceDropNotification($product, $oldPrice, $newPrice);

        // Assert
        Notification::assertNothingSent();
    }

    public function test_skips_notification_for_user_without_email(): void
    {
        // Arrange
        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);

        // Create an in-memory user model without saving to DB, then nullify email
        $userWithoutEmail = User::factory()->make(['id' => 999]);
        $userWithoutEmail->email = null; // simulate missing email without violating DB constraints

        // Create an in-memory PriceAlert and attach the user without email
        $alert = new PriceAlert([
            'product_id' => $product->id,
            'user_id' => $userWithoutEmail->id,
            'target_price' => 90.0,
            'is_active' => true,
        ]);
        $alert->setRelation('user', $userWithoutEmail);

        $alertsCollection = new \Illuminate\Database\Eloquent\Collection([$alert]);

        // Mock the PriceAlert query chain to return our prepared collection
        $this->mock(PriceAlert::class, function ($mock) use ($alertsCollection) {
            $mock->shouldReceive('where')->andReturnSelf();
            $mock->shouldReceive('with')->andReturnSelf();
            $mock->shouldReceive('get')->andReturn($alertsCollection);
        });

        // Ensure audit logging is not called for users without email
        $this->auditService->shouldNotReceive('logSensitiveOperation');

        // Act
        $this->service->sendPriceDropNotification($product, 100.0, 80.0);

        // Assert
        Notification::assertNothingSent();
        $this->addToAssertionCount(1); // count expectations to avoid risky test classification
    }

    public function test_handles_exception_during_notification_sending(): void
    {
        // Arrange
        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);
        $oldPrice = 100.0;
        $newPrice = 80.0;

        // Mock database exception
        $this->mock(PriceAlert::class, function ($mock) {
            $mock->shouldReceive('where')->andThrow(new \Exception('Database error'));
        });

        // Mock Log to verify error is logged
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send price drop notifications', Mockery::type('array'));

        // Act
        $this->service->sendPriceDropNotification($product, $oldPrice, $newPrice);

        // Assert - ensure Mockery expectations are counted to avoid risky test classification
        $this->addToAssertionCount(1);
    }

    public function test_logs_audit_trail_for_notification(): void
    {
        // Arrange
        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);
        $user = User::factory()->create(['id' => 1, 'email' => 'test@example.com']);
        $oldPrice = 100.0;
        $newPrice = 80.0;
        $targetPrice = 90.0;

        $alert = PriceAlert::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'target_price' => $targetPrice,
            'is_active' => true,
        ]);

        $this->auditService->shouldReceive('logSensitiveOperation')
            ->once()
            ->with('price_drop_notification', Mockery::on(function ($passedUser) use ($user) {
                return $passedUser instanceof \App\Models\User && $passedUser->id === $user->id;
            }), Mockery::on(function ($data) use ($product, $oldPrice, $newPrice, $targetPrice) {
                return is_array($data) &&
                    $data['product_id'] === $product->id &&
                    $data['old_price'] === $oldPrice &&
                    $data['new_price'] === $newPrice &&
                    $data['target_price'] === $targetPrice;
            }));

        // Act
        $this->service->sendPriceDropNotification($product, $oldPrice, $newPrice);

        // Assert
        Notification::assertSentTo($user, PriceDropNotification::class);
    }
}
