<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Rules;

use App\Enums\OrderStatus;
use Closure;

final class ValidOrderStatus implements RuleValidationRule
{
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

        $validStatuses = array_column(OrderStatus::cases(), 'value');

        if (! in_array($value, $validStatuses, true)) {
            $fail('The :attribute must be a valid order status.');
        }
    }
}
