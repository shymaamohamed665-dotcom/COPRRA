<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RTLMiddleware
{
    /** @var array<string> */
    private array $rtlLanguages = ['ar', 'he', 'fa', 'ur'];

    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        $locale = app()->getLocale();

        if (in_array($locale, $this->rtlLanguages)) {
            view()->share('isRTL', true);
            view()->share('textDirection', 'rtl');
        } else {
            view()->share('isRTL', false);
            view()->share('textDirection', 'ltr');
        }

        $response = $next($request);
        if (! ($response instanceof \Symfony\Component\HttpFoundation\Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }
}
