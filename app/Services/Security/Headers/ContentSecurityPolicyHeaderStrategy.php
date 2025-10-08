<?php

declare(strict_types=1);

namespace App\Services\Security\Headers;

use Illuminate\Http\Request;

class ContentSecurityPolicyHeaderStrategy implements SecurityHeaderStrategyInterface
{
    public function getHeaderName(): string
    {
        return 'Content-Security-Policy';
    }

    public function getValue(Request $request, array $config): ?string
    {
        return config(
            'security.headers.Content-Security-Policy',
            $config['value'] ?? "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';"
        );
    }

    public function shouldApply(Request $request, array $config): bool
    {
        return $config['enabled'] ?? true;
    }

    public function supportsDynamicValues(): bool
    {
        return true;
    }
}
