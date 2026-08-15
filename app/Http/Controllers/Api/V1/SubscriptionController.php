<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SubscriptionActionRequest;
use App\Http\Requests\Api\SubscriptionChangePlanRequest;
use App\Http\Requests\Api\SubscriptionFeatureRequest;
use App\Http\Requests\Api\SubscriptionRequest;
use App\Http\Resources\SubscriptionHistoryResource;
use App\Http\Resources\SubscriptionResource;
use App\Enums\Platform\BillingCycle;
use App\Enums\Platform\ExpirationBehavior;
use App\Enums\Platform\SubscriptionStatus;
use App\Models\SchoolProfile;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\SubscriptionAuditService;
use App\Services\SubscriptionService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends CrudController
{
    protected string $modelClass = Subscription::class;

    protected string $resourceClass = SubscriptionResource::class;

    public function __construct(
        SubscriptionService $service,
        private readonly SubscriptionAuditService $audit,
        private readonly TenantService $tenantService,
    ) {
        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return SubscriptionRequest::class;
    }

    /**
     * The human readable label of the resource.
     */
    protected function resourceLabel(): string
    {
        return 'Subscription';
    }

    /**
     * Provision a subscription for a tenant (honoring trial and issuing a license).
     */
    public function provision(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_cycle' => ['sometimes', 'in:monthly,annual,custom'],
            'auto_renewal' => ['sometimes', 'boolean'],
            'grace_days' => ['sometimes', 'integer', 'min:0'],
            'expiration_behavior' => ['sometimes', 'in:grace_period,restricted_access,suspended,read_only'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tenant = Tenant::query()->findOrFail($request->integer('tenant_id'));
        $plan = SubscriptionPlan::query()->findOrFail($request->integer('plan_id'));

        $this->authorize('create', Subscription::class);

        $subscription = $this->service->subscribeTenant($tenant, $plan, $data);

        return $this->success(new SubscriptionResource($subscription), 'Subscription provisioned.', 201);
    }

    /**
     * Manually grant a subscription to a school.
     *
     * The school is the primary key: its tenant (the school's billing entity) is
     * resolved automatically on first use. A legacy `tenant_id` is still
     * accepted for backward compatibility.
     */
    public function grant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'school_profile_id' => ['required_without:tenant_id', 'integer', 'exists:school_profiles,id'],
            'tenant_id' => ['required_without:school_profile_id', 'integer', 'exists:tenants,id'],
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'status' => ['sometimes', Rule::enum(SubscriptionStatus::class)],
            'billing_cycle' => ['sometimes', Rule::enum(BillingCycle::class)],
            'start_date' => ['sometimes', 'date'],
            'expiration_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'auto_renewal' => ['sometimes', 'boolean'],
            'grace_days' => ['sometimes', 'integer', 'min:0'],
            'expiration_behavior' => ['sometimes', Rule::enum(ExpirationBehavior::class)],
            'issue_license' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $plan = SubscriptionPlan::query()->findOrFail($request->integer('plan_id'));

        $this->authorize('create', Subscription::class);

        $tenant = $request->filled('school_profile_id')
            ? $this->tenantService->resolveForSchool(SchoolProfile::query()->findOrFail($request->integer('school_profile_id')))
            : Tenant::query()->findOrFail($request->integer('tenant_id'));

        $subscription = $this->service->grant($tenant, $plan, $data);

        return $this->success(new SubscriptionResource($subscription), 'Subscription manually granted.', 201);
    }

    /**
     * Renew a subscription.
     */
    public function renew(SubscriptionActionRequest $request, int $id): JsonResponse
    {
        $subscription = $this->service->find($id);

        $this->authorize('renew', $subscription);

        return $this->success(
            new SubscriptionResource($this->service->renew($subscription, $request->validated())),
            'Subscription renewed.'
        );
    }

    /**
     * Suspend a subscription.
     */
    public function suspend(SubscriptionActionRequest $request, int $id): JsonResponse
    {
        $subscription = $this->service->find($id);

        $this->authorize('suspend', $subscription);

        return $this->success(
            new SubscriptionResource($this->service->suspend($subscription, $request->validated())),
            'Subscription suspended.'
        );
    }

    /**
     * Resume a suspended subscription.
     */
    public function resume(SubscriptionActionRequest $request, int $id): JsonResponse
    {
        $subscription = $this->service->find($id);

        $this->authorize('resume', $subscription);

        return $this->success(
            new SubscriptionResource($this->service->resume($subscription, $request->validated())),
            'Subscription resumed.'
        );
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(SubscriptionActionRequest $request, int $id): JsonResponse
    {
        $subscription = $this->service->find($id);

        $this->authorize('cancel', $subscription);

        return $this->success(
            new SubscriptionResource($this->service->cancel($subscription, $request->validated())),
            'Subscription cancelled.'
        );
    }

    /**
     * Change the plan of a subscription.
     */
    public function changePlan(SubscriptionChangePlanRequest $request, int $id): JsonResponse
    {
        $subscription = $this->service->find($id);
        $plan = SubscriptionPlan::query()->findOrFail($request->integer('plan_id'));

        $this->authorize('changePlan', $subscription);

        return $this->success(
            new SubscriptionResource($this->service->changePlan($subscription, $plan, $request->validated())),
            'Subscription plan changed.'
        );
    }

    /**
     * Enable or disable a feature for a subscription.
     */
    public function toggleFeature(SubscriptionFeatureRequest $request, int $id): JsonResponse
    {
        $subscription = $this->service->find($id);

        $this->authorize('toggleFeature', $subscription);

        $enabled = $request->boolean('is_enabled', true);

        return $this->success(
            new SubscriptionResource($this->service->toggleFeature($subscription, $request->input('feature_code'), $enabled)),
            $enabled ? 'Feature enabled.' : 'Feature disabled.'
        );
    }

    /**
     * The audit trail scoped to a subscription.
     */
    public function history(Request $request, int $id): JsonResponse
    {
        $subscription = $this->service->find($id);

        $this->authorize('history', $subscription);

        $history = $this->audit->index(['subscription_id' => $id], $request->integer('per_page', 15));

        return $this->paginatedResource($history, 'Subscription history retrieved.');
    }
}