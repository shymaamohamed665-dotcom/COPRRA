<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\OrderStatus;
use App\Models\Order;

final class OrderHelper
{
    /**
     * Get order status badge HTML.
     */
    public static function getStatusBadge(OrderStatus $status): string
    {
        $color = $status->color();
        $label = $status->label();

        return sprintf(
            '<span class="badge badge-%s">%s</span>',
            htmlspecialchars($color, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Calculate order total with tax and shipping.
     *
     * @param  array<string, float|int|string>  $data
     */
    public static function calculateTotal(array $data): float
    {
        $subtotal = self::getNumericValue($data, 'subtotal');
        $taxAmount = self::getNumericValue($data, 'tax_amount');
        $shippingAmount = self::getNumericValue($data, 'shipping_amount');
        $discountAmount = self::getNumericValue($data, 'discount_amount');

        return max(0.0, $subtotal + $taxAmount + $shippingAmount - $discountAmount);
    }

    /**
     * Calculate tax amount based on subtotal.
     */
    public static function calculateTax(float $subtotal, float $taxRate = 0.15): float
    {
        return round($subtotal * $taxRate, 2);
    }

    /**
     * Generate unique order number.
     */
    public static function generateOrderNumber(): string
    {
        return 'ORD-'.strtoupper(uniqid()).'-'.time();
    }

    /**
     * Check if order can be cancelled.
     */
    public static function canBeCancelled(Order $order): bool
    {
        return in_array(
            $order->status,
            [OrderStatus::PENDING, OrderStatus::PROCESSING],
            true
        );
    }

    /**
     * Check if order can be refunded.
     */
    public static function canBeRefunded(Order $order): bool
    {
        return $order->status === OrderStatus::DELIVERED;
    }

    /**
     * Get order progress percentage.
     */
    public static function getProgressPercentage(OrderStatus $status): int
    {
        return match ($status) {
            OrderStatus::PENDING => 0,
            OrderStatus::PROCESSING => 25,
            OrderStatus::SHIPPED => 50,
            OrderStatus::DELIVERED => 100,
            OrderStatus::CANCELLED, OrderStatus::REFUNDED => 0,
        };
    }

    /**
     * Format order total for display.
     */
    public static function formatTotal(float $amount, string $currency = 'USD'): string
    {
        $symbol = self::getCurrencySymbol($currency);

        return $symbol.' '.number_format($amount, 2);
    }

    /**
     * Get estimated delivery date.
     */
    public static function getEstimatedDeliveryDate(Order $order): ?\DateTimeInterface
    {
        if ($order->status === OrderStatus::SHIPPED && $order->shipped_at) {
            return $order->shipped_at->addDays(3);
        }

        if ($order->status === OrderStatus::PROCESSING) {
            return now()->addDays(5);
        }

        return null;
    }

    /**
     * Check if order is overdue.
     */
    public static function isOverdue(Order $order): bool
    {
        if ($order->status !== OrderStatus::SHIPPED) {
            return false;
        }

        $estimatedDelivery = self::getEstimatedDeliveryDate($order);

        return $estimatedDelivery && now()->isAfter($estimatedDelivery);
    }

    /**
     * Get numeric value from array with default.
     *
     * @param  array<string, float|int|string>  $data
     */
    private static function getNumericValue(array $data, string $key, float $default = 0.0): float
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (float) $data[$key] : $default;
    }

    /**
     * Get currency symbol for given currency code.
     */
    private static function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'SAR' => 'ر.س',
            'AED' => 'د.إ',
            'EGP' => 'ج.م',
        ];

        return $symbols[$currency] ?? $currency;
    }
}
