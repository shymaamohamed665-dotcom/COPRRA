<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersService
{
    private SecurityHeaderConfiguration $configuration;

    public function __construct(SecurityHeaderConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Apply security headers to the response.
     */
    public function applySecurityHeaders(Response $response, Request $request): void
    {
        $headers = $this->configuration->getHeaders();

        foreach ($headers as $header => $config) {
            if ($this->shouldApplyHeader($header, $config, $request)) {
                $value = $this->getHeaderValue($header, $config, $request);
                if ($value !== null) {
                    $response->headers->set($header, $value);
                }
            }
        }
    }

    /**
     * Determine if a header should be applied based on its configuration.
     */
    private function shouldApplyHeader(string $header, array $config, Request $request): bool
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

    /**
     * Get the value for a header based on its configuration.
     */
    private function getHeaderValue(string $header, array $config, Request $request): ?string
    {
        $value = $config['value'] ?? null;

        // Handle route-specific overrides
        if (isset($config['route_overrides'])) {
            foreach ($config['route_overrides'] as $pattern => $overrideValue) {
                if ($request->is($pattern)) {
                    $value = $overrideValue;
                    break;
                }
            }
        }

        // Handle dynamic values
        if (isset($config['dynamic']) && $config['dynamic'] === true) {
            $value = $this->getDynamicValue($header, $request);
        }

        return is_string($value) ? $value : null;
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

    /**
     * Get dynamic value for specific headers.
     */
    private function getDynamicValue(string $header, Request $request): ?string
    {
        return match ($header) {
            'Content-Security-Policy' => $this->getContentSecurityPolicy(),
            'Strict-Transport-Security' => $this->getStrictTransportSecurity(),
            'Permissions-Policy' => $this->getPermissionsPolicy(),
            default => null,
        };
    }

    /**
     * Get Content Security Policy value.
     */
    private function getContentSecurityPolicy(): string
    {
        return config(
            'security.headers.Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';"
        );
    }

    /**
     * Get Strict Transport Security value.
     */
    private function getStrictTransportSecurity(): string
    {
        return config(
            'security.headers.Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload'
        );
    }

    /**
     * Get Permissions Policy value.
     */
    private function getPermissionsPolicy(): string
    {
        return config(
            'security.headers.Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), interest-cohort=()'
        );
    }
}
