<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_enum_has_all_expected_cases(): void
    {
        $cases = OrderStatus::cases();

        $this->assertCount(6, $cases);
        $this->assertContains(OrderStatus::PENDING, $cases);
        $this->assertContains(OrderStatus::PROCESSING, $cases);
        $this->assertContains(OrderStatus::SHIPPED, $cases);
        $this->assertContains(OrderStatus::DELIVERED, $cases);
        $this->assertContains(OrderStatus::CANCELLED, $cases);
        $this->assertContains(OrderStatus::REFUNDED, $cases);
    }

    public function test_enum_values_are_correct(): void
    {
        $this->assertEquals('pending', OrderStatus::PENDING->value);
        $this->assertEquals('processing', OrderStatus::PROCESSING->value);
        $this->assertEquals('shipped', OrderStatus::SHIPPED->value);
        $this->assertEquals('delivered', OrderStatus::DELIVERED->value);
        $this->assertEquals('cancelled', OrderStatus::CANCELLED->value);
        $this->assertEquals('refunded', OrderStatus::REFUNDED->value);
    }

    public function test_label_returns_correct_arabic_text(): void
    {
        $this->assertEquals('قيد الانتظار', OrderStatus::PENDING->label());
        $this->assertEquals('قيد المعالجة', OrderStatus::PROCESSING->label());
        $this->assertEquals('تم الشحن', OrderStatus::SHIPPED->label());
        $this->assertEquals('تم التسليم', OrderStatus::DELIVERED->label());
        $this->assertEquals('ملغي', OrderStatus::CANCELLED->label());
        $this->assertEquals('مسترد', OrderStatus::REFUNDED->label());
    }

    public function test_color_returns_correct_values(): void
    {
        $this->assertEquals('yellow', OrderStatus::PENDING->color());
        $this->assertEquals('blue', OrderStatus::PROCESSING->color());
        $this->assertEquals('purple', OrderStatus::SHIPPED->color());
        $this->assertEquals('green', OrderStatus::DELIVERED->color());
        $this->assertEquals('red', OrderStatus::CANCELLED->color());
        $this->assertEquals('orange', OrderStatus::REFUNDED->color());
    }

    public function test_allowed_transitions_from_pending(): void
    {
        $transitions = OrderStatus::PENDING->allowedTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(OrderStatus::PROCESSING, $transitions);
        $this->assertContains(OrderStatus::CANCELLED, $transitions);
    }

    public function test_allowed_transitions_from_processing(): void
    {
        $transitions = OrderStatus::PROCESSING->allowedTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(OrderStatus::SHIPPED, $transitions);
        $this->assertContains(OrderStatus::CANCELLED, $transitions);
    }

    public function test_allowed_transitions_from_shipped(): void
    {
        $transitions = OrderStatus::SHIPPED->allowedTransitions();

        $this->assertCount(1, $transitions);
        $this->assertContains(OrderStatus::DELIVERED, $transitions);
    }

    public function test_no_transitions_from_final_statuses(): void
    {
        $this->assertEmpty(OrderStatus::DELIVERED->allowedTransitions());
        $this->assertEmpty(OrderStatus::CANCELLED->allowedTransitions());
        $this->assertEmpty(OrderStatus::REFUNDED->allowedTransitions());
    }

    public function test_can_transition_to_allowed_status(): void
    {
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::PROCESSING));
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CANCELLED));
        $this->assertTrue(OrderStatus::PROCESSING->canTransitionTo(OrderStatus::SHIPPED));
        $this->assertTrue(OrderStatus::SHIPPED->canTransitionTo(OrderStatus::DELIVERED));
    }

    public function test_cannot_transition_to_disallowed_status(): void
    {
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::SHIPPED));
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::DELIVERED));
        $this->assertFalse(OrderStatus::PROCESSING->canTransitionTo(OrderStatus::DELIVERED));
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::PENDING));
    }

    public function test_to_array_returns_correct_format(): void
    {
        $array = OrderStatus::toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('PENDING', $array);
        $this->assertEquals('pending', $array['PENDING']);
        $this->assertArrayHasKey('PROCESSING', $array);
        $this->assertEquals('processing', $array['PROCESSING']);
    }

    public function test_options_returns_value_label_pairs(): void
    {
        $options = OrderStatus::options();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('pending', $options);
        $this->assertEquals('قيد الانتظار', $options['pending']);
        $this->assertArrayHasKey('processing', $options);
        $this->assertEquals('قيد المعالجة', $options['processing']);
    }

    public function test_can_create_from_string(): void
    {
        $status = OrderStatus::from('pending');
        $this->assertEquals(OrderStatus::PENDING, $status);

        $status = OrderStatus::from('shipped');
        $this->assertEquals(OrderStatus::SHIPPED, $status);
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $status = OrderStatus::tryFrom('invalid');
        $this->assertNull($status);
    }

    public function test_from_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        OrderStatus::from('invalid');
    }
}
