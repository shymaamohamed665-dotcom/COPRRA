<?php

declare(strict_types=1);

namespace App\Services\Security\Headers;

use Illuminate\Http\Request;

class PermissionsPolicyHeaderStrategy implements SecurityHeaderStrategyInterface
{
    /**
     * @psalm-return 'Permissions-Policy'
     */
    #[\Override]
    public function getHeaderName(): string
    {
        return 'Permissions-Policy';
    }

    #[\Override]
    public function getValue(Request $request, array $config): ?string
    {
        return config(
            'security.headers.Permissions-Policy',
            $config['value'] ?? 'camera=(), microphone=(), geolocation=()'
        );
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
