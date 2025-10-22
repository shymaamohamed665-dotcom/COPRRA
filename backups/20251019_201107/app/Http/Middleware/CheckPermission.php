<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! $request->user()) {
            abort(401, 'Unauthorized');
        }

        if (! $this->userHasPermission($request->user(), $permissions)) {
            abort(403, 'Forbidden - Missing required permission');
        }

        return $next($request);
    }

    /**
     * Check if the user has any of the required permissions.
     *
     * @param  array<int, string>  $permissions
     */
    private function userHasPermission(\Illuminate\Contracts\Auth\Authenticatable $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (is_object($user->role) && method_exists($user->role, 'hasPermission') && $user->role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
