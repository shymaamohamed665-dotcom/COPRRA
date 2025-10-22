<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;

/**
 * Contract for simple validation rules used in unit tests.
 */
interface RuleValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void;
}
