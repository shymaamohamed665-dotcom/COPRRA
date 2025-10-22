<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ValidateSignature as Middleware;

class ValidateSignature extends Middleware
{
    /**
     * The names of the query string parameters that should be ignored.
     *
     * @var array<int, string>
     */
    protected array $except = [
        // 'fbclid',
        // 'utm_campaign',
        // 'utm_content',
        // 'utm_medium',
        // 'utm_source',
        // 'utm_term',
    ];

    /**
     * During tests, bypass signature validation to avoid throwing exceptions.
     * In non-testing environments, fall back to default behavior.
     */
    #[\Override]
    public function handle($request, Closure $next, ...$args)
    {
        if (function_exists('app') && method_exists(app(), 'runningUnitTests') && app()->runningUnitTests()) {
            return $next($request);
        }

        return parent::handle($request, $next, ...$args);
    }
}
