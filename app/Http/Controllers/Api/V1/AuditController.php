<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\IndexRequest;
use App\Http\Resources\SubscriptionHistoryResource;
use App\Models\SubscriptionHistory;
use App\Services\SubscriptionAuditService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The read-only platform audit trail of subscription/tenant events.
 */
class AuditController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(private readonly SubscriptionAuditService $audit) {}

    /**
     * The paginated audit trail.
     */
    public function index(IndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionHistory::class);

        $filters = $request->filters();

        $paginator = $this->audit->index($filters, $request->perPage());

        return $this->success([
            'items' => SubscriptionHistoryResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ], 'Subscription audit trail retrieved.');
    }

    /**
     * A single audit entry.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $history = SubscriptionHistory::query()
            ->with(['tenant:id,code,name', 'subscription:id,subscription_code'])
            ->findOrFail($id);

        $this->authorize('view', $history);

        return $this->success(new SubscriptionHistoryResource($history), 'Audit entry retrieved.');
    }

    /**
     * The distinct actions available for filtering.
     */
    public function actions(): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionHistory::class);

        return $this->success(
            \App\Enums\Platform\SubscriptionHistoryAction::toOptions(),
            'Audit actions retrieved.'
        );
    }
}