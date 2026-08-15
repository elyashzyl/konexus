<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Platform\SubscriptionFeature;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\FeatureAccessService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

/**
 * The feature catalog and the effective features of a subscription.
 */
class FeatureController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(private readonly FeatureAccessService $features) {}

    /**
     * The complete feature catalog.
     */
    public function catalog(): JsonResponse
    {
        $this->authorize('viewAny', Subscription::class);

        return $this->success(SubscriptionFeature::toOptions(), 'Feature catalog retrieved.');
    }

    /**
     * The effective features of a subscription.
     */
    public function subscription(int $id): JsonResponse
    {
        $subscription = Subscription::query()
            ->with(['plan.planFeatures', 'features'])
            ->findOrFail($id);

        $this->authorize('view', $subscription);

        return $this->success([
            'features' => $this->features->effectiveFeatures($subscription),
        ], 'Subscription features retrieved.');
    }

    /**
     * The effective features and plan limits of a tenant.
     */
    public function tenant(int $tenantId): JsonResponse
    {
        $tenant = Tenant::query()->findOrFail($tenantId);

        $this->authorize('viewAny', Subscription::class);

        return $this->success([
            'tenant_id' => $tenant->id,
            'features' => collect($this->features->effectiveFeatures($tenant->currentSubscription() ?? new Subscription))
                ->values()
                ->all(),
            'limits' => $this->features->planLimits($tenant),
            'subscription' => $tenant->currentSubscription() ? new SubscriptionResource($tenant->currentSubscription()) : null,
        ], 'Tenant feature access retrieved.');
    }
}