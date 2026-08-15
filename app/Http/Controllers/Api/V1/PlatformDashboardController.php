<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tenant;
use App\Services\SubscriptionDashboardService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Aggregated statistics for the platform subscription dashboard.
 */
class PlatformDashboardController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(private readonly SubscriptionDashboardService $dashboard) {}

    /**
     * The headline platform metrics.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        return $this->success(
            $this->dashboard->dashboard($request->input('filter', [])),
            'Platform dashboard retrieved.'
        );
    }
}