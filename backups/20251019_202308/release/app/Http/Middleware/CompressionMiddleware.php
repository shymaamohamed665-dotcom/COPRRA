<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Compression\CompressionResponseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompressionMiddleware
{
    private readonly CompressionResponseService $compressionResponseService;

    public function __construct(CompressionResponseService $compressionResponseService)
    {
        $this->compressionResponseService = $compressionResponseService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof Response) {
            throw new \RuntimeException('Middleware must return an instance of Response');
        }

        $acceptEncoding = $request->header('Accept-Encoding', '');

        if ($this->compressionResponseService->shouldCompress($acceptEncoding, $response)) {
            $this->compressionResponseService->compressResponse($response, $acceptEncoding);
        }

        return $response;
    }
}
