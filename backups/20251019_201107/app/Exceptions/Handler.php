<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * @var array<int, class-string<\Throwable>>
     */
    protected array $dontReport = [];

    /**
     * @var array<int, string>
     */
    protected array $dontFlash = ['current_password', 'password', 'password_confirmation'];

    #[\Override]
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            if ($this->isSecurityException($e)) {
                $this->logSecurityException($e);
            }
        });

        $this->renderable(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $this->handleApiExceptions($e);
            }
        });
    }

    /**
     * Check if the exception is a security-related exception.
     */
    private function isSecurityException(Throwable $e): bool
    {
        return $e instanceof AuthenticationException ||
            $e instanceof AuthorizationException ||
            $e instanceof ValidationException ||
            $e instanceof QueryException;
    }

    /**
     * Log security-related exceptions.
     */
    private function logSecurityException(Throwable $e): void
    {
        logger()->warning('Security-related exception occurred', [
            'exception_type' => $e::class,
            'message' => $e->getMessage(),
            'ip' => request()->ip() ?? 'unknown',
            'user_agent' => request()->userAgent() ?? 'unknown',
            'url' => request()->url() ?? 'unknown',
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Handle API exceptions.
     */
    private function handleApiExceptions(Throwable $e): \Illuminate\Http\JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422),
            $e instanceof NotFoundHttpException => response()->json(['message' => 'Resource not found.'], 404),
            $e instanceof AuthenticationException => response()->json(['message' => 'Unauthenticated.'], 401),
            $e instanceof QueryException => response()->json(['message' => 'A server-side database error occurred.'], 500),
            $e instanceof AuthorizationException => response()->json(['message' => 'Forbidden.'], 403),
            default => response()->json(['message' => 'An unexpected server error occurred.'], 500),
        };
    }
}
