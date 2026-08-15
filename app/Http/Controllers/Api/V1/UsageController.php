<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\IndexRequest;
use App\Http\Resources\SubscriptionUsageResource;
use App\Models\SubscriptionUsage;
use App\Models\Tenant;
use App\Services\UsageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

/**
 * Usage snapshots and plan-limit enforcement readouts.
 */
class UsageController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(private readonly UsageService $usage) {}

    /**
     * The usage snapshots across tenants.
     */
    public function index(IndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionUsage::class);

        $query = SubscriptionUsage::query()->with(['tenant:id,code,name']);

        $filters = $request->filters();

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (! empty($filters['period_year'])) {
            $query->where('period_year', $filters['period_year']);
        }

        $paginator = $query
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate($request->perPage());

        return $this->success([
            'items' => SubscriptionUsageResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ], 'Usage snapshots retrieved.');
    }

    /**
     * The current usage, plan limits and warnings of a tenant.
     */
    public function tenant(int $tenantId): JsonResponse
    {
        $tenant = Tenant::query()->findOrFail($tenantId);

        $this->authorize('viewAny', SubscriptionUsage::class);

        return $this->success([
            'snapshot' => new SubscriptionUsageResource($this->usage->current($tenant)),
            'limit_status' => $this->usage->limitStatus($tenant),
            'trend' => $this->usage->trend($tenant)->map(fn ($snapshot) => new SubscriptionUsageResource($snapshot))->values(),
        ], 'Tenant usage retrieved.');
    }

    /**
     * Force-capture a usage snapshot for a tenant.
     */
    public function snapshot(int $tenantId): JsonResponse
    {
        $tenant = Tenant::query()->findOrFail($tenantId);

        return $this->success(
            new SubscriptionUsageResource($this->usage->snapshot($tenant)),
            'Usage snapshot captured.'
        );
    }
}