<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 */
class ApiErrorHandler
{
    /**
     * Handle an incoming request and convert exceptions to JSON responses.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            return $next($request);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Access denied',
                'error_code' => 'UNAUTHORIZED',
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
                'error_code' => 'NOT_FOUND',
            ], 404);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Endpoint not found',
                'error_code' => 'ENDPOINT_NOT_FOUND',
            ], 404);
        } catch (MethodNotAllowedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Method not allowed',
                'error_code' => 'METHOD_NOT_ALLOWED',
            ], 405);
        } catch (TooManyRequestsHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests',
                'error_code' => 'RATE_LIMIT_EXCEEDED',
            ], 429);
        } catch (\PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection error',
                'error_code' => 'DATABASE_ERROR',
            ], 503);
        } catch (\Throwable $e) {
            $isProduction = app()->environment('production');

            return response()->json([
                'success' => false,
                'message' => $isProduction ? 'Internal server error' : ($e->getMessage() ?: 'Internal server error'),
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }
}
