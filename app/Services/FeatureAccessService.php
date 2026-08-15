<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;

/**
 * Central authority for feature access. Determines whether a tenant (or a
 * user scoped to a tenant) may use a given module feature based on their
 * active subscription, its plan catalog and per-subscription overrides.
 *
 * Platform administrators bypass feature gating. Users with no resolvable
 * tenant (unmanaged schools) are allowed to keep existing modules working.
 */
class FeatureAccessService
{
    public function __construct(
        private readonly TenantResolverService $resolver,
        private readonly SubscriptionSettingsService $settings,
    ) {}

    /**
     * Whether the user may use the feature.
     */
    public function checkForUser(?User $user, string $feature, ?int $tenantId = null): bool
    {
        if ($user && $this->resolver->isPlatformAdmin($user)) {
            return true;
        }

        $tenant = $this->resolver->resolveForUser($user, $tenantId);

        // Unmanaged user/tenant: keep existing modules available.
        if ($tenant === null) {
            return true;
        }

        return $this->allowsFeature($tenant, $feature);
    }

    /**
     * Whether a tenant may use the feature right now.
     */
    public function allowsFeature(Tenant $tenant, string $feature): bool
    {
        if ($tenant->status !== 'active') {
            return false;
        }

        $subscription = $tenant->currentSubscription();

        if ($subscription === null || ! $subscription->allowsAccess()) {
            return false;
        }

        return in_array($feature, $this->effectiveFeatures($subscription), true);
    }

    /**
     * The effective enabled features of a subscription: the plan catalog
     * adjusted by per-subscription overrides.
     *
     * @return list<string>
     */
    public function effectiveFeatures(Subscription $subscription): array
    {
        if (! $subscription->relationLoaded('plan')) {
            $subscription->load('plan.planFeatures');
        }

        if (! $subscription->relationLoaded('features')) {
            $subscription->load('features');
        }

        $base = $subscription->plan?->featureCodes() ?? [];
        $overrides = $subscription->features ?? collect();

        $enabled = $base;
        foreach ($overrides as $override) {
            $enabled = array_values(array_filter($enabled, fn (string $code) => $code !== $override->feature_code));
            if ($override->is_enabled) {
                $enabled[] = $override->feature_code;
            }
        }

        return array_values(array_unique($enabled));
    }

    /**
     * The usage limits applicable to a tenant from their active plan.
     *
     * @return array<string, int|null>
     */
    public function planLimits(Tenant $tenant): array
    {
        $subscription = $tenant->currentSubscription();

        if ($subscription === null || ! $subscription->plan) {
            $subscription?->load('plan');

            return [
                'max_students' => null,
                'max_staff' => null,
                'max_branches' => null,
                'max_users' => null,
                'max_storage_mb' => null,
            ];
        }

        $plan = $subscription->plan;

        return [
            'max_students' => $plan->max_students,
            'max_staff' => $plan->max_staff,
            'max_branches' => $plan->max_branches,
            'max_users' => $plan->max_users,
            'max_storage_mb' => $plan->max_storage_mb,
        ];
    }

    /**
     * The names of the platform roles, used by middleware and seeders.
     *
     * @return list<string>
     */
    public function platformRoleNames(): array
    {
        return [
            RoleEnum::SUPER_ADMINISTRATOR->roleName(),
            RoleEnum::PLATFORM_ADMINISTRATOR->roleName(),
        ];
    }

    /**
     * The settings service (exposed for usage-limit thresholds).
     */
    public function settings(): SubscriptionSettingsService
    {
        return $this->settings;
    }
}