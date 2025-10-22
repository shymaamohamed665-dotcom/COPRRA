<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    protected string $title;

    protected string $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $title, string $message)
    {
        $this->title = $title;
        $this->message = $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string>
     *
     * @psalm-return array{title: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
