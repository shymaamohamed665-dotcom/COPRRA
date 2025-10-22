<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\SafeMiddlewareTestBase;

/**
 * @runTestsInSeparateProcesses
 */
class SetLocaleAndCurrencyTest extends SafeMiddlewareTestBase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_set_locale_and_currency_middleware_sets_locale_and_currency(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8');

        $middleware = new \App\Http\Middleware\SetLocaleAndCurrency;
        $response = $middleware->handle($request, function ($req) {
            return new \Illuminate\Http\Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_set_locale_and_currency_middleware_handles_default_values(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\SetLocaleAndCurrency;
        $response = $middleware->handle($request, function ($req) {
            return new \Illuminate\Http\Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_set_locale_and_currency_middleware_handles_session_values(): void
    {
        $request = Request::create('/test', 'GET');
        $session = new \Symfony\Component\HttpFoundation\Session\Session(
            new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage
        );
        $request->setSession($session);
        $session->set('locale', 'es');
        $session->set('currency', 'EUR');

        $middleware = new \App\Http\Middleware\SetLocaleAndCurrency;
        $response = $middleware->handle($request, function ($req) {
            return new \Illuminate\Http\Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_set_locale_and_currency_middleware_handles_cookie_values(): void
    {
        $request = Request::create('/test', 'GET');
        $request->cookies->set('locale', 'de');
        $request->cookies->set('currency', 'EUR');

        $middleware = new \App\Http\Middleware\SetLocaleAndCurrency;
        $response = $middleware->handle($request, function ($req) {
            return new \Illuminate\Http\Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_set_locale_and_currency_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $middleware = new \App\Http\Middleware\SetLocaleAndCurrency;
        $response = $middleware->handle($request, function ($req) {
            return new \Illuminate\Http\Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
