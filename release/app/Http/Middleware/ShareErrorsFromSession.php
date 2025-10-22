<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 */
class ShareErrorsFromSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        $response = $next($request);

        if (! ($response instanceof \Symfony\Component\HttpFoundation\Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        if ($request->hasSession()) {
            $errors = $request->session()->get('errors');
            if ($errors) {
                view()->share('errors', $errors);
            }
        }

        return $response;
    }
}
