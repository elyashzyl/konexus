<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Platform\SubscriptionStatus;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\FeatureAccessService;
use App\Services\TenantResolverService;
use App\Services\UsageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The read-only "My Subscription" surface for school administrators. School
 * users may view their own school's subscription, features and usage, but
 * never manage or mutate it.
 */
class SchoolSubscriptionController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TenantResolverService $resolver,
        private readonly FeatureAccessService $features,
        private readonly UsageService $usage,
    ) {}

    /**
     * The current subscription summary of the user's school.
     */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();

        $tenant = $this->resolver->resolveForUser($user);

        if ($tenant === null) {
            return $this->success([
                'tenant' => null,
                'subscription' => null,
                'message' => 'No school subscription is linked to this account.',
            ], 'Subscription summary retrieved.');
        }

        $subscription = $tenant->currentSubscription();

        return $this->success([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'code' => $tenant->code,
                'status' => $tenant->status,
            ],
            'subscription' => $subscription ? new SubscriptionResource($subscription->load(['plan.planFeatures', 'features'])) : null,
            'features' => $subscription ? $this->features->effectiveFeatures($subscription) : [],
            'limits' => $this->features->planLimits($tenant),
            'usage' => $this->usage->limitStatus($tenant),
            'read_only' => true,
        ], 'Subscription summary retrieved.');
    }
}