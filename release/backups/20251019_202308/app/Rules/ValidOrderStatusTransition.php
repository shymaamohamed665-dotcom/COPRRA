<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Rules;

use App\Enums\OrderStatus;
use App\Models\Order;
use Closure;

final readonly class ValidOrderStatusTransition implements RuleValidationRule
{
    public function __construct(private Order $order)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        // Normalize legacy alias
        $normalized = strtolower($value);
        if ($normalized === 'completed') {
            $normalized = OrderStatus::DELIVERED->value;
        }

        try {
            $newStatus = OrderStatus::from($normalized);
        } catch (\ValueError) {
            $fail('The :attribute must be a valid order status.');

            return;
        }

        $currentStatus = $this->order->status_enum;
        if (! $currentStatus->canTransitionTo($newStatus)) {
            $currentLabel = $currentStatus->label();
            $newLabel = $newStatus->label();
            $fail("Cannot transition from {$currentLabel} to {$newLabel}.");
        }
    }
}
