<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PriceAlert;
use App\Notifications\PriceAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendPriceAlert implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        PriceAlert::query()->where('is_active', true)->chunk(100, function ($alerts): void {
            foreach ($alerts as $alert) {
                $product = $alert->product;
                if (! $product) {
                    continue;
                }
                $currentPrice = (float) ($product->getCurrentPrice() ?? 0);
                if ($currentPrice <= (float) $alert->target_price) {
                    Notification::route('mail', $alert->user->email)
                        ->notify(new PriceAlertNotification($alert, $currentPrice));

                    if (! $alert->repeat_alert) {
                        $alert->is_active = false;
                        $alert->save();
                    }
                }
            }
        });
    }
}
