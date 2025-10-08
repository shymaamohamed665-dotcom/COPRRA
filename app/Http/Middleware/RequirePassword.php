<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isPasswordConfirmed($request)) {
            return $next($request);
        }

        return $this->responseForMissingPasswordConfirmation($request);
    }

    private function isPasswordConfirmed(Request $request): bool
    {
        $user = $request->user();

        if (! $this->hasConfirmedPassword($user)) {
            return false;
        }

        $lastConfirmation = is_string($user->password_confirmed_at)
            ? strtotime($user->password_confirmed_at)
            : $user->password_confirmed_at->timestamp;

        $timeout = config('auth.password_timeout', 10800);

        return time() - $lastConfirmation <= $timeout;
    }

    private function hasConfirmedPassword(\App\Models\User $user): bool
    {
        return $user && isset($user->password_confirmed_at) && $user->password_confirmed_at;
    }

    private function responseForMissingPasswordConfirmation(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Password confirmation required'], 423);
        }

        return redirect()->route('password.confirm');
    }
}
