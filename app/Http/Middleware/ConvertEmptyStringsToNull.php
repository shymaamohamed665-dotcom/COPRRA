<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ConvertEmptyStringsToNull
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        $input = $request->all();
        $convertedInput = $this->convertEmptyStringsToNull($input);
        $request->merge($convertedInput);

        $response = $next($request);
        if (! ($response instanceof \Symfony\Component\HttpFoundation\Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }

    /**
     * Convert empty strings to null recursively.
     */
    private function convertEmptyStringsToNull(mixed $input): mixed
    {
        if (! is_array($input)) {
            return $input;
        }

        foreach ($input as $key => $value) {
            if (is_string($value) && $value === '') {
                $input[$key] = null;
            } elseif (is_array($value)) {
                $input[$key] = $this->convertEmptyStringsToNull($value);
            }
        }

        return $input;
    }
}
