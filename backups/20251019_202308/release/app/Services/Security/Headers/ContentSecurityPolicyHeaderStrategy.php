<?php

declare(strict_types=1);

namespace App\Services\Security\Headers;

use Illuminate\Http\Request;

class ContentSecurityPolicyHeaderStrategy implements SecurityHeaderStrategyInterface
{
    /**
     * @psalm-return 'Content-Security-Policy'
     */
    #[\Override]
    public function getHeaderName(): string
    {
        return 'Content-Security-Policy';
    }

    #[\Override]
    public function getValue(Request $request, array $config): ?string
    {
        // Prefer configuration resolved and normalized via SecurityHeaderConfiguration
        return $config['value'] ?? "default-src 'self'; script-src 'self'; style-src 'self';";
    }

    #[\Override]
    public function shouldApply(Request $request, array $config): bool
    {
        return $config['enabled'] ?? true;
    }

    #[\Override]
    public function supportsDynamicValues(): bool
    {
        return true;
    }
}
