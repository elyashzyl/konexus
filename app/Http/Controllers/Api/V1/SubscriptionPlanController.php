<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Enums\Platform\SubscriptionFeature;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionPlanService;
use Illuminate\Http\JsonResponse;

class SubscriptionPlanController extends CrudController
{
    protected string $modelClass = SubscriptionPlan::class;

    protected string $resourceClass = SubscriptionPlanResource::class;

    public function __construct(SubscriptionPlanService $service)
    {
        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return SubscriptionPlanRequest::class;
    }

    /**
     * The human readable label of the resource.
     */
    protected function resourceLabel(): string
    {
        return 'Subscription plan';
    }

    /**
     * The active plans for subscription builders.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionPlan::class);

        return $this->success(
            $this->service->activePlans()
                ->map(fn (SubscriptionPlan $plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'code' => $plan->code,
                    'billing_cycle' => $plan->billing_cycle,
                    'monthly_price' => (float) $plan->monthly_price,
                    'annual_price' => (float) $plan->annual_price,
                ])
                ->values(),
            'Subscription plan options retrieved.'
        );
    }

    /**
     * The complete feature catalog used by plan builders.
     */
    public function features(): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionPlan::class);

        return $this->success($this->service->featureCatalog(), 'Feature catalog retrieved.');
    }

    /**
     * The public marketing catalog of active plans shown on the landing page.
     * Deliberately public: it only exposes display data of active plans.
     */
    public function publicCatalog(): JsonResponse
    {
        $plans = $this->service->activePlans()->map(function (SubscriptionPlan $plan): array {
            $features = $plan->planFeatures
                ->map(fn ($feature) => [
                    'code' => $feature->feature_code,
                    'label' => SubscriptionFeature::tryFrom($feature->feature_code)?->label() ?? $feature->feature_code,
                ])
                ->values()
                ->all();

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'description' => $plan->description,
                'billing_cycle' => $plan->billing_cycle,
                'monthly_price' => (float) $plan->monthly_price,
                'annual_price' => (float) $plan->annual_price,
                'trial_days' => $plan->trial_days,
                'max_students' => $plan->max_students,
                'max_storage_mb' => $plan->max_storage_mb,
                'display_order' => $plan->display_order,
                'features' => $features,
            ];
        });

        return $this->success($plans, 'Subscription plans retrieved.');
    }
}