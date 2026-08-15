<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureFeatureAccess;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ResolveCampusWorkspace;
use App\Providers\RateLimitServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withProviders([
        RepositoryServiceProvider::class,
        RateLimitServiceProvider::class,
    ])
    ->withEvents(discover: [
        __DIR__.'/../app/Listeners',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => Authenticate::class,

            // KONEXUS foundation middleware
            'roles' => EnsureUserHasRole::class,

            // Part 10 – subscription feature gating
            'feature' => EnsureFeatureAccess::class,
            'campus.workspace' => ResolveCampusWorkspace::class,

            // Spatie Laravel Permission middleware
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::fromThrowable($e, $request);
            }
        });
    })
    ->create();
