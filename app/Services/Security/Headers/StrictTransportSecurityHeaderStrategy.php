<?php

declare(strict_types=1);

namespace App\Services\Security\Headers;

use Illuminate\Http\Request;

class StrictTransportSecurityHeaderStrategy implements SecurityHeaderStrategyInterface
{
    /**
     * @psalm-return 'Strict-Transport-Security'
     */
    #[\Override]
    public function getHeaderName(): string
    {
        return 'Strict-Transport-Security';
    }

    #[\Override]
    public function getValue(Request $request, array $config): ?string
    {
        if (! $request->isSecure()) {
            return null;
        }

        return config(
            'security.headers.Strict-Transport-Security',
            $config['value'] ?? 'max-age=31536000; includeSubDomains; preload'
        );
    }

    #[\Override]
    public function shouldApply(Request $request, array $config): bool
    {
        // Only apply on HTTPS requests
        if (! $request->isSecure()) {
            return false;
        }

        return $config['enabled'] ?? true;
    }

    /**
     * @return true
     */
    #[\Override]
    public function supportsDynamicValues(): bool
    {
        return true;
    }
}
