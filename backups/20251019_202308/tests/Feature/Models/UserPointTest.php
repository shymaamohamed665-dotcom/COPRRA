<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Order;
use App\Models\User;
use App\Models\UserPoint;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPointTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_user_point(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $userPoint = UserPoint::factory()->create([
            'user_id' => $user->id,
            'points' => 100,
            'type' => 'earned',
            'source' => 'purchase',
            'order_id' => $order->id,
            'description' => 'Points for purchase',
            'expires_at' => Carbon::now()->addYear(),
        ]);

        $this->assertInstanceOf(UserPoint::class, $userPoint);
        $this->assertEquals($user->id, $userPoint->user_id);
        $this->assertEquals(100, $userPoint->points);
        $this->assertEquals('earned', $userPoint->type);
        $this->assertEquals('purchase', $userPoint->source);
        $this->assertEquals($order->id, $userPoint->order_id);
        $this->assertEquals('Points for purchase', $userPoint->description);
        $this->assertInstanceOf(Carbon::class, $userPoint->expires_at);

        $this->assertDatabaseHas('user_points', [
            'user_id' => $user->id,
            'points' => 100,
            'type' => 'earned',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_casts_expires_at_to_datetime(): void
    {
        $userPoint = UserPoint::factory()->create([
            'expires_at' => '2023-12-31 23:59:59',
        ]);

        $this->assertInstanceOf(Carbon::class, $userPoint->expires_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $userPoint = UserPoint::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $userPoint->user);
        $this->assertEquals($user->id, $userPoint->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_belongs_to_order(): void
    {
        $order = Order::factory()->create();
        $userPoint = UserPoint::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $userPoint->order);
        $this->assertEquals($order->id, $userPoint->order->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_earned_scope(): void
    {
        UserPoint::factory()->create(['type' => 'earned']);
        UserPoint::factory()->create(['type' => 'redeemed']);

        $earnedPoints = UserPoint::earned()->get();

        $this->assertCount(1, $earnedPoints);
        $this->assertEquals('earned', $earnedPoints->first()->type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_redeemed_scope(): void
    {
        UserPoint::factory()->create(['type' => 'earned']);
        UserPoint::factory()->create(['type' => 'redeemed']);

        $redeemedPoints = UserPoint::redeemed()->get();

        $this->assertCount(1, $redeemedPoints);
        $this->assertEquals('redeemed', $redeemedPoints->first()->type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_valid_scope(): void
    {
        // Valid point (no expiry)
        UserPoint::factory()->create(['expires_at' => null]);

        // Valid point (future expiry)
        UserPoint::factory()->create(['expires_at' => Carbon::now()->addDay()]);

        // Expired point
        UserPoint::factory()->create(['expires_at' => Carbon::now()->subDay()]);

        $validPoints = UserPoint::valid()->get();

        $this->assertCount(2, $validPoints);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_fillable_attributes(): void
    {
        $fillable = [
            'user_id',
            'points',
            'type',
            'source',
            'order_id',
            'description',
            'expires_at',
        ];

        $this->assertEquals($fillable, (new UserPoint)->getFillable());
    }
}
