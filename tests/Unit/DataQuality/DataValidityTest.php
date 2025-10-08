<?php

namespace Tests\Unit\DataQuality;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DataValidityTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_email_format_validity(): void
    {
        $validUser = User::factory()->create([
            'email' => 'test@example.com',
        ]);
        $this->assertTrue($validUser->exists());

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create([
            'email' => 'invalid-email',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_phone_number_format(): void
    {
        $validUser = User::factory()->create([
            'phone' => '+1234567890',
        ]);
        $this->assertTrue($validUser->exists());

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create([
            'phone' => 'invalid-phone',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_order_date_format(): void
    {
        $validOrder = Order::factory()->create([
            'order_date' => '2023-12-25 10:00:00',
        ]);
        $this->assertTrue($validOrder->exists());

        $this->expectException(\Illuminate\Database\QueryException::class);
        Order::factory()->create([
            'order_date' => 'invalid-date',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
