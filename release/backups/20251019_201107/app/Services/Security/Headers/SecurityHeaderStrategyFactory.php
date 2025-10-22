<?php

declare(strict_types=1);

namespace App\Services\Security\Headers;

class SecurityHeaderStrategyFactory
{
    /**
     * @var array<string, SecurityHeaderStrategyInterface>
     */
    private array $strategies = [];

    public function __construct()
    {
        $this->registerDefaultStrategies();
    }

    /**
     * Get strategy for a specific header.
     */
    public function getStrategy(string $header): SecurityHeaderStrategyInterface
    {
        return $this->strategies[$header] ?? $this->createStandardStrategy($header);
    }

    /**
     * Register a custom strategy for a header.
     */
    public function registerStrategy(string $header, SecurityHeaderStrategyInterface $strategy): void
    {
        $this->strategies[$header] = $strategy;
    }

    /**
     * Register default strategies for common security headers.
     */
    private function registerDefaultStrategies(): void
    {
        // Dynamic headers
        $this->strategies['Content-Security-Policy'] = new ContentSecurityPolicyHeaderStrategy();
        $this->strategies['Strict-Transport-Security'] = new StrictTransportSecurityHeaderStrategy();
        $this->strategies['Permissions-Policy'] = new PermissionsPolicyHeaderStrategy();

        // Standard headers with defaults
        $standardHeaders = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Cross-Origin-Embedder-Policy' => 'require-corp',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];

        foreach ($standardHeaders as $header => $defaultValue) {
            $this->strategies[$header] = new StandardSecurityHeaderStrategy($header, $defaultValue);
        }
    }

    /**
     * Create a standard strategy for headers without specific implementations.
     */
    private function createStandardStrategy(string $header): StandardSecurityHeaderStrategy
    {
        return new StandardSecurityHeaderStrategy($header, '');
    }
}
