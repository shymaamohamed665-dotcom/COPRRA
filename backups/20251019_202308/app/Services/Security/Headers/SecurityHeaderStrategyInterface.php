<?php

declare(strict_types=1);

namespace App\Services\Security\Headers;

use Illuminate\Http\Request;

interface SecurityHeaderStrategyInterface
{
    /**
     * Get the header name.
     */
    public function getHeaderName(): string;

    /**
     * Get the header value based on configuration and request.
     */
    public function getValue(Request $request, array $config): ?string;

    /**
     * Check if this header should be applied.
     */
    public function shouldApply(Request $request, array $config): bool;

    /**
     * Check if this header supports dynamic values.
     */
    public function supportsDynamicValues(): bool;
}
