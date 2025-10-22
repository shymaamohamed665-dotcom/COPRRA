<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 */
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

    private readonly LoggerInterface $logger;

    private readonly Mailer $mailer;

    public function __construct(\Illuminate\Contracts\Container\Container $app, ?LoggerInterface $logger = null, ?Mailer $mailer = null)
    {
        parent::__construct($app);
        $this->logger = $logger ?? app(LoggerInterface::class);
        $this->mailer = $mailer ?? app(Mailer::class);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    #[\Override]
    public function register(): void
    {
        $this->reportable(static function (): void {
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    #[\Override]
    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function render($request, Throwable $exception): JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        // Handle API requests
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $exception);
        }

        // Handle web requests
        return $this->handleWebException($request, $exception);
    }

    /**
     * Handle API exceptions.
     */
    private function handleApiException(Request $request, Throwable $exception): JsonResponse
    {
        $this->logException($exception, $request);

        $exceptionHandlers = $this->getExceptionHandlers();

        foreach ($exceptionHandlers as $exceptionClass => $handler) {
            if ($exception instanceof $exceptionClass) {
                return $handler($exception);
            }
        }

        return $this->handleGenericException($exception);
    }

    /**
     * Get exception handlers mapping.
     *
     * @return array<\Closure>
     *
     * @psalm-return array{'Illuminate\\Validation\\ValidationException'::class: \Closure(mixed):JsonResponse, 'Illuminate\\Auth\\AuthenticationException'::class: \Closure():JsonResponse, 'Illuminate\\Auth\\Access\\AuthorizationException'::class: \Closure():JsonResponse, 'Illuminate\\Database\\Eloquent\\ModelNotFoundException'::class: \Closure():JsonResponse, 'Illuminate\\Database\\QueryException'::class: \Closure(mixed):JsonResponse, 'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException'::class: \Closure():JsonResponse, 'Symfony\\Component\\HttpKernel\\Exception\\MethodNotAllowedHttpException'::class: \Closure(mixed):JsonResponse, 'Symfony\\Component\\HttpKernel\\Exception\\HttpException'::class: \Closure(mixed):JsonResponse}
     */
    private function getExceptionHandlers(): array
    {
        return [
            ValidationException::class => fn ($exception): \Illuminate\Http\JsonResponse => $this->handleValidationException($exception),
            AuthenticationException::class => fn (): \Illuminate\Http\JsonResponse => $this->handleAuthenticationException(),
            AuthorizationException::class => fn (): \Illuminate\Http\JsonResponse => $this->handleAuthorizationException(),
            ModelNotFoundException::class => fn (): \Illuminate\Http\JsonResponse => $this->handleModelNotFoundException(),
            QueryException::class => fn ($exception): \Illuminate\Http\JsonResponse => $this->handleQueryException($exception),
            NotFoundHttpException::class => fn (): \Illuminate\Http\JsonResponse => $this->handleNotFoundHttpException(),
            MethodNotAllowedHttpException::class => fn ($exception): \Illuminate\Http\JsonResponse => $this->handleMethodNotAllowedHttpException($exception),
            HttpException::class => fn ($exception): \Illuminate\Http\JsonResponse => $this->handleHttpException($exception),
        ];
    }

    /**
     * Handle web exceptions.
     */
    private function handleWebException(Request $request, Throwable $exception): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->logException($exception, $request);

        $webExceptionHandlers = $this->getWebExceptionHandlers();

        foreach ($webExceptionHandlers as $exceptionClass => $handler) {
            if ($exception instanceof $exceptionClass) {
                return $handler($exception, $request);
            }
        }

        return $this->handleGenericWebException($request, $exception);
    }

    /**
     * Get web exception handlers mapping.
     *
     * @return array<\Closure>
     *
     * @psalm-return array{'Illuminate\\Validation\\ValidationException'::class: \Closure(mixed):\Illuminate\Http\RedirectResponse, 'Illuminate\\Auth\\AuthenticationException'::class: \Closure():\Illuminate\Http\RedirectResponse, 'Illuminate\\Auth\\Access\\AuthorizationException'::class: \Closure():\Illuminate\Http\Response, 'Illuminate\\Database\\Eloquent\\ModelNotFoundException'::class: \Closure():\Illuminate\Http\Response, 'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException'::class: \Closure():\Illuminate\Http\Response}
     */
    private function getWebExceptionHandlers(): array
    {
        return [
            ValidationException::class => fn (ValidationException $exception) => redirect()->back()->withErrors($exception->errors())->withInput(),
            AuthenticationException::class => fn () => redirect()->guest(route('login')),
            AuthorizationException::class => fn () => response()->view('errors.403', [], 403),
            ModelNotFoundException::class => fn () => response()->view('errors.404', [], 404),
            NotFoundHttpException::class => fn () => response()->view('errors.404', [], 404),
        ];
    }

    /**
     * Handle generic web exceptions.
     */
    private function handleGenericWebException(Request $request, Throwable $exception): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        return $this->handleWebDebugMode($request, $exception);
    }

    /**
     * Handle web debug mode.
     */
    private function handleWebDebugMode(Request $request, Throwable $exception): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        if (config('app.debug')) {
            return parent::render($request, $exception);
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
    private function handleValidationException(ValidationException $exception): JsonResponse
    {
        return $this->createErrorResponse(
            'Validation failed',
            'VALIDATION_ERROR',
            422,
            ['errors' => $exception->errors()]
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
    private function handleQueryException(QueryException $exception): JsonResponse
    {
        // Log the actual database error for debugging
        $this->logger->error('Database query error', [
            'error' => $exception->getMessage(),
            'sql' => $exception->getSql(),
            'bindings' => $exception->getBindings(),
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
    private function handleHttpException(HttpException $exception): JsonResponse
    {
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage() !== '' && $exception->getMessage() !== '0' ? $exception->getMessage() : $this->getHttpStatusMessage($statusCode);

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
    private function handleMethodNotAllowedHttpException(MethodNotAllowedHttpException $exception): JsonResponse
    {
        return $this->createErrorResponse(
            'Method not allowed',
            'METHOD_NOT_ALLOWED',
            405,
            ['allowed_methods' => $exception->getHeaders()['Allow'] ?? []]
        );
    }

    /**
     * Handle generic exceptions.
     */
    private function handleGenericException(Throwable $exception): JsonResponse
    {
        $statusCode = 500;
        $message = 'Internal server error';
        $errorCode = 'INTERNAL_ERROR';

        $this->handleCriticalErrorCheck($exception);
        [$message, $errorCode] = $this->handleDebugMode($exception, $message, $errorCode);

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
    private function handleCriticalErrorCheck(Throwable $exception): void
    {
        if ($this->isCriticalError($exception)) {
            $this->sendCriticalErrorNotification($exception);
        }
    }

    /**
     * Handle debug mode.
     *
     * @return array<string>
     *
     * @psalm-return list{string, string}
     */
    private function handleDebugMode(Throwable $exception, string $message, string $errorCode): array
    {
        if (config('app.debug')) {
            $message = $exception->getMessage();
            $errorCode = 'DEBUG_ERROR';
        }

        return [$message, $errorCode];
    }

    /**
     * Log exception with context.
     */
    private function logException(Throwable $exception, Request $request): void
    {
        $context = [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'request' => [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
            ],
        ];

        if ($this->isCriticalError($exception)) {
            $this->logger->critical('Critical error occurred', $context);

            return;
        }

        $this->logger->error('Exception occurred', $context);
    }

    /**
     * Check if error is critical.
     */
    private function isCriticalError(Throwable $exception): bool
    {
        $criticalErrors = [
            'PDOException',
            'RedisException',
            'MemcachedException',
            \GuzzleHttp\Exception\ConnectException::class,
            \Illuminate\Database\QueryException::class,
        ];

        return in_array($exception::class, $criticalErrors, true) || $exception->getCode() >= Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * Send critical error notification.
     */
    private function sendCriticalErrorNotification(Throwable $exception): void
    {
        try {
            $adminEmails = config('app.admin_emails', []);

            if ($adminEmails !== []) {
                $this->mailer->raw(
                    $this->createCriticalErrorMessage($exception),
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
            $this->logger->error('Failed to send critical error notification', [
                'original_error' => $exception->getMessage(),
                'mail_error' => $mailException->getMessage(),
            ]);
        }
    }

    /**
     * Create critical error message.
     */
    private function createCriticalErrorMessage(Throwable $exception): string
    {
        return "Critical error occurred in COPRRA application:\n\n".
            'Error: '.$exception->getMessage()."\n".
            'File: '.$exception->getFile().':'.$exception->getLine()."\n".
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
