<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DimensionSum implements Rule
{
    private int $maxSum;

    public function __construct(int $maxSum = 2000)
    {
        $this->maxSum = $maxSum;
    }

    #[\Override]
    public function passes(mixed $attribute, mixed $value): bool
    {
        if (! is_array($value) || count($value) !== 3) {
            return false;
        }

        // Support both indexed and associative arrays (length, width, height)
        $values = [];
        if (array_is_list($value)) {
            $values = $value;
        } else {
            $values = [
                $value['length'] ?? null,
                $value['width'] ?? null,
                $value['height'] ?? null,
            ];
        }

        // Ensure all values are numeric
        foreach ($values as $v) {
            if (! is_numeric($v)) {
                return false;
            }
        }

        $total = array_sum(array_map(static fn ($v) => (float) $v, $values));

        return $total <= $this->maxSum;
    }

    #[\Override]
    public function message(): string
    {
        return 'The sum of dimensions cannot exceed '.$this->maxSum.' cm.';
    }
}
