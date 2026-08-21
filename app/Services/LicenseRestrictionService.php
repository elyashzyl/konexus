<?php

namespace App\Services;

use App\Enums\Platform\LicenseStatus;
use App\Exceptions\ApiException;
use App\Models\Campus;
use App\Models\Employee;
use App\Models\License;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;

/**
 * Enforces license-based usage restrictions.
 *
 * The tenant's active license is the primary source of usage limits; any
 * limit the license does not define falls back to the subscription plan.
 * Creating restricted resources is hard-blocked once the matching limit is
 * reached while existing records remain untouched.
 *
 * Tenants that cannot be resolved (platform administrators operating across
 * schools and unmanaged schools without a tenant record) are never restricted
 * so existing flows keep working unchanged.
 */
class LicenseRestrictionService
{
    /**
     * Restricted resources keyed by resource name:
     * [license column, plan column, human label].
     *
     * @var array<string, array{?string, string, string}>
     */
    private const RESOURCES = [
        'students' => ['max_students', 'max_students', 'student'],
        'staff' => [null, 'max_staff', 'staff'],
        'users' => ['max_users', 'max_users', 'user account'],
        'branches' => ['max_branches', 'max_branches', 'campus'],
    ];

    /**
     * Model classes mapped to the restricted resource they count against.
     *
     * @var array<class-string, string>
     */
    private const MODEL_RESOURCES = [
        Student::class => 'students',
        Employee::class => 'staff',
        Staff::class => 'staff',
        Teacher::class => 'staff',
        Campus::class => 'branches',
    ];

    public function __construct(
        private readonly TenantResolverService $resolver,
        private readonly FeatureAccessService $features,
    ) {}

    /**
     * Block creation when the tenant's license limit for the resource is
     * reached. A no-op for unrestricted tenants and unknown resources.
     */
    public function assertCanCreate(?User $user, string $resource, ?int $schoolProfileId = null): void
    {
        if (! isset(self::RESOURCES[$resource])) {
            return;
        }

        $tenant = $this->tenantFor($user, $schoolProfileId);

        if ($tenant === null) {
            return;
        }

        $limit = $this->limitFor($tenant, $resource);

        if ($limit === null || $limit <= 0) {
            return;
        }

        $used = $this->usageFor($tenant, $resource);

        if ($used >= $limit) {
            throw ApiException::forbidden($this->limitMessage($resource, $used, $limit));
        }
    }

    /**
     * The resource key a model class counts against, or null when the model
     * is not license-restricted.
     */
    public function resourceForModel(string $modelClass): ?string
    {
        return self::MODEL_RESOURCES[$modelClass] ?? null;
    }

    /**
     * The enforced limit for a resource: the active license value when set,
     * otherwise the subscription plan limit, otherwise unlimited (null).
     */
    public function limitFor(Tenant $tenant, string $resource): ?int
    {
        if (! isset(self::RESOURCES[$resource])) {
            return null;
        }

        [, $planColumn] = self::RESOURCES[$resource];

        return $this->effectiveLimits($tenant)[$planColumn] ?? null;
    }

    /**
     * The effective limits of a tenant: the subscription plan limits with any
     * active license values taking precedence per column.
     *
     * @return array<string, int|null>
     */
    public function effectiveLimits(Tenant $tenant): array
    {
        $limits = $this->features->planLimits($tenant);

        $license = $this->activeLicense($tenant);

        if ($license === null) {
            return $limits;
        }

        foreach (['max_students', 'max_users', 'max_branches', 'max_storage_mb'] as $column) {
            if ($license->{$column} !== null) {
                $limits[$column] = (int) $license->{$column};
            }
        }

        return $limits;
    }

    /**
     * How many records of the resource the tenant currently uses.
     */
    public function usageFor(Tenant $tenant, string $resource): int
    {
        $schoolProfileId = $tenant->school_profile_id;

        if ($schoolProfileId === null) {
            return 0;
        }

        return match ($resource) {
            'students' => Student::query()->where('school_profile_id', $schoolProfileId)->count(),
            'staff' => Employee::query()->where('school_profile_id', $schoolProfileId)->count(),
            'users' => User::query()->where('school_profile_id', $schoolProfileId)->count(),
            'branches' => Campus::query()->where('school_profile_id', $schoolProfileId)->count(),
            default => 0,
        };
    }

    /**
     * The tenant's current active, non-expired license.
     */
    public function activeLicense(Tenant $tenant): ?License
    {
        return $tenant->licenses()
            ->where('status', LicenseStatus::ACTIVE->value)
            ->where(fn ($query) => $query
                ->whereNull('expiration_date')
                ->orWhere('expiration_date', '>=', today()))
            ->latest('id')
            ->first();
    }

    /**
     * The tenant whose limits apply: an explicit school profile wins,
     * otherwise the tenant of the acting user.
     */
    private function tenantFor(?User $user, ?int $schoolProfileId): ?Tenant
    {
        if ($schoolProfileId !== null) {
            return Tenant::query()->where('school_profile_id', $schoolProfileId)->first();
        }

        return $this->resolver->resolveForUser($user);
    }

    /**
     * The error message shown when a limit is hit.
     */
    private function limitMessage(string $resource, int $used, int $limit): string
    {
        $label = self::RESOURCES[$resource][2];

        return "The license limit for {$label} records has been reached ({$used} of {$limit}). "
            .'Please upgrade the school\'s subscription or contact the platform administrator.';
    }
}
