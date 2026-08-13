<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Admin Dashboard & Analytics API.
 *
 * Part 8 – Admin Dashboard. Aggregated, permission-scoped statistics for the
 * system operators. Route middleware restricts access to privileged roles.
 */
class AdminDashboardController extends ApiController
{
    public function __construct(
        private readonly AdminDashboardService $service,
    ) {}

    /**
     * The aggregated dashboard snapshot.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return $this->success(
            $this->service->snapshot($request->user()),
            'Admin dashboard retrieved.'
        );
    }
}