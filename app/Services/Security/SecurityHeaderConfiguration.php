<?php

declare(strict_types=1);

namespace App\Services\Security;

class SecurityHeaderConfiguration
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $headers;

    public function __construct()
    {
        $this->headers = $this->getDefaultConfiguration();
    }

    /**
     * Get all header configurations.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get configuration for a specific header.
     */
    public function getHeaderConfig(string $header): ?array
    {
        return $this->headers[$header] ?? null;
    }

    /**
     * Add or update a header configuration.
     */
    public function setHeaderConfig(string $header, array $config): void
    {
        $this->headers[$header] = array_merge($this->getDefaultHeaderConfig(), $config);
    }

    /**
     * Remove a header configuration.
     */
    public function removeHeaderConfig(string $header): void
    {
        unset($this->headers[$header]);
    }

    /**
     * Load configuration from Laravel config files.
     */
    public function loadFromConfig(): void
    {
        $configHeaders = config('security.headers', []);

        foreach ($configHeaders as $header => $value) {
            if (is_string($value)) {
                $this->setHeaderConfig($header, ['value' => $value]);
            } elseif (is_array($value)) {
                $this->setHeaderConfig($header, $value);
            }
        }
    }

    /**
     * Enable a header.
     */
    public function enableHeader(string $header): void
    {
        if (isset($this->headers[$header])) {
            $this->headers[$header]['enabled'] = true;
        }
    }

    /**
     * Disable a header.
     */
    public function disableHeader(string $header): void
    {
        if (isset($this->headers[$header])) {
            $this->headers[$header]['enabled'] = false;
        }
    }

    /**
     * Check if a header is enabled.
     */
    public function isHeaderEnabled(string $header): bool
    {
        return $this->headers[$header]['enabled'] ?? false;
    }

    /**
     * Get the default header configuration structure.
     */
    private function getDefaultHeaderConfig(): array
    {
        return [
            'enabled' => true,
            'value' => null,
            'dynamic' => false,
            'conditions' => [],
            'route_overrides' => [],
        ];
    }

    /**
     * Get the default security header configurations.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getDefaultConfiguration(): array
    {
        return [
            'X-Frame-Options' => [
                'enabled' => true,
                'value' => 'SAMEORIGIN',
                'route_overrides' => [
                    'admin/*' => 'DENY',
                    'settings/*' => 'DENY',
                    'profile/*' => 'DENY',
                    'billing/*' => 'DENY',
                    'api/*/admin/*' => 'DENY',
                ],
            ],
            'X-Content-Type-Options' => [
                'enabled' => true,
                'value' => 'nosniff',
            ],
            'X-XSS-Protection' => [
                'enabled' => true,
                'value' => '1; mode=block',
            ],
            'Referrer-Policy' => [
                'enabled' => true,
                'value' => 'strict-origin-when-cross-origin',
            ],
            'X-Permitted-Cross-Domain-Policies' => [
                'enabled' => true,
                'value' => 'none',
            ],
            'Cross-Origin-Embedder-Policy' => [
                'enabled' => true,
                'value' => 'require-corp',
            ],
            'Cross-Origin-Opener-Policy' => [
                'enabled' => true,
                'value' => 'same-origin',
            ],
            'Cross-Origin-Resource-Policy' => [
                'enabled' => true,
                'value' => 'same-origin',
            ],
            'Content-Security-Policy' => [
                'enabled' => true,
                'dynamic' => true,
                'value' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
            ],
            'Strict-Transport-Security' => [
                'enabled' => true,
                'dynamic' => true,
                'conditions' => [
                    'https_only' => true,
                ],
            ],
            'Permissions-Policy' => [
                'enabled' => true,
                'dynamic' => true,
                'value' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
            ],
        ];
    }
}
