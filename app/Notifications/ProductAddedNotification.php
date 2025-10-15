<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductAddedNotification extends Notification
{
    use Queueable;

    protected \App\Models\Product $product;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(\App\Models\Product $product)
    {
        $this->product = $product;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return (int|string)[]
     *
     * @psalm-return array{product_id: int, product_name: string}
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
        ];
    }
}
