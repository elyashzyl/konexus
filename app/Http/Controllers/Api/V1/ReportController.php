<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Reports Center API.
 *
 * Part 8 – Reports. Lists the available reports and generates them as CSV or
 * PDF. All reports are permission-gated through the route middleware.
 */
class ReportController extends ApiController
{
    public function __construct(
        private readonly ReportService $service,
    ) {}

    /**
     * The catalog of available reports.
     */
    public function index(): JsonResponse
    {
        return $this->success($this->service->catalog(), 'Report catalog retrieved.');
    }

    /**
     * Generate a report file (CSV or PDF).
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'report' => ['required', 'string', 'max:40'],
            'format' => ['required', 'string', 'in:csv,pdf'],
            'academic_year_id' => ['nullable', 'integer'],
            'academic_term_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
        ]);

        return $this->service->generate(
            $validated['report'],
            $validated['format'],
            $validated
        );
    }
}