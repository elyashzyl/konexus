<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Services\FeatureAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces subscription feature gating on module routes.
 *
 * Usage: ->middleware('feature:attendance')
 *
 * Platform administrators and users without a resolvable tenant bypass the
 * check. All other users must belong to an active tenant subscription that
 * grants the requested feature.
 */
class EnsureFeatureAccess
{
    public function __construct(private readonly FeatureAccessService $features) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenantId = $request->header('X-Tenant-Id') ? (int) $request->header('X-Tenant-Id') : null;

        $allowed = $this->features->checkForUser($request->user(), $feature, $tenantId);

        if (! $allowed) {
            throw ApiException::forbidden(
                "The '{$feature}' feature is not available for this school under the current subscription."
            );
        }

        return $next($request);
    }
}