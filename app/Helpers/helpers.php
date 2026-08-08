<?php

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

if (! function_exists('api_success')) {
    /**
     * Return a successful API response envelope.
     */
    function api_success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }
}

if (! function_exists('api_error')) {
    /**
     * Return an error API response envelope.
     */
    function api_error(string $message = 'Something went wrong.', mixed $errors = null, int $status = 400): JsonResponse
    {
        return ApiResponse::error($message, $errors, $status);
    }
}

if (! function_exists('api_route_name')) {
    /**
     * Build a namespaced API route name for the given version and name.
     */
    function api_route_name(string $name, string $version = 'v1'): string
    {
        return "api.{$version}.{$name}";
    }
}

if (! function_exists('is_api_request')) {
    /**
     * Determine whether the current request targets the API.
     */
    function is_api_request(?string $uri = null): bool
    {
        $uri ??= request()->path();

        return str_starts_with($uri, 'api/');
    }
}

if (! function_exists('frontend_url')) {
    /**
     * Build an absolute URL that points at the SPA frontend.
     */
    function frontend_url(string $path): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/'.ltrim($path, '/');
    }
}
