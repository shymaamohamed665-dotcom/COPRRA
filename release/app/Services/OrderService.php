<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Order Service
 *
 * Handles all order-related operations including creation, status updates, and cancellation.
 * Not marked as final to allow mocking in unit tests while maintaining production integrity.
 */
class OrderService
{
    /**
     * Create a new order
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     * @param  array<string, string>  $addresses
     */
    public function createOrder(User $user, array $cartItems, array $addresses): Order
    {
        return DB::transaction(function () use ($user, $cartItems, $addresses) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::PENDING,
                'subtotal' => $this->calculateSubtotal($cartItems),
                'tax_amount' => $this->calculateTax($cartItems),
                'shipping_amount' => $this->calculateShipping($cartItems),
                'total_amount' => 0, // Will be calculated
                'currency' => 'USD',
                'shipping_address' => $addresses['shipping'],
                'billing_address' => $addresses['billing'],
            ]);

            $totalAmount = $order->subtotal + $order->tax_amount + $order->shipping_amount;
            $order->update(['total_amount' => $totalAmount]);

            foreach ($cartItems as $item) {
                $productId = $item['product_id'] ?? null;
                if (! is_numeric($productId)) {
                    continue;
                }

                $product = Product::findOrFail((int) $productId);
                $quantity = $item['quantity'] ?? 1;
                if (! is_numeric($quantity)) {
                    $quantity = 1;
                }

                $orderItemData = [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $quantity,
                    'unit_price' => $product->price,
                    'total_price' => (float) $product->price * (int) $quantity,
                ];

                // Only include product_details if the column exists on the same connection as OrderItem
                $orderItemModel = new OrderItem();
                $schema = $orderItemModel->getConnection()->getSchemaBuilder();
                if ($schema->hasColumn($orderItemModel->getTable(), 'product_details')) {
                    $orderItemData['product_details'] = [
                        'name' => $product->name,
                        'sku' => $product->sku ?? '',
                        'image' => $product->image ?? '',
                    ];
                }

                OrderItem::create($orderItemData);
            }

            return $order;
        });
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Order $order, OrderStatus|string $status): bool
    {
        // Convert string to Enum if needed, mapping legacy alias "completed" to "delivered"
        if (is_string($status)) {
            $normalized = strtolower($status);
            if ($normalized === 'completed') {
                $normalized = OrderStatus::DELIVERED->value;
            }
            $newStatus = OrderStatus::from($normalized);
        } else {
            $newStatus = $status;
        }

        // Store old status for event (always enum via accessor)
        $oldStatus = $order->status_enum;

        // Check if transition is allowed
        if (! $oldStatus->canTransitionTo($newStatus)) {
            return false;
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === OrderStatus::SHIPPED) {
            $updateData['shipped_at'] = now();
        } elseif ($newStatus === OrderStatus::DELIVERED) {
            $updateData['delivered_at'] = now();
        }

        $updated = $order->update($updateData);

        // Fire event if status was updated
        if ($updated) {
            event(new OrderStatusChanged($order, $oldStatus, $newStatus));
        }

        return $updated;
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Order $order, ?string $reason = null): bool
    {
        // Only allow cancellation for pending or processing orders
        if (! in_array($order->status_enum, [OrderStatus::PENDING, OrderStatus::PROCESSING], true)) {
            return false;
        }

        $order->update([
            'status' => OrderStatus::CANCELLED,
            'notes' => $order->notes."\nCancelled: ".($reason ?? 'No reason provided'),
        ]);

        // Restore product stock using available column
        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product && method_exists($product, 'increment')) {
                $schema = $product->getConnection()->getSchemaBuilder();
                $table = $product->getTable();
                // Prefer stock_quantity (new schema), fallback to legacy stock
                if ($schema->hasColumn($table, 'stock_quantity')) {
                    $product->increment('stock_quantity', $item->quantity);
                } elseif ($schema->hasColumn($table, 'stock')) {
                    $product->increment('stock', $item->quantity);
                }
            }
        }

        return true;
    }

    /**
     * Get order history for user
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order>
     */
    public function getOrderHistory(User $user, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $user->orders()
            ->with(['items.product', 'payments'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.date('Y').'-'.strtoupper(Str::random(8));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Calculate order subtotal
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     */
    private function calculateSubtotal(array $cartItems): float
    {
        return collect($cartItems)->sum(static function (array $item): float|int {
            $product = Product::find($item['product_id']);

            return $product ? (float) $product->price * $item['quantity'] : 0;
        });
    }

    /**
     * Calculate order tax
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     */
    private function calculateTax(array $cartItems): float
    {
        $subtotal = $this->calculateSubtotal($cartItems);
        $taxRate = (float) config('coprra.tax.rate', 0.1);

        return $subtotal * $taxRate;
    }

    /**
     * Calculate shipping cost
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     */
    private function calculateShipping(array $cartItems): float
    {
        $subtotal = $this->calculateSubtotal($cartItems);
        $freeShippingThreshold = (float) config('coprra.shipping.free_threshold', 100);
        $standardShippingFee = (float) config('coprra.shipping.standard_fee', 10);

        return $subtotal > $freeShippingThreshold ? 0.0 : $standardShippingFee;
    }
}
