<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ActivityResource;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Audit & Activity Center API.
 *
 * Part 8 – Audit & Activity Center. Restricted log names are automatically
 * filtered for every role except the Super Administrator.
 */
class ActivityLogController extends ApiController
{
    public function __construct(
        private readonly ActivityLogService $service,
    ) {}

    /**
     * The paginated, filterable activity timeline.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'log_name' => ['nullable', 'string', 'max:120'],
            'subject_type' => ['nullable', 'string', 'max:160'],
            'event' => ['nullable', 'string', 'max:40'],
            'causer_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:120'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'batch' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $activities = $this->service->index($request->user(), $validated);

        return $this->success([
            'items' => ActivityResource::collection($activities->items())->resolve(),
            'pagination' => [
                'current_page' => $activities->currentPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
                'last_page' => $activities->lastPage(),
                'from' => $activities->firstItem(),
                'to' => $activities->lastItem(),
            ],
        ], 'Activity timeline retrieved.');
    }

    /**
     * The full record of a single activity entry.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        return $this->success(
            ActivityResource::make($this->service->find($request->user(), $id))->resolve(),
            'Activity record retrieved.'
        );
    }

    /**
     * Aggregated statistics of the activity timeline.
     */
    public function stats(Request $request): JsonResponse
    {
        return $this->success($this->service->stats($request->user()), 'Activity statistics retrieved.');
    }

    /**
     * The grouped catalog of auditable modules and the restricted list.
     */
    public function catalog(Request $request): JsonResponse
    {
        return $this->success($this->service->catalog($request->user()), 'Activity catalog retrieved.');
    }

    /**
     * The users who caused activity (causer filter options).
     */
    public function causers(Request $request): JsonResponse
    {
        return $this->success(['items' => $this->service->causers($request->user())], 'Activity causers retrieved.');
    }
}