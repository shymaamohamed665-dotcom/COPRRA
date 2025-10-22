<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PriceAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PriceAlert $alert,
        public float $currentPrice
    ) {
    }

    /**
     * @return array{0: 'mail'}
     * @SuppressWarnings("UnusedFormalParameter")
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @SuppressWarnings("UnusedFormalParameter")
     */
    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->alert->product;
        $productName = $product ? (string) ($product->name ?? 'Product') : 'Product';

        return (new MailMessage())
            ->subject('Price Alert Reached: '.$productName)
            ->greeting('Hello!')
            ->line('Your price alert has been triggered.')
            ->line('Product: '.$productName)
            ->line('Current Price: '.number_format($this->currentPrice, 2))
            ->line('Target Price: '.number_format((float) $this->alert->target_price, 2))
            ->action('View Product', method_exists($product, 'getAttribute') ? (string) ($product->getAttribute('url') ?? url('/')) : url('/'))
            ->line('You are receiving this because you set a price alert.');
    }

    /**
     * @return array{product_id?: int, current_price: float, target_price: float}
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->alert->product_id,
            'current_price' => $this->currentPrice,
            'target_price' => (float) $this->alert->target_price,
        ];
    }
}
