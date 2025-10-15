<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class ReviewNotification extends Mailable implements ShouldQueue
{
    use Queueable;

    protected \App\Models\Product $product;

    protected \App\Models\User $reviewer;

    protected int|float $rating;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(\App\Models\Product $product, \App\Models\User $reviewer, int|float $rating)
    {
        $this->product = $product;
        $this->reviewer = $reviewer;
        $this->rating = $rating;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return (float|int)[]
     *
     * @psalm-return array{product_id: int, reviewer_id: int, rating: float|int}
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'reviewer_id' => $this->reviewer->id,
            'rating' => $this->rating,
        ];
    }
}
