<?php

declare(strict_types=1);

namespace App\Services\Compression;

use Illuminate\Http\Response;

class CompressionResponseService
{
    private CompressionService $compressionService;

    private ContentTypeService $contentTypeService;

    public function __construct(
        CompressionService $compressionService,
        ContentTypeService $contentTypeService
    ) {
        $this->compressionService = $compressionService;
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * Check if response should be compressed
     */
    public function shouldCompress(string $acceptEncoding, Response $response): bool
    {
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return false;
        }

        return $this->compressionService->clientAcceptsCompression($acceptEncoding)
            && $this->contentTypeService->isCompressibleContentType($response)
            && $this->isContentLargeEnough($content);
    }

    /**
     * Apply compression to response
     */
    public function compressResponse(Response $response, string $acceptEncoding): void
    {
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return;
        }

        $compression = $this->compressionService->compress($content, $acceptEncoding);

        if ($compression) {
            $response->setContent($compression['content']);
            $response->headers->set('Content-Encoding', $compression['encoding']);
            $response->headers->set('Content-Length', (string) strlen($compression['content']));
            $response->headers->set('Vary', 'Accept-Encoding');
        }
    }

    /**
     * Check if content is large enough to warrant compression
     */
    private function isContentLargeEnough(string $content): bool
    {
        return strlen($content) > 1024;
    }
}
