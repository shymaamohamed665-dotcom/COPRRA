<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $newStatus = $event->newStatus;

        $message = $this->getNotificationMessage($newStatus, $order->order_number);

        Notification::create([
            'user_id' => $order->user_id,
            'type' => 'order_status',
            'title' => 'تحديث حالة الطلب',
            'message' => $message,
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'old_status' => $event->oldStatus->value,
                'new_status' => $newStatus->value,
            ],
            'status' => 'pending',
        ]);
    }

    /**
     * Get notification message based on status.
     */
    private function getNotificationMessage(OrderStatus $status, string $orderNumber): string
    {
        return match ($status) {
            OrderStatus::PENDING => "تم استلام طلبك رقم {$orderNumber} وهو قيد المراجعة.",
            OrderStatus::PROCESSING => "طلبك رقم {$orderNumber} قيد المعالجة الآن.",
            OrderStatus::SHIPPED => "تم شحن طلبك رقم {$orderNumber}. سيصلك قريباً!",
            OrderStatus::DELIVERED => "تم تسليم طلبك رقم {$orderNumber} بنجاح. نتمنى أن تكون راضياً عن الخدمة!",
            OrderStatus::CANCELLED => "تم إلغاء طلبك رقم {$orderNumber}.",
            OrderStatus::REFUNDED => "تم استرداد قيمة طلبك رقم {$orderNumber}.",
        };
    }
}
