<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AddCspNonce
{
    /**
     * Generate a CSP nonce and share it with request attributes.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = '';

        try {
            $nonce = base64_encode(random_bytes(16));
        } catch (\Throwable $e) {
            // Fallbacks to avoid hard failures if random_bytes is unavailable
            if (function_exists('openssl_random_pseudo_bytes')) {
                $bytes = openssl_random_pseudo_bytes(16);
                if ($bytes !== '') {
                    $nonce = base64_encode($bytes);
                }
            }

            // Final deterministic fallback (lower entropy but prevents 500s)
            if ($nonce === '') {
                $nonce = base64_encode(uniqid('', true));
            }
        }

        // Share nonce with request for CSP header generation
        $request->attributes->set('cspNonce', $nonce);

        $response = $next($request);

        $cspHeader = $response->headers->get('Content-Security-Policy');

        if (is_string($cspHeader) && str_contains($cspHeader, 'nonce-')) {
            return $response;
        }

        $response->headers->set('Content-Security-Policy', "script-src 'self' 'nonce-{$nonce}'");

        $contentType = $response->headers->get('Content-Type');
        if (
            $response instanceof BinaryFileResponse ||
            (is_string($contentType) && str_contains($contentType, 'application/json'))
        ) {
            return $response;
        }

        $content = (string) $response->getContent();
        $response->setContent(str_replace('<head>', "<head>\n<meta http-equiv=\"Content-Security-Policy\" content=\"script-src 'self' 'nonce-{$nonce}'\">", $content));

        return $response;
    }
}
