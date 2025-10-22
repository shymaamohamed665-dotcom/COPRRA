<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);
        $validatedLocale = $this->validateLocale($locale);

        App::setLocale($validatedLocale);
        Session::put('locale', $validatedLocale);

        $response = $next($request);
        if (! ($response instanceof Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }

    private function determineLocale(Request $request): string
    {
        $locale = $request->get('lang')
            ?? Session::get('locale')
            ?? $request->header('Accept-Language')
            ?? config('app.locale');

        $locale = is_string($locale) ? $locale : 'en';

        return $this->extractPrimaryLanguage($locale);
    }

    private function extractPrimaryLanguage(string $locale): string
    {
        if (str_contains($locale, ',')) {
            $locale = explode(',', $locale)[0];
        }
        if (str_contains($locale, '-')) {
            $locale = explode('-', $locale)[0];
        }

        return $locale;
    }

    private function validateLocale(string $locale): string
    {
        $supportedLocales = ['en', 'ar', 'fr', 'es', 'de'];

        if (! in_array($locale, $supportedLocales)) {
            return config('app.fallback_locale', 'en');
        }

        return $locale;
    }
}
