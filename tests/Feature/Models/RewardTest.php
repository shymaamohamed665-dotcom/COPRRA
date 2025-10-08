<?php

namespace Tests\Feature\Models;

use App\Models\Reward;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_reward(): void
    {
        $reward = Reward::factory()->create([
            'name' => 'Discount Coupon',
            'description' => '10% off',
            'points_required' => 100,
            'type' => 'discount',
            'value' => ['percentage' => 10],
            'is_active' => true,
            'usage_limit' => 100,
            'valid_from' => Carbon::now()->subDay(),
            'valid_until' => Carbon::now()->addDay(),
        ]);

        $this->assertInstanceOf(Reward::class, $reward);
        $this->assertEquals('Discount Coupon', $reward->name);
        $this->assertEquals('10% off', $reward->description);
        $this->assertEquals(100, $reward->points_required);
        $this->assertEquals('discount', $reward->type);
        $this->assertIsArray($reward->value);
        $this->assertTrue($reward->is_active);
        $this->assertEquals(100, $reward->usage_limit);
        $this->assertInstanceOf(Carbon::class, $reward->valid_from);
        $this->assertInstanceOf(Carbon::class, $reward->valid_until);

        $this->assertDatabaseHas('rewards', [
            'name' => 'Discount Coupon',
            'points_required' => 100,
            'type' => 'discount',
            'is_active' => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_casts_attributes_correctly(): void
    {
        $reward = Reward::factory()->create([
            'value' => ['amount' => 50],
            'is_active' => 1,
            'valid_from' => '2023-01-01 00:00:00',
            'valid_until' => '2023-12-31 23:59:59',
        ]);

        $this->assertIsArray($reward->value);
        $this->assertEquals(['amount' => 50], $reward->value);
        $this->assertIsBool($reward->is_active);
        $this->assertTrue($reward->is_active);
        $this->assertInstanceOf(Carbon::class, $reward->valid_from);
        $this->assertInstanceOf(Carbon::class, $reward->valid_until);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_active_scope(): void
    {
        // Active reward
        Reward::factory()->create([
            'is_active' => true,
            'valid_from' => Carbon::now()->subDay(),
            'valid_until' => Carbon::now()->addDay(),
        ]);

        // Inactive reward
        Reward::factory()->create(['is_active' => false]);

        // Expired reward
        Reward::factory()->create([
            'is_active' => true,
            'valid_until' => Carbon::now()->subDay(),
        ]);

        // Future reward
        Reward::factory()->create([
            'is_active' => true,
            'valid_from' => Carbon::now()->addDay(),
        ]);

        $activeRewards = Reward::active()->get();

        $this->assertCount(1, $activeRewards);
        $this->assertTrue($activeRewards->first()->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_available_for_points_scope(): void
    {
        Reward::factory()->create([
            'points_required' => 50,
            'is_active' => true,
            'valid_from' => Carbon::now()->subDay(),
            'valid_until' => Carbon::now()->addDay(),
        ]);

        Reward::factory()->create([
            'points_required' => 200,
            'is_active' => true,
            'valid_from' => Carbon::now()->subDay(),
            'valid_until' => Carbon::now()->addDay(),
        ]);

        $availableRewards = Reward::availableForPoints(100)->get();

        $this->assertCount(1, $availableRewards);
        $this->assertEquals(50, $availableRewards->first()->points_required);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_fillable_attributes(): void
    {
        $fillable = [
            'name',
            'description',
            'points_required',
            'type',
            'value',
            'is_active',
            'usage_limit',
            'valid_from',
            'valid_until',
        ];

        $this->assertEquals($fillable, (new Reward)->getFillable());
    }
}
