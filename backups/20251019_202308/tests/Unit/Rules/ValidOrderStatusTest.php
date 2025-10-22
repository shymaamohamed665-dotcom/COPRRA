<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\ValidOrderStatus;
use PHPUnit\Framework\TestCase;

class ValidOrderStatusTest extends TestCase
{
    private ValidOrderStatus $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new ValidOrderStatus;
    }

    public function test_passes_with_valid_status(): void
    {
        $failCalled = false;
        $fail = function () use (&$failCalled) {
            $failCalled = true;
        };

        $this->rule->validate('status', 'pending', $fail);

        $this->assertFalse($failCalled);
    }

    public function test_fails_with_invalid_status(): void
    {
        $failCalled = false;
        $fail = function () use (&$failCalled) {
            $failCalled = true;
        };

        $this->rule->validate('status', 'invalid_status', $fail);

        $this->assertTrue($failCalled);
    }

    public function test_fails_with_non_string_value(): void
    {
        $failCalled = false;
        $fail = function () use (&$failCalled) {
            $failCalled = true;
        };

        $this->rule->validate('status', 123, $fail);

        $this->assertTrue($failCalled);
    }

    public function test_passes_with_all_valid_statuses(): void
    {
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

        foreach ($validStatuses as $status) {
            $failCalled = false;
            $fail = function () use (&$failCalled) {
                $failCalled = true;
            };

            $this->rule->validate('status', $status, $fail);

            $this->assertFalse($failCalled, "Status '{$status}' should be valid");
        }
    }
}
