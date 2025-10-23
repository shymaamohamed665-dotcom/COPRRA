<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\OrderStatus;
use App\Helpers\OrderHelper;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_status_badge_returns_correct_html(): void
    {
        $badge = OrderHelper::getStatusBadge(OrderStatus::PENDING);

        $this->assertStringContainsString('badge', $badge);
        $this->assertStringContainsString('yellow', $badge);
        $this->assertStringContainsString('قيد الانتظار', $badge);
    }

    public function test_calculate_total_with_all_values(): void
    {
        $data = [
            'subtotal' => 100.00,
            'tax_amount' => 15.00,
            'shipping_amount' => 10.00,
            'discount_amount' => 5.00,
        ];

        $total = OrderHelper::calculateTotal($data);

        $this->assertEquals(120.00, $total);
    }

    public function test_calculate_total_with_missing_values(): void
    {
        $data = ['subtotal' => 100.00];

        $total = OrderHelper::calculateTotal($data);

        $this->assertEquals(100.00, $total);
    }

    public function test_calculate_total_returns_zero_for_negative_result(): void
    {
        $data = [
            'subtotal' => 50.00,
            'discount_amount' => 100.00,
        ];

        $total = OrderHelper::calculateTotal($data);

        $this->assertEquals(0.00, $total);
    }

    public function test_calculate_tax_with_default_rate(): void
    {
        $tax = OrderHelper::calculateTax(100.00);

        $this->assertEquals(15.00, $tax);
    }

    public function test_calculate_tax_with_custom_rate(): void
    {
        $tax = OrderHelper::calculateTax(100.00, 0.20);

        $this->assertEquals(20.00, $tax);
    }

    public function test_generate_order_number_is_unique(): void
    {
        $number1 = OrderHelper::generateOrderNumber();
        $number2 = OrderHelper::generateOrderNumber();

        $this->assertNotEquals($number1, $number2);
        $this->assertStringStartsWith('ORD-', $number1);
        $this->assertStringStartsWith('ORD-', $number2);
    }

    public function test_can_be_cancelled_returns_true_for_pending(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        $this->assertTrue(OrderHelper::canBeCancelled($order));
    }

    public function test_can_be_cancelled_returns_true_for_processing(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PROCESSING]);

        $this->assertTrue(OrderHelper::canBeCancelled($order));
    }

    public function test_can_be_cancelled_returns_false_for_shipped(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::SHIPPED]);

        $this->assertFalse(OrderHelper::canBeCancelled($order));
    }

    public function test_can_be_refunded_returns_true_for_delivered(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::DELIVERED]);

        $this->assertTrue(OrderHelper::canBeRefunded($order));
    }

    public function test_can_be_refunded_returns_false_for_pending(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        $this->assertFalse(OrderHelper::canBeRefunded($order));
    }

    public function test_get_progress_percentage_for_all_statuses(): void
    {
        $this->assertEquals(0, OrderHelper::getProgressPercentage(OrderStatus::PENDING));
        $this->assertEquals(25, OrderHelper::getProgressPercentage(OrderStatus::PROCESSING));
        $this->assertEquals(50, OrderHelper::getProgressPercentage(OrderStatus::SHIPPED));
        $this->assertEquals(100, OrderHelper::getProgressPercentage(OrderStatus::DELIVERED));
        $this->assertEquals(0, OrderHelper::getProgressPercentage(OrderStatus::CANCELLED));
        $this->assertEquals(0, OrderHelper::getProgressPercentage(OrderStatus::REFUNDED));
    }

    public function test_format_total_with_default_currency(): void
    {
        $formatted = OrderHelper::formatTotal(150.50);

        $this->assertStringContainsString('$', $formatted);
        $this->assertStringContainsString('150.50', $formatted);
    }

    public function test_format_total_with_sar_currency(): void
    {
        $formatted = OrderHelper::formatTotal(150.50, 'SAR');

        $this->assertStringContainsString('ر.س', $formatted);
        $this->assertStringContainsString('150.50', $formatted);
    }

    public function test_get_estimated_delivery_date_for_shipped_order(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::SHIPPED,
            'shipped_at' => now(),
        ]);

        $estimatedDate = OrderHelper::getEstimatedDeliveryDate($order);

        $this->assertNotNull($estimatedDate);
        $this->assertEquals(now()->addDays(3)->format('Y-m-d'), $estimatedDate->format('Y-m-d'));
    }

    public function test_get_estimated_delivery_date_for_processing_order(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PROCESSING]);

        $estimatedDate = OrderHelper::getEstimatedDeliveryDate($order);

        $this->assertNotNull($estimatedDate);
        $this->assertEquals(now()->addDays(5)->format('Y-m-d'), $estimatedDate->format('Y-m-d'));
    }

    public function test_get_estimated_delivery_date_returns_null_for_pending(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        $estimatedDate = OrderHelper::getEstimatedDeliveryDate($order);

        $this->assertNull($estimatedDate);
    }

    public function test_is_overdue_returns_false_for_non_shipped_order(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        $this->assertFalse(OrderHelper::isOverdue($order));
    }

    public function test_is_overdue_returns_false_for_recent_shipment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::SHIPPED,
            'shipped_at' => now(),
        ]);

        $this->assertFalse(OrderHelper::isOverdue($order));
    }
}
