<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // ... existing code ...
    // ... existing code ...
}
