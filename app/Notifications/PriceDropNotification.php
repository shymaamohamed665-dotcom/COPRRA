<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PriceDropNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product,
        public float $oldPrice,
        public float $newPrice,
        public float $targetPrice
    ) {}

    /**
     * @return (float|int)[]
     *
     * @psalm-return array{product_id: int, old_price: float, new_price: float, target_price: float}
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'old_price' => $this->oldPrice,
            'new_price' => $this->newPrice,
            'target_price' => $this->targetPrice,
        ];
    }

    /**
     * Determine notification delivery channels.
     *
     * @return string[]
     */
    public function via($notifiable): array
    {
        // Use database channel for testing; Notification::fake intercepts delivery
        return ['database'];
    }
}
