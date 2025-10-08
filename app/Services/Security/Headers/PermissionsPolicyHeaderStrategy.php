<?php

declare(strict_types=1);

namespace App\Services\Security\Headers;

use Illuminate\Http\Request;

class PermissionsPolicyHeaderStrategy implements SecurityHeaderStrategyInterface
{
    public function getHeaderName(): string
    {
        return 'Permissions-Policy';
    }

    public function getValue(Request $request, array $config): ?string
    {
        return config(
            'security.headers.Permissions-Policy',
            $config['value'] ?? 'camera=(), microphone=(), geolocation=(), interest-cohort=()'
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
