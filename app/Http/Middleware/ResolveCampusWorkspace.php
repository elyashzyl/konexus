<?php

namespace App\Http\Middleware;

use App\Services\CampusWorkspaceService;
use App\Support\CampusContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCampusWorkspace
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function __construct(private readonly CampusWorkspaceService $workspaces) {}

    public function handle(Request $request, Closure $next): Response
    {
        CampusContext::clear();

        $user = $request->user();
        if ($user !== null) {
            $header = $request->header('X-Campus-Id');
            $requestedCampusId = is_numeric($header) ? (int) $header : null;
            $campus = $this->workspaces->activeFor($user, $requestedCampusId);

            CampusContext::set($campus);
            $request->attributes->set('campus_workspace', $campus);

            if ($campus !== null
                && in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)
                && ! $request->has('campus_id')) {
                $request->merge(['campus_id' => $campus->id]);
            }
        }

        return $next($request);
    }
}
