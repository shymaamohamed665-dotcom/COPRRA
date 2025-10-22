<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DimensionSum implements Rule
{
    private readonly int $maxSum;

    public function __construct(int $maxSum = 2000)
    {
        $this->maxSum = $maxSum;
    }

    #[\Override]
    public function passes(mixed $attribute, mixed $value): bool
    {
        // تأكيد استخدام معامل $attribute لتجنب عدم استعماله ولتعزيز التحقق
        if (! is_string($attribute) || $attribute === '') {
            return false;
        }

        if (! is_array($value) || count($value) !== 3) {
            return false;
        }

        // Support both indexed and associative arrays (length, width, height)
        $values = $value;
        if (! array_is_list($value)) {
            $values = [
                $value['length'] ?? null,
                $value['width'] ?? null,
                $value['height'] ?? null,
            ];
        }

        // Ensure all values are numeric
        foreach ($values as $val) {
            if (! is_numeric($val)) {
                return false;
            }
        }

        $total = array_sum(array_map(static fn ($val): float => (float) $val, $values));

        return $total <= $this->maxSum;
    }

    #[\Override]
    public function message(): string
    {
        return 'The sum of dimensions cannot exceed '.$this->maxSum.' cm.';
    }
}
