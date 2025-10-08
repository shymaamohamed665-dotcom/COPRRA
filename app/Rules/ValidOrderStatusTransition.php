<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Rules;

use App\Enums\OrderStatus;
use App\Models\Order;
use Closure;

final class ValidOrderStatusTransition implements RuleValidationRule
{
    public function __construct(private readonly Order $order) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        try {
            $newStatus = OrderStatus::from($value);
        } catch (\ValueError) {
            $fail('The :attribute must be a valid order status.');

            return;
        }

        if ($this->order->status instanceof \App\Enums\OrderStatus) {
            if (! $this->order->status->canTransitionTo($newStatus)) {
                $currentLabel = $this->order->status->label();
                $newLabel = $newStatus->label();
                $fail("Cannot transition from {$currentLabel} to {$newLabel}.");
            }
        }
    }
}
