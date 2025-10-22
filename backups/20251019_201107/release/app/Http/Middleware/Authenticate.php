<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    #[\Override]
    protected function redirectTo(Request $request): ?string
    {
        // For API requests, return null to trigger JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        return route('login');
    }

    /**
     * @param  array<int, string>  $guards
     */
    #[\Override]
    protected function unauthenticated($request, array $guards): never
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(401, 'Unauthenticated');
        }

        abort(302, '', ['Location' => route('login')]);
    }
}
