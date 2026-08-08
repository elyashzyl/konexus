<?php

namespace App\Support;

use App\Exceptions\ApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Centralized, consistent REST API envelope used across the entire platform.
 *
 * Success: { success: true,  message: string, data: mixed|null, errors: null }
 * Error:   { success: false, message: string, data: null,      errors: object|null }
 */
final class ApiResponse
{
    /**
     * Build a successful response.
     */
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    /**
     * Build an error response.
     */
    public static function error(string $message = 'Something went wrong.', mixed $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Convert a throwable into the standard error envelope.
     */
    public static function fromThrowable(Throwable $e, Request $request): JsonResponse
    {
        [$status, $message, $errors] = self::resolve($e, $request);

        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Resolve the status code, message and structured errors for a throwable.
     *
     * @return array{0: int, 1: string, 2: mixed}
     */
    private static function resolve(Throwable $e, Request $request): array
    {
        if ($e instanceof ApiException) {
            return [$e->getStatusCode(), $e->getMessage(), $e->errors()];
        }

        if ($e instanceof ValidationException) {
            return [422, 'The given data was invalid.', $e->errors()];
        }

        if ($e instanceof AuthenticationException) {
            return [401, 'Unauthenticated.', null];
        }

        if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
            return [403, 'You are not authorized to perform this action.', null];
        }

        if ($e instanceof ModelNotFoundException) {
            return [404, 'Resource not found.', null];
        }

        if ($e instanceof NotFoundHttpException) {
            return [404, 'Route not found.', null];
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return [405, 'Method not allowed.', null];
        }

        if ($e instanceof HttpException) {
            return [$e->getStatusCode(), $e->getMessage() ?: 'Request failed.', null];
        }

        if ($request->is('api/*')) {
            if (config('app.debug')) {
                return [500, $e->getMessage(), $e->getTrace()];
            }

            return [500, 'An unexpected error occurred.', null];
        }

        return [500, 'An unexpected error occurred.', null];
    }
}
