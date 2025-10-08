<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Http\Request;

/**
 * Service for handling API utilities and information
 */
class ApiInfoService
{
    /**
     * Get API version from request
     */
    public function getApiVersion(): string
    {
        return '2.0';
    }

    /**
     * Check API version compatibility
     */
    public function checkApiVersion(): bool
    {
        return true; // v2 is always compatible with itself
    }

    /**
     * Enhanced API documentation URL for v2
     */
    public function getApiDocumentationUrl(): string
    {
        return url('/api/v2/documentation');
    }

    /**
     * Get API changelog URL for v2
     */
    public function getApiChangelogUrl(): string
    {
        return url('/api/v2/changelog');
    }

    /**
     * Get API migration guide URL
     */
    public function getApiMigrationGuideUrl(): string
    {
        return url('/api/v2/migration-guide');
    }

    /**
     * Get API deprecation notices
     *
     * @return array<string, string>
     */
    public function getApiDeprecationNotices(): array
    {
        return [
            'v1_endpoint' => 'Some v1 endpoints will be deprecated in v3.0',
            'migration_guide' => $this->getApiMigrationGuideUrl(),
        ];
    }

    /**
     * Enhanced logging for v2
     */
    public function logApiRequest(Request $request): void
    {
        $requestParameterService = app(RequestParameterService::class);

        // Add v2 specific logging
        \Log::info('API v2 Request', [
            'version' => '2.0',
            'include' => $requestParameterService->getIncludeParams($request),
            'fields' => $requestParameterService->getFieldsParams($request),
        ]);
    }
}
