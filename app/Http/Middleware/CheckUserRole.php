<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthorized');
        }

        // Convert string roles to UserRole enums
        $allowedRoles = array_map(
            static fn (string $role): UserRole => UserRole::from($role),
            $roles
        );

        // Check if user has any of the allowed roles
        if (! in_array($user->role, $allowedRoles, true)) {
            abort(403, 'Forbidden - Insufficient permissions');
        }

        return $next($request);
    }
}
