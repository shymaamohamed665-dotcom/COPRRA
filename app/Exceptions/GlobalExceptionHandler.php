<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class GlobalExceptionHandler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected array $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    #[\Override]
    public function register(): void
    {
        $this->reportable(static function (): void {});
    }

    /**
     * Render an exception into an HTTP response.
     */
    #[\Override]
    public function render(\Illuminate\Http\Request $request, Throwable $e): JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        // Handle API requests
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        // Handle web requests
        return $this->handleWebException($request, $e);
    }

    /**
     * Handle API exceptions.
     */
    private function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        $this->logException($e, $request);

        $exceptionHandlers = $this->getExceptionHandlers();

        foreach ($exceptionHandlers as $exceptionClass => $handler) {
            if ($e instanceof $exceptionClass) {
                return $handler($e);
            }
        }

        return $this->handleGenericException($e);
    }

    /**
     * Get exception handlers mapping.
     *
     * @return array<class-string, callable>
     */
    private function getExceptionHandlers(): array
    {
        return [
            ValidationException::class => fn ($e) => $this->handleValidationException($e),
            AuthenticationException::class => fn () => $this->handleAuthenticationException(),
            AuthorizationException::class => fn () => $this->handleAuthorizationException(),
            ModelNotFoundException::class => fn () => $this->handleModelNotFoundException(),
            QueryException::class => fn ($e) => $this->handleQueryException($e),
            NotFoundHttpException::class => fn () => $this->handleNotFoundHttpException(),
            MethodNotAllowedHttpException::class => fn ($e) => $this->handleMethodNotAllowedHttpException($e),
            HttpException::class => fn ($e) => $this->handleHttpException($e),
        ];
    }

    /**
     * Handle web exceptions.
     */
    private function handleWebException(Request $request, Throwable $e): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->logException($e, $request);

        $webExceptionHandlers = $this->getWebExceptionHandlers();

        foreach ($webExceptionHandlers as $exceptionClass => $handler) {
            if ($e instanceof $exceptionClass) {
                return $handler($e, $request);
            }
        }

        return $this->handleGenericWebException($request, $e);
    }

    /**
     * Get web exception handlers mapping.
     *
     * @return array<class-string, callable>
     */
    private function getWebExceptionHandlers(): array
    {
        return [
            ValidationException::class => fn ($e) => redirect()->back()->withErrors($e->errors())->withInput(),
            AuthenticationException::class => fn () => redirect()->guest(route('login')),
            AuthorizationException::class => fn () => response()->view('errors.403', [], 403),
            ModelNotFoundException::class => fn () => response()->view('errors.404', [], 404),
            NotFoundHttpException::class => fn () => response()->view('errors.404', [], 404),
        ];
    }

    /**
     * Handle generic web exceptions.
     */
    private function handleGenericWebException(Request $request, Throwable $e): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        return $this->handleWebDebugMode($request, $e);
    }

    /**
     * Handle web debug mode.
     */
    private function handleWebDebugMode(Request $request, Throwable $e): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        if (config('app.debug')) {
            return parent::render($request, $e);
        }

        return response()->view('errors.500', [], 500);
    }

    /**
     * Create a standardized JSON error response.
     *
     * @param  string  $message  Error message
     * @param  string  $errorCode  Error code identifier
     * @param  int  $statusCode  HTTP status code
     * @param  array<string, string|int|array|bool|null>  $additionalData  Additional data to include in response
     */
    private function createErrorResponse(string $message, string $errorCode, int $statusCode, array $additionalData = []): JsonResponse
    {
        $responseData = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ];

        return response()->json(array_merge($responseData, $additionalData), $statusCode);
    }

    /**
     * Handle validation exceptions.
     */
    private function handleValidationException(ValidationException $e): JsonResponse
    {
        return $this->createErrorResponse(
            'Validation failed',
            'VALIDATION_ERROR',
            422,
            ['errors' => $e->errors()]
        );
    }

    /**
     * Handle authentication exceptions.
     */
    private function handleAuthenticationException(): JsonResponse
    {
        return $this->createErrorResponse(
            'Authentication required',
            'AUTHENTICATION_REQUIRED',
            401
        );
    }

    /**
     * Handle authorization exceptions.
     */
    private function handleAuthorizationException(): JsonResponse
    {
        return $this->createErrorResponse(
            'Access denied',
            'AUTHORIZATION_DENIED',
            403
        );
    }

    /**
     * Handle model not found exceptions.
     */
    private function handleModelNotFoundException(): JsonResponse
    {
        return $this->createErrorResponse(
            'Resource not found',
            'RESOURCE_NOT_FOUND',
            404
        );
    }

    /**
     * Handle query exceptions.
     */
    private function handleQueryException(QueryException $e): JsonResponse
    {
        // Log the actual database error for debugging
        Log::error('Database query error', [
            'error' => $e->getMessage(),
            'sql' => $e->getSql(),
            'bindings' => $e->getBindings(),
        ]);

        return $this->createErrorResponse(
            'Database error occurred',
            'DATABASE_ERROR',
            500
        );
    }

    /**
     * Handle HTTP exceptions.
     */
    private function handleHttpException(HttpException $e): JsonResponse
    {
        $statusCode = $e->getStatusCode();
        $message = $e->getMessage() ? $e->getMessage() : $this->getHttpStatusMessage($statusCode);

        return $this->createErrorResponse(
            $message,
            'HTTP_ERROR',
            $statusCode,
            ['status_code' => $statusCode]
        );
    }

    /**
     * Handle not found HTTP exceptions.
     */
    private function handleNotFoundHttpException(): JsonResponse
    {
        return $this->createErrorResponse(
            'Endpoint not found',
            'ENDPOINT_NOT_FOUND',
            404
        );
    }

    /**
     * Handle method not allowed HTTP exceptions.
     */
    private function handleMethodNotAllowedHttpException(MethodNotAllowedHttpException $e): JsonResponse
    {
        return $this->createErrorResponse(
            'Method not allowed',
            'METHOD_NOT_ALLOWED',
            405,
            ['allowed_methods' => $e->getHeaders()['Allow'] ?? []]
        );
    }

    /**
     * Handle generic exceptions.
     */
    private function handleGenericException(Throwable $e): JsonResponse
    {
        $statusCode = 500;
        $message = 'Internal server error';
        $errorCode = 'INTERNAL_ERROR';

        $this->handleCriticalErrorCheck($e);
        [$message, $errorCode] = $this->handleDebugMode($e, $message, $errorCode);

        return $this->createErrorResponse(
            $message,
            $errorCode,
            $statusCode,
            ['status_code' => $statusCode]
        );
    }

    /**
     * Handle critical error check.
     */
    private function handleCriticalErrorCheck(Throwable $e): void
    {
        if ($this->isCriticalError($e)) {
            $this->sendCriticalErrorNotification($e);
        }
    }

    /**
     * Handle debug mode.
     *
     * @return array{0: string, 1: string}
     */
    private function handleDebugMode(Throwable $e, string $message, string $errorCode): array
    {
        if (config('app.debug')) {
            $message = $e->getMessage();
            $errorCode = 'DEBUG_ERROR';
        }

        return [$message, $errorCode];
    }

    /**
     * Log exception with context.
     */
    private function logException(Throwable $e, Request $request): void
    {
        $context = [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'request' => [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
            ],
        ];

        if ($this->isCriticalError($e)) {
            Log::critical('Critical error occurred', $context);
        } else {
            Log::error('Exception occurred', $context);
        }
    }

    /**
     * Check if error is critical.
     */
    private function isCriticalError(Throwable $e): bool
    {
        $criticalErrors = [
            'PDOException',
            'RedisException',
            'MemcachedException',
            \GuzzleHttp\Exception\ConnectException::class,
            \Illuminate\Database\QueryException::class,
        ];

        return in_array($e::class, $criticalErrors) || $e->getCode() >= 500;
    }

    /**
     * Send critical error notification.
     */
    private function sendCriticalErrorNotification(Throwable $e): void
    {
        try {
            $adminEmails = config('app.admin_emails', []);

            if ($adminEmails !== []) {
                Mail::raw(
                    $this->createCriticalErrorMessage($e),
                    static function (\Illuminate\Mail\Message $message) use ($adminEmails): void {
                        $emailsArray = is_array($adminEmails) ? $adminEmails : (is_string($adminEmails) ? [$adminEmails] : []);
                        if ($emailsArray !== []) {
                            $message->to($emailsArray)
                                ->subject('Critical Error Alert - COPRRA');
                        }
                    }
                );
            }
        } catch (Throwable $mailException) {
            Log::error('Failed to send critical error notification', [
                'original_error' => $e->getMessage(),
                'mail_error' => $mailException->getMessage(),
            ]);
        }
    }

    /**
     * Create critical error message.
     */
    private function createCriticalErrorMessage(Throwable $e): string
    {
        return "Critical error occurred in COPRRA application:\n\n".
            'Error: '.$e->getMessage()."\n".
            'File: '.$e->getFile().':'.$e->getLine()."\n".
            'Time: '.now()->toISOString()."\n".
            'URL: '.request()->fullUrl();
    }

    /**
     * Get HTTP status message.
     */
    private function getHttpStatusMessage(int $statusCode): string
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        return $messages[$statusCode] ?? 'Unknown Error';
    }
}
