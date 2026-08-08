<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        return route('login');
    }

    /**
     * Handle an incoming request (kept for clarity).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        return parent::handle($request, $next, ...$guards);
    }
}
