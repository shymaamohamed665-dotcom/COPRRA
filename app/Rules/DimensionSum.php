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

    /**
     * @param  array<int, int>  $value
     */
    public function passes(array $value): bool
    {
        if (! is_array($value) || count($value) !== 3) {
            return false;
        }

        $total = array_sum($value);

        return $total <= $this->maxSum;
    }

    public function message(): string
    {
        return 'The sum of dimensions cannot exceed '.$this->maxSum.' cm.';
    }
}
