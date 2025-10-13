<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiRequest
{
    public function handle(Request $request, Closure $next, ?string $rules = null): Response
    {
        if ($this->shouldSkipValidation($rules)) {
            return $next($request);
        }

        $validationRules = $this->getValidationRules($rules);
        if (! $validationRules) {
            return $next($request);
        }

        return $this->validateRequest($request, $next, $validationRules);
    }

    private function shouldSkipValidation(?string $rules): bool
    {
        return ! $rules;
    }

    /**
     * @param  array<string, string>  $rules
     */
    private function validateRequest(Request $request, Closure $next, array $rules): Response
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        return $next($request);
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function getValidationRules(string $rules): array
    {
        $configRules = config("validation.rules.{$rules}", []);

        if (is_string($configRules)) {
            return $this->parseJsonRules($configRules);
        }

        if (is_array($configRules)) {
            return $this->normalizeRules($configRules);
        }

        return [];
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function parseJsonRules(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $this->normalizeRules($decoded) : [];
    }

    /**
     * @param  array<string, string|array<int, string>>  $rules
     * @return array<string, string|array<int, string>>
     */
    private function normalizeRules(array $rules): array
    {
        return array_map(/**
         * @return string|string[]
         *
         * @psalm-return array<int, string>|string
         */
            static function ($value): array|string|array {
                if (is_array($value)) {
                    return array_map(strval(...), $value);
                }

                return is_string($value) || is_numeric($value) ? (string) $value : '';
            }, $rules);
    }
}
