<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class AuthenticateWithBasicAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticate_with_basic_auth_middleware_handles_requests(): void
    {
        // إنشاء مستخدم ببيانات اعتماد صحيحة
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $request = Request::create('/test', 'GET');
        // اضبط بيانات اعتماد Basic Auth عبر ترويسات PHP_AUTH_*
        $request->headers->set('PHP_AUTH_USER', 'test@example.com');
        $request->headers->set('PHP_AUTH_PW', 'password123');

        // اربط الطلب داخل الحاوية ليستخدمه الحارس أثناء basic auth
        $this->app->instance('request', $request);

        $middleware = $this->app->make(\App\Http\Middleware\AuthenticateWithBasicAuth::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        // يعتمد نجاح التوثيق على وجود المستخدم وبيانات الاعتماد الصحيحة
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_authenticate_with_basic_auth_middleware_handles_invalid_credentials(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('PHP_AUTH_USER', 'test@example.com');
        $request->headers->set('PHP_AUTH_PW', 'wrongpassword');

        // اربط الطلب داخل الحاوية ليستخدمه الحارس أثناء basic auth
        $this->app->instance('request', $request);

        $middleware = $this->app->make(\App\Http\Middleware\AuthenticateWithBasicAuth::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class);
        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_authenticate_with_basic_auth_middleware_handles_missing_authorization(): void
    {
        $request = Request::create('/test', 'GET');

        // اربط الطلب داخل الحاوية ليستخدمه الحارس أثناء basic auth
        $this->app->instance('request', $request);

        $middleware = $this->app->make(\App\Http\Middleware\AuthenticateWithBasicAuth::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class);
        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_authenticate_with_basic_auth_middleware_handles_malformed_authorization(): void
    {
        $request = Request::create('/test', 'GET');
        // إعداد ترويسة غير صحيحة (لن تُقرأ غالبًا بواسطة الحارس، وسيفشل التوثيق)
        $request->headers->set('Authorization', 'InvalidFormat');

        // اربط الطلب داخل الحاوية ليستخدمه الحارس أثناء basic auth
        $this->app->instance('request', $request);

        $middleware = $this->app->make(\App\Http\Middleware\AuthenticateWithBasicAuth::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class);
        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_authenticate_with_basic_auth_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->headers->set('PHP_AUTH_USER', 'test@example.com');
        $request->headers->set('PHP_AUTH_PW', 'password123');

        // اربط الطلب داخل الحاوية ليستخدمه الحارس أثناء basic auth
        $this->app->instance('request', $request);

        $middleware = $this->app->make(\App\Http\Middleware\AuthenticateWithBasicAuth::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class);

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }
}
