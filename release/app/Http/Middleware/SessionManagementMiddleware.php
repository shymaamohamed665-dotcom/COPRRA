<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 */
class SessionManagementMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        // Check for session fixation
        $this->preventSessionFixation($request);

        // Regenerate session ID periodically
        $this->regenerateSessionId($request);

        // Clean up inactive sessions
        $this->cleanupInactiveSessions($request);

        $response = $next($request);

        if (! ($response instanceof \Symfony\Component\HttpFoundation\Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }

    /**
     * Prevent session fixation attacks.
     */
    private function preventSessionFixation(Request $request): void
    {
        $this->regenerateSessionOnLogin($request);
        $this->regenerateSessionOnPrivilegeEscalation($request);
    }

    private function regenerateSessionOnLogin(Request $request): void
    {
        if ($request->is('login') && $request->isMethod('post')) {
            Session::regenerate(true);
            Log::info('Session regenerated on login', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    private function regenerateSessionOnPrivilegeEscalation(Request $request): void
    {
        if ($request->user() && $request->user()->wasChanged('role')) {
            Session::regenerate(true);
            Log::info('Session regenerated on role change', [
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
            ]);
        }
    }

    /**
     * Regenerate session ID periodically.
     */
    private function regenerateSessionId(Request $request): void
    {
        $lastRegeneration = Session::get('last_regeneration', 0);
        $regenerationInterval = config('session.regeneration_interval', 300); // 5 minutes

        if (is_numeric($lastRegeneration) && time() - (int) $lastRegeneration > $regenerationInterval) {
            Session::regenerate(true);
            Session::put('last_regeneration', time());

            Log::debug('Session ID regenerated periodically', [
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);
        }
    }

    /**
     * Clean up inactive sessions.
     */
    private function cleanupInactiveSessions(Request $request): void
    {
        $lastActivity = Session::get('last_activity', time());
        $inactivityTimeout = config('session.inactivity_timeout', 1800); // 30 minutes

        if (is_numeric($lastActivity) && time() - (int) $lastActivity > $inactivityTimeout) {
            Session::flush();

            Log::info('Session cleaned up due to inactivity', [
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'inactivity_duration' => time() - $lastActivity,
            ]);
        }

        Session::put('last_activity', time());
    }
}
