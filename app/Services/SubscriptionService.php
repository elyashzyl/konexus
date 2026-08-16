<?php

namespace App\Services;

use App\Enums\Platform\BillingCycle;
use App\Enums\Platform\ExpirationBehavior;
use App\Enums\Platform\SubscriptionHistoryAction;
use App\Enums\Platform\SubscriptionStatus;
use App\Exceptions\ApiException;
use App\Http\Requests\Api\IndexRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The subscription engine. Owns the lifecycle of a tenant subscription:
 * provisioning, plan changes, renewals, suspensions and cancellations. Every
 * transition is recorded in the subscription audit trail.
 */
class SubscriptionService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['subscription_code'];

    /**
     * Relation columns included in free-text search.
     *
     * @var array<string, list<string>>
     */
    protected array $searchableRelations = ['tenant' => ['name', 'code']];

    protected array $sortable = ['id', 'subscription_code', 'status', 'start_date', 'expiration_date', 'amount', 'created_at', 'updated_at'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['tenant', 'plan.planFeatures', 'features'];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortDir = 'desc';

    public function __construct(
        private readonly SubscriptionRepositoryInterface $repo,
        private readonly TenantRepositoryInterface $tenantRepo,
        private readonly SubscriptionAuditService $audit,
        private readonly SubscriptionSettingsService $settings,
        private readonly LicenseService $licenseService,
    ) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * The equality filters extracted from the request.
     *
     * @return array<string, mixed>
     */
    protected function filters(IndexRequest $request): array
    {
        $filters = parent::filters($request);

        foreach (['status', 'tenant_id', 'plan_id', 'billing_cycle', 'auto_renewal'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Create a subscription record directly from validated data.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $data['subscription_code'] = $data['subscription_code'] ?? $this->generateCode();
        $data['start_date'] = $data['start_date'] ?? Carbon::today()->toDateString();
        $data['status'] = $data['status'] ?? SubscriptionStatus::PENDING->value;
        $data['billing_cycle'] = $data['billing_cycle'] ?? BillingCycle::MONTHLY->value;
        $schoolId = isset($data['tenant_id']) ? (int) Tenant::query()->whereKey($data['tenant_id'])->value('school_profile_id') : null;
        $data['grace_days'] = $data['grace_days'] ?? $this->settings->get('default_grace_days', 7, $schoolId);
        $data['expiration_behavior'] = $data['expiration_behavior'] ?? $this->settings->get('default_expiration_behavior', ExpirationBehavior::GRACE_PERIOD->value, $schoolId);
        $data['expiration_date'] = $data['expiration_date']
            ?? $this->computeExpiration($data['start_date'], $data['billing_cycle']);

        $subscription = parent::create($data);

        if (! empty($data['features']) && is_array($data['features'])) {
            $this->syncFeatures($subscription, $data['features']);
        } else {
            $this->syncFeaturesFromPlan($subscription);
        }

        $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::CREATED, [
            'description' => 'Subscription created.',
            'new_value' => ['plan_id' => $subscription->plan_id, 'status' => $subscription->status, 'expiration_date' => $subscription->expiration_date?->toDateString()],
        ]);

        return $subscription;
    }

    /**
     * Provision a new subscription for a tenant, honoring trial days and
     * issuing the matching license.
     *
     * @param  array<string, mixed>  $data
     */
    public function subscribeTenant(Tenant $tenant, SubscriptionPlan $plan, array $data = []): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $data): Subscription {
            $existing = $tenant->currentSubscription();

            if ($existing !== null && in_array($existing->status, [
                SubscriptionStatus::TRIAL->value,
                SubscriptionStatus::ACTIVE->value,
                SubscriptionStatus::GRACE_PERIOD->value,
            ], true)) {
                throw ApiException::conflict('The tenant already has an active subscription.');
            }

            $cycle = $data['billing_cycle'] ?? $plan->billing_cycle;
            $trialDays = $data['trial_days'] ?? $plan->trial_days;
            $start = Carbon::today();
            $useTrial = $trialDays !== null && (int) $trialDays > 0;

            $payload = [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $cycle,
                'amount' => $plan->priceForCycle($cycle),
                'auto_renewal' => (bool) ($data['auto_renewal'] ?? true),
                'grace_days' => (int) ($data['grace_days'] ?? $this->settings->get('default_grace_days', 7, $tenant->school_profile_id)),
                'expiration_behavior' => $data['expiration_behavior'] ?? $this->settings->get('default_expiration_behavior', ExpirationBehavior::GRACE_PERIOD->value, $tenant->school_profile_id),
                'notes' => $data['notes'] ?? null,
            ];

            if ($useTrial) {
                $payload += [
                    'status' => SubscriptionStatus::TRIAL->value,
                    'start_date' => $start->toDateString(),
                    'trial_started_at' => $start->toDateString(),
                    'trial_ends_at' => $start->copy()->addDays((int) $trialDays)->toDateString(),
                    'trial_status' => 'active',
                    'expiration_date' => $start->copy()->addDays((int) $trialDays)->toDateString(),
                ];
            } else {
                $payload += [
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'start_date' => $start->toDateString(),
                    'expiration_date' => $this->computeExpiration($start->toDateString(), $cycle),
                ];
            }

            $subscription = $this->create($payload);

            $this->audit->recordForSubscription($subscription, $useTrial
                ? SubscriptionHistoryAction::TRIAL_STARTED
                : SubscriptionHistoryAction::CREATED, [
                    'description' => $useTrial
                        ? "Trial started for plan {$plan->name}."
                        : "Subscription to plan {$plan->name} started.",
                    'new_value' => [
                        'plan_id' => $plan->id,
                        'trial_ends_at' => $subscription->trial_ends_at?->toDateString(),
                        'expiration_date' => $subscription->expiration_date?->toDateString(),
                    ],
                ]);

            $this->licenseService->issueLicense($tenant, $plan, [
                'start_date' => $subscription->start_date->toDateString(),
                'expiration_date' => $subscription->expiration_date?->toDateString(),
                'features' => $plan->featureCodes(),
                'subscription_id' => $subscription->id,
            ]);

            return $subscription;
        });
    }

    /**
     * Manually grant a subscription to a tenant without going through the
     * billing/payment pipeline. The admin picks the plan and dates directly;
     * a license is issued unless explicitly skipped.
     *
     * @param  array<string, mixed>  $data
     */
    public function grant(Tenant $tenant, SubscriptionPlan $plan, array $data = []): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $data): Subscription {
            $existing = $tenant->currentSubscription();

            if ($existing !== null && in_array($existing->status, [
                SubscriptionStatus::TRIAL->value,
                SubscriptionStatus::ACTIVE->value,
                SubscriptionStatus::GRACE_PERIOD->value,
            ], true)) {
                throw ApiException::conflict('The tenant already has an active subscription.');
            }

            $cycle = $data['billing_cycle'] ?? $plan->billing_cycle;
            $start = isset($data['start_date']) ? Carbon::parse($data['start_date']) : Carbon::today();

            $payload = [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => $data['status'] ?? SubscriptionStatus::ACTIVE->value,
                'billing_cycle' => $cycle,
                'amount' => $data['amount'] ?? $plan->priceForCycle($cycle),
                'start_date' => $start->toDateString(),
                'expiration_date' => $data['expiration_date'] ?? $this->computeExpiration($start->toDateString(), $cycle),
                'auto_renewal' => (bool) ($data['auto_renewal'] ?? false),
                'grace_days' => (int) ($data['grace_days'] ?? $this->settings->get('default_grace_days', 7, $tenant->school_profile_id)),
                'expiration_behavior' => $data['expiration_behavior'] ?? $this->settings->get('default_expiration_behavior', ExpirationBehavior::GRACE_PERIOD->value, $tenant->school_profile_id),
                'notes' => $data['notes'] ?? null,
            ];

            $subscription = $this->create($payload);

            if (($data['issue_license'] ?? true) === true) {
                $this->licenseService->issueLicense($tenant, $plan, [
                    'start_date' => $subscription->start_date->toDateString(),
                    'expiration_date' => $subscription->expiration_date?->toDateString(),
                    'features' => $plan->featureCodes(),
                    'subscription_id' => $subscription->id,
                ]);
            }

            $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::MANUAL_GRANT, [
                'description' => "Subscription manually granted for plan {$plan->name}.",
                'reason' => $data['notes'] ?? null,
                'new_value' => [
                    'plan_id' => $plan->id,
                    'status' => $subscription->status,
                    'amount' => (float) $subscription->amount,
                    'start_date' => $subscription->start_date?->toDateString(),
                    'expiration_date' => $subscription->expiration_date?->toDateString(),
                    'license_issued' => ($data['issue_license'] ?? true) === true,
                ],
            ]);

            return $subscription;
        });
    }

    /**
     * Renew an existing subscription, extending its expiration date.
     *
     * @param  array<string, mixed>  $data
     */
    public function renew(Subscription $subscription, array $data = []): Subscription
    {
        if (in_array($subscription->status, [SubscriptionStatus::EXPIRED->value, SubscriptionStatus::CANCELLED->value], true)) {
            throw ApiException::conflict('An expired or cancelled subscription cannot be renewed.');
        }

        return DB::transaction(function () use ($subscription, $data): Subscription {
            $cycle = $data['billing_cycle'] ?? $subscription->billing_cycle;
            $newExpiration = $this->computeExpiration(
                $subscription->expiration_date?->toDateString() ?? Carbon::today()->toDateString(),
                $cycle
            );

            $subscription->update([
                'status' => SubscriptionStatus::ACTIVE->value,
                'expiration_date' => $newExpiration,
                'grace_ends_at' => null,
                'suspended_at' => null,
                'suspend_reason' => null,
                'resumed_at' => null,
                'expected_resume_at' => null,
                'last_renewed_at' => Carbon::today(),
                'auto_renewal' => (bool) ($data['auto_renewal'] ?? $subscription->auto_renewal),
            ]);

            $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::RENEWED, [
                'description' => 'Subscription renewed.',
                'old_value' => ['expiration_date' => $subscription->getOriginal('expiration_date')?->toDateString()],
                'new_value' => ['expiration_date' => $subscription->expiration_date?->toDateString()],
            ]);

            return $subscription;
        });
    }

    /**
     * Suspend a subscription, optionally scheduling an expected resume date.
     *
     * @param  array<string, mixed>  $data
     */
    public function suspend(Subscription $subscription, array $data = []): Subscription
    {
        if ($subscription->status === SubscriptionStatus::SUSPENDED->value) {
            throw ApiException::conflict('The subscription is already suspended.');
        }

        $subscription->update([
            'status' => SubscriptionStatus::SUSPENDED->value,
            'suspended_at' => now(),
            'suspend_reason' => $data['reason'] ?? null,
            'expected_resume_at' => isset($data['expected_resume_at']) ? Carbon::parse($data['expected_resume_at'])->toDateString() : null,
        ]);

        $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::SUSPENDED, [
            'description' => 'Subscription suspended.',
            'reason' => $data['reason'] ?? null,
            'old_value' => ['status' => SubscriptionStatus::ACTIVE->value],
            'new_value' => ['status' => SubscriptionStatus::SUSPENDED->value],
        ]);

        return $subscription;
    }

    /**
     * Resume a suspended subscription.
     *
     * @param  array<string, mixed>  $data
     */
    public function resume(Subscription $subscription, array $data = []): Subscription
    {
        if ($subscription->status !== SubscriptionStatus::SUSPENDED->value) {
            throw ApiException::conflict('Only a suspended subscription can be resumed.');
        }

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE->value,
            'suspended_at' => null,
            'suspend_reason' => null,
            'expected_resume_at' => null,
            'resumed_at' => now(),
        ]);

        $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::RESUMED, [
            'description' => 'Subscription resumed.',
            'reason' => $data['reason'] ?? null,
            'old_value' => ['status' => SubscriptionStatus::SUSPENDED->value],
            'new_value' => ['status' => SubscriptionStatus::ACTIVE->value],
        ]);

        return $subscription;
    }

    /**
     * Cancel a subscription.
     *
     * @param  array<string, mixed>  $data
     */
    public function cancel(Subscription $subscription, array $data = []): Subscription
    {
        if (in_array($subscription->status, [SubscriptionStatus::CANCELLED->value, SubscriptionStatus::EXPIRED->value], true)) {
            throw ApiException::conflict('The subscription is already terminated.');
        }

        $subscription->update([
            'status' => SubscriptionStatus::CANCELLED->value,
            'cancelled_at' => now(),
            'cancel_reason' => $data['reason'] ?? null,
            'auto_renewal' => false,
        ]);

        $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::CANCELLED, [
            'description' => 'Subscription cancelled.',
            'reason' => $data['reason'] ?? null,
            'old_value' => ['status' => $subscription->getOriginal('status')],
            'new_value' => ['status' => SubscriptionStatus::CANCELLED->value],
        ]);

        return $subscription;
    }

    /**
     * Move a trial subscription to a paid plan.
     *
     * @param  array<string, mixed>  $data
     */
    public function convertTrial(Subscription $subscription, SubscriptionPlan $plan, array $data = []): Subscription
    {
        if ($subscription->status !== SubscriptionStatus::TRIAL->value) {
            throw ApiException::conflict('Only a trial subscription can be converted.');
        }

        $cycle = $data['billing_cycle'] ?? $plan->billing_cycle;

        $subscription->update([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'billing_cycle' => $cycle,
            'amount' => $plan->priceForCycle($cycle),
            'trial_status' => 'converted',
            'expiration_date' => $this->computeExpiration(Carbon::today()->toDateString(), $cycle),
        ]);

        $this->syncFeaturesFromPlan($subscription);

        $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::TRIAL_CONVERTED, [
            'description' => "Trial converted to plan {$plan->name}.",
            'old_value' => ['plan_id' => $subscription->getOriginal('plan_id')],
            'new_value' => ['plan_id' => $plan->id, 'expiration_date' => $subscription->expiration_date?->toDateString()],
        ]);

        return $subscription;
    }

    /**
     * Change the plan of a subscription.
     *
     * @param  array<string, mixed>  $data
     */
    public function changePlan(Subscription $subscription, SubscriptionPlan $plan, array $data = []): Subscription
    {
        if (in_array($subscription->status, [SubscriptionStatus::EXPIRED->value, SubscriptionStatus::CANCELLED->value], true)) {
            throw ApiException::conflict('A terminated subscription cannot change plans.');
        }

        $cycle = $data['billing_cycle'] ?? $subscription->billing_cycle;
        $oldPlanId = $subscription->plan_id;

        $subscription->update([
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
            'amount' => $plan->priceForCycle($cycle),
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $this->syncFeaturesFromPlan($subscription);

        $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::PLAN_CHANGED, [
            'description' => "Plan changed to {$plan->name}.",
            'reason' => $data['reason'] ?? null,
            'old_value' => ['plan_id' => $oldPlanId],
            'new_value' => ['plan_id' => $plan->id, 'amount' => (float) $subscription->amount],
        ]);

        return $subscription;
    }

    /**
     * Enable or disable a feature for a subscription.
     */
    public function toggleFeature(Subscription $subscription, string $featureCode, bool $enabled): Subscription
    {
        $feature = $subscription->features()->firstOrCreate(
            ['feature_code' => $featureCode],
            ['is_enabled' => $enabled]
        );

        if ($feature->is_enabled !== $enabled) {
            $feature->update(['is_enabled' => $enabled]);
        }

        $this->audit->recordForSubscription($subscription, $enabled
            ? SubscriptionHistoryAction::FEATURE_ENABLED
            : SubscriptionHistoryAction::FEATURE_DISABLED, [
                'description' => ($enabled ? 'Enabled' : 'Disabled')." feature {$featureCode}.",
                'new_value' => ['feature_code' => $featureCode, 'is_enabled' => $enabled],
            ]);

        return $subscription;
    }

    /**
     * Replace the feature overrides of a subscription.
     *
     * @param  array<string, mixed>  $features  feature_code => bool
     */
    public function syncFeatures(Subscription $subscription, array $features): void
    {
        $subscription->features()->delete();

        foreach ($features as $code => $enabled) {
            $subscription->features()->create([
                'feature_code' => (string) $code,
                'is_enabled' => (bool) $enabled,
            ]);
        }
    }

    /**
     * Rebuild the subscription feature overrides from the plan catalog.
     */
    public function syncFeaturesFromPlan(Subscription $subscription): void
    {
        $subscription->unsetRelation('plan');
        $subscription->load('plan.planFeatures');

        $codes = $subscription->plan?->featureCodes() ?? [];

        $subscription->features()->delete();

        foreach ($codes as $code) {
            $subscription->features()->create(['feature_code' => $code, 'is_enabled' => true]);
        }
    }

    /**
     * Compute the next expiration date for the given billing cycle.
     */
    public function computeExpiration(string|Carbon $start, string $cycle): string
    {
        $start = $start instanceof Carbon ? $start : Carbon::parse($start);

        return match ($cycle) {
            BillingCycle::ANNUAL->value => $start->copy()->addYear()->toDateString(),
            BillingCycle::CUSTOM->value => $start->copy()->addDays(90)->toDateString(),
            default => $start->copy()->addMonth()->toDateString(),
        };
    }

    /**
     * Generate a unique subscription code.
     */
    public function generateCode(): string
    {
        do {
            $code = 'SUB-'.strtoupper(bin2hex(random_bytes(4)));
        } while (Subscription::query()->withTrashed()->where('subscription_code', $code)->exists());

        return $code;
    }
}
