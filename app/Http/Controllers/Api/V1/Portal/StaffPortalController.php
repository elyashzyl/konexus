<?php

namespace App\Http\Controllers\Api\V1\Portal;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\StaffPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Staff Portal dashboard.
 *
 * Returns role-scoped statistics for the signed-in staff member so each
 * office portal opens on live numbers instead of static content.
 */
class StaffPortalController extends ApiController
{
    public function __construct(
        private readonly StaffPortalService $portal,
    ) {}

    /**
     * The statistics snapshot for the authenticated staff member.
     */
    public function dashboard(Request $request): JsonResponse
    {
        return $this->success(
            $this->portal->dashboard($request->user()),
            'Staff portal dashboard retrieved.'
        );
    }
}
