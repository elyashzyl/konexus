<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionSettingsService;
use Illuminate\Database\Seeder;

/**
 * Seeds the platform subscription domain: settings, default plans, the
 * platform-administrator role and its granular permissions. Idempotent.
 */
class PlatformSubscriptionSeeder extends Seeder
{
    /**
     * The granular permissions granted to the platform-administrator role.
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'platform.tenants.viewAny',
        'platform.tenants.view',
        'platform.tenants.create',
        'platform.tenants.update',
        'platform.tenants.delete',
        'platform.tenants.restore',
        'platform.tenants.forceDelete',
        'platform.tenants.manage',
        'platform.plans.viewAny',
        'platform.plans.view',
        'platform.plans.create',
        'platform.plans.update',
        'platform.plans.delete',
        'platform.plans.restore',
        'platform.plans.forceDelete',
        'platform.subscriptions.viewAny',
        'platform.subscriptions.view',
        'platform.subscriptions.create',
        'platform.subscriptions.update',
        'platform.subscriptions.delete',
        'platform.subscriptions.restore',
        'platform.subscriptions.forceDelete',
        'platform.subscriptions.manage',
        'platform.billing.viewAny',
        'platform.billing.view',
        'platform.billing.create',
        'platform.billing.update',
        'platform.billing.delete',
        'platform.billing.restore',
        'platform.billing.forceDelete',
        'platform.billing.manage',
        'platform.licenses.viewAny',
        'platform.licenses.view',
        'platform.licenses.create',
        'platform.licenses.update',
        'platform.licenses.delete',
        'platform.licenses.restore',
        'platform.licenses.forceDelete',
        'platform.licenses.manage',
        'platform.usage.viewAny',
        'platform.usage.view',
        'platform.usage.create',
        'platform.usage.update',
        'platform.usage.delete',
        'platform.usage.restore',
        'platform.usage.forceDelete',
        'platform.audit.viewAny',
        'platform.audit.view',
        'platform.audit.create',
        'platform.audit.update',
        'platform.audit.delete',
        'platform.audit.restore',
        'platform.audit.forceDelete',
        'platform.settings.viewAny',
        'platform.settings.view',
        'platform.settings.create',
        'platform.settings.update',
        'platform.settings.delete',
        'platform.settings.restore',
        'platform.settings.forceDelete',
        'platform.features.viewAny',
        'platform.features.view',
        'platform.features.create',
        'platform.features.update',
        'platform.features.delete',
        'platform.features.restore',
        'platform.features.forceDelete',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedRole();
        $this->seedPermissions();
        $this->seedSettings();
        $this->seedPlans();
    }

    /**
     * Ensure the platform-administrator role exists.
     */
    protected function seedRole(): void
    {
        Role::query()->firstOrCreate(
            ['name' => RoleEnum::PLATFORM_ADMINISTRATOR->roleName(), 'guard_name' => 'web'],
            [
                'label' => RoleEnum::PLATFORM_ADMINISTRATOR->label(),
                'description' => RoleEnum::PLATFORM_ADMINISTRATOR->description(),
            ]
        );
    }

    /**
     * Create the granular permissions and attach them to the platform role.
     */
    protected function seedPermissions(): void
    {
        $role = Role::query()
            ->where('name', RoleEnum::PLATFORM_ADMINISTRATOR->roleName())
            ->first();

        if (! $role) {
            return;
        }

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    /**
     * Seed the platform subscription settings.
     */
    protected function seedSettings(): void
    {
        app(SubscriptionSettingsService::class)->seedDefaults();
    }

    /**
     * Create the default plans with their feature catalogs (idempotent).
     */
    protected function seedPlans(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'code' => 'starter',
                'description' => 'For small schools just getting started.',
                'billing_cycle' => 'monthly',
                'monthly_price' => 199,
                'annual_price' => 1990,
                'trial_days' => 14,
                'max_students' => 500,
                'max_staff' => 30,
                'max_branches' => 1,
                'max_users' => 20,
                'max_storage_mb' => 5120,
                'features' => ['students', 'enrollment', 'academic', 'attendance', 'reports', 'parent-portal', 'student-portal', 'notifications'],
            ],
            [
                'name' => 'Standard',
                'code' => 'standard',
                'description' => 'The balanced plan for growing schools.',
                'billing_cycle' => 'monthly',
                'monthly_price' => 399,
                'annual_price' => 3990,
                'trial_days' => 14,
                'max_students' => 2000,
                'max_staff' => 80,
                'max_branches' => 3,
                'max_users' => 60,
                'max_storage_mb' => 20480,
                'features' => ['students', 'enrollment', 'academic', 'attendance', 'finance', 'library', 'clinic', 'guidance', 'reports', 'analytics', 'parent-portal', 'student-portal', 'teacher-portal', 'notifications'],
            ],
            [
                'name' => 'Enterprise',
                'code' => 'enterprise',
                'description' => 'Unlimited scale, advanced reporting and integrations.',
                'billing_cycle' => 'annual',
                'monthly_price' => 899,
                'annual_price' => 8990,
                'trial_days' => 14,
                'max_students' => 10000,
                'max_staff' => 300,
                'max_branches' => 10,
                'max_users' => 250,
                'max_storage_mb' => 102400,
                'features' => ['students', 'enrollment', 'academic', 'attendance', 'finance', 'library', 'clinic', 'guidance', 'inventory', 'reports', 'analytics', 'advanced-reports', 'multi-campus', 'api-access', 'custom-branding', 'parent-portal', 'student-portal', 'teacher-portal', 'notifications'],
            ],
        ];

        foreach ($plans as $plan) {
            $features = $plan['features'];
            unset($plan['features']);

            $model = SubscriptionPlan::query()->updateOrCreate(
                ['code' => $plan['code']],
                $plan
            );

            $model->planFeatures()->delete();

            foreach ($features as $feature) {
                $model->planFeatures()->create(['feature_code' => $feature]);
            }
        }
    }
}