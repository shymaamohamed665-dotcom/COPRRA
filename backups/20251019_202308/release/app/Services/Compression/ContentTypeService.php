<?php

declare(strict_types=1);

namespace App\Services\Compression;

use Illuminate\Http\Response;

class ContentTypeService
{
    private const COMPRESSIBLE_TYPES = [
        'text/html',
        'text/css',
        'text/javascript',
        'application/javascript',
        'application/json',
        'application/xml',
        'text/xml',
    ];

    /**
     * Check if response content type is compressible
     */
    public function isCompressibleContentType(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        foreach (self::COMPRESSIBLE_TYPES as $type) {
            if (str_contains((string) $contentType, $type)) {
                return true;
            }
        }

        return false;
    }
}
