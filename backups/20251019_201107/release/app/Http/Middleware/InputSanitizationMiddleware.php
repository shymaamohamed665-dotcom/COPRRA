<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InputSanitizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        // Sanitize input data
        $this->sanitizeInput($request);

        $response = $next($request);

        if (! ($response instanceof \Symfony\Component\HttpFoundation\Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        // Sanitize output data
        $this->sanitizeOutput($response);

        return $response;
    }

    /**
     * Sanitize input data.
     */
    private function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);
        $request->merge($sanitized);
    }

    /**
     * Sanitize output data.
     */
    private function sanitizeOutput(Response $response): void
    {
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            if (is_array($data)) {
                $sanitized = $this->sanitizeArray($data);
                $response->setData($sanitized);
            }
        }
    }

    /**
     * Sanitize array recursively.
     *
     * @param  array<array-key, array|bool|float|int|string|null>  $data
     * @return array<array-key, array|bool|float|int|string|null>
     */
    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }

        return $data;
    }

    /**
     * Sanitize string.
     */
    private function sanitizeString(string $value): string
    {
        return $this->cleanString($value);
    }

    private function cleanString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Remove control characters except newlines and tabs
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        // Trim whitespace
        $value = trim($value ?? '');

        // Normalize line endings
        return str_replace(["\r\n", "\r"], "\n", $value);
    }
}
