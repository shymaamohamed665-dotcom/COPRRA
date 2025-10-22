<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\UserPoint;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PointsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PointsService;
    }

    public function test_add_points_creates_user_point(): void
    {
        // Arrange
        $user = User::factory()->create();
        $points = 100;
        $type = 'earned';
        $source = 'purchase';

        // Act
        $userPoint = $this->service->addPoints($user, $points, $type, $source);

        // Assert
        $this->assertInstanceOf(UserPoint::class, $userPoint);
        $this->assertEquals($user->id, $userPoint->user_id);
        $this->assertEquals($points, $userPoint->points);
        $this->assertEquals($type, $userPoint->type);
    }

    public function test_get_available_points_returns_sum(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPoint::factory()->create(['user_id' => $user->id, 'points' => 50, 'type' => 'earned']);
        UserPoint::factory()->create(['user_id' => $user->id, 'points' => -20, 'type' => 'redeemed']);

        // Act
        $available = $this->service->getAvailablePoints($user->id);

        // Assert
        $this->assertEquals(30, $available);
    }

    public function test_redeem_points_succeeds_with_sufficient_points(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPoint::factory()->create(['user_id' => $user->id, 'points' => 100, 'type' => 'earned']);

        // Act
        $result = $this->service->redeemPoints($user, 50, 'Test redemption');

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(50, $this->service->getAvailablePoints($user->id));
    }

    public function test_redeem_points_fails_with_insufficient_points(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPoint::factory()->create(['user_id' => $user->id, 'points' => 30, 'type' => 'earned']);

        // Act
        $result = $this->service->redeemPoints($user, 50, 'Test redemption');

        // Assert
        $this->assertFalse($result);
        $this->assertEquals(30, $this->service->getAvailablePoints($user->id));
    }

    public function test_award_purchase_points_adds_points(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 100.00]);

        // Act
        $this->service->awardPurchasePoints($order);

        // Assert
        $this->assertEquals(1, $this->service->getAvailablePoints($user->id)); // 100 * 0.01 = 1
    }

    public function test_get_points_history_returns_paginated(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPoint::factory()->count(5)->create(['user_id' => $user->id]);

        // Act
        $history = $this->service->getPointsHistory($user->id, 3);

        // Assert
        $this->assertCount(3, $history);
    }
}
