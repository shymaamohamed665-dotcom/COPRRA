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

final class OrderService
{
    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     * @param  array<string, string>  $addresses
     */
    public function createOrder(User $user, array $cartItems, array $addresses): Order
    {
        return DB::transaction(function () use ($user, $cartItems, $addresses) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'status' => 'pending',
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

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $quantity,
                    'unit_price' => $product->price,
                    'total_price' => (float) $product->price * (int) $quantity,
                    'product_details' => [
                        'name' => $product->name,
                        'sku' => $product->sku ?? '',
                        'image' => $product->image ?? '',
                    ],
                ]);
            }

            return $order;
        });
    }

    public function updateOrderStatus(Order $order, OrderStatus|string $status): bool
    {
        // Convert string to Enum if needed
        $newStatus = is_string($status) ? OrderStatus::from($status) : $status;

        // Store old status for event
        $oldStatus = $order->status;

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

    public function cancelOrder(Order $order, ?string $reason = null): bool
    {
        if (! in_array($order->status, ['pending', 'processing'])) {
            return false;
        }

        $order->update([
            'status' => 'cancelled',
            'notes' => $order->notes."\nCancelled: ".($reason ?? 'No reason provided'),
        ]);

        // Restore product stock
        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock', $item->quantity);
            }
        }

        return true;
    }

    /**
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

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.date('Y').'-'.strtoupper(Str::random(8));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     */
    private function calculateSubtotal(array $cartItems): float
    {
        return collect($cartItems)->sum(static function ($item) {
            $product = Product::find($item['product_id']);

            return $product ? (float) $product->price * $item['quantity'] : 0;
        });
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     */
    private function calculateTax(array $cartItems): float
    {
        $subtotal = $this->calculateSubtotal($cartItems);

        return $subtotal * 0.1; // 10% tax rate
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     */
    private function calculateShipping(array $cartItems): float
    {
        $subtotal = $this->calculateSubtotal($cartItems);

        return $subtotal > 100 ? 0 : 10; // Free shipping over $100
    }
}
