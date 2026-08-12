<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AcademicClass;
use App\Services\AcademicContextService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Aggregated statistics backing the Academic Dashboard and the schedule
 * configuration used across the Academic module.
 */
class AcademicDashboardController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(private readonly AcademicContextService $context) {}

    /**
     * The aggregate statistics of the academic module.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AcademicClass::class);

        return $this->success(
            $this->context->dashboard($request->input('filter', [])),
            'Academic dashboard retrieved.'
        );
    }

    /**
     * The operating days and academic context configuration.
     */
    public function context(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AcademicClass::class);

        $filters = $request->input('filter', []);

        return $this->success([
            'operating_days' => $this->context->operatingDays(),
            'academic_year' => $this->context->currentAcademicYear()?->only('id', 'name'),
            'academic_term' => $this->context->currentAcademicTerm()?->only('id', 'name'),
            'filters' => $filters,
        ], 'Academic context retrieved.');
    }
}