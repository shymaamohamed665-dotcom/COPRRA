<?php

declare(strict_types=1);

namespace App\Services\Security\Headers;

use Illuminate\Http\Request;

class StandardSecurityHeaderStrategy implements SecurityHeaderStrategyInterface
{
    private readonly string $headerName;

    private readonly string $defaultValue;

    public function __construct(string $headerName, string $defaultValue)
    {
        $this->headerName = $headerName;
        $this->defaultValue = $defaultValue;
    }

    #[\Override]
    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    #[\Override]
    public function getValue(Request $request, array $config): ?string
    {
        // Check for route-specific overrides
        if (isset($config['route_overrides'])) {
            foreach ($config['route_overrides'] as $pattern => $overrideValue) {
                if ($request->is($pattern)) {
                    return $overrideValue;
                }
            }
        }

        return $config['value'] ?? $this->defaultValue;
    }

    #[\Override]
    public function shouldApply(Request $request, array $config): bool
    {
        // Check if header is enabled
        if (! ($config['enabled'] ?? true)) {
            return false;
        }

        // Check conditional requirements
        if (isset($config['conditions'])) {
            foreach ($config['conditions'] as $condition => $required) {
                if (! $this->checkCondition($condition, $required, $request)) {
                    return false;
                }
            }
        }

        return true;
    }

    #[\Override]
    public function supportsDynamicValues(): bool
    {
        return false;
    }

    /**
     * Check a condition for header application.
     */
    private function checkCondition(string $condition, $required, Request $request): bool
    {
        return match ($condition) {
            'https_only' => $required ? $request->isSecure() : true,
            'environment' => $this->checkEnvironmentCondition($required),
            'route_pattern' => $this->checkRoutePattern($required, $request),
            default => true,
        };
    }

    /**
     * Check environment condition.
     */
    private function checkEnvironmentCondition($environments): bool
    {
        if (! is_array($environments)) {
            $environments = [$environments];
        }

        return in_array(app()->environment(), $environments, true);
    }

    /**
     * Check route pattern condition.
     */
    private function checkRoutePattern($patterns, Request $request): bool
    {
        if (! is_array($patterns)) {
            $patterns = [$patterns];
        }

        foreach ($patterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
