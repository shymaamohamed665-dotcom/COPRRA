<?php

declare(strict_types=1);

namespace App\Services;

final class CallbackService
{
    public function processWithCallback(callable $callback): mixed
    {
        return $callback();
    }
}
