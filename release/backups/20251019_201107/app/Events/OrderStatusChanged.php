<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderStatusChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Order $order,
        public OrderStatus $oldStatus,
        public OrderStatus $newStatus
    ) {
    }
}
