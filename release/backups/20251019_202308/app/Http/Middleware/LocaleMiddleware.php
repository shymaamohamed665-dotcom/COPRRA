<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\App as AppFacade;

/**
 */
class LocaleMiddleware
{
    public function __construct(
        private readonly Guard $auth,
        private readonly Store $session
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            $locale = null;

            if ($this->session->has('locale_language')) {
                $locale = (string) $this->session->get('locale_language');
            } elseif ($request->server->get('HTTP_ACCEPT_LANGUAGE')) {
                $accept = (string) $request->server->get('HTTP_ACCEPT_LANGUAGE');
                $primary = strtolower(explode(',', $accept)[0] ?? '');
                $locale = substr($primary, 0, 2) ?: null;
            } elseif (method_exists($this->auth, 'check') && $this->auth->check()) {
                $user = $this->auth->user();
                if ($user && property_exists($user, 'locale') && is_string($user->locale)) {
                    $locale = $user->locale;
                }
            }

            if (is_string($locale) && $locale !== '') {
                AppFacade::setLocale($locale);
            }
        } catch (\Throwable $e) {
            // Fail gracefully and continue the request pipeline
        }

        return $next($request);
    }
}
