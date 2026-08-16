<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleEnum;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionExpiring;
use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureFeatureAccess;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\License;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use App\Models\SubscriptionHistory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FeatureAccessService;
use App\Services\SubscriptionCheckService;
use App\Services\SubscriptionService;
use App\Services\TenantResolverService;
use Database\Seeders\PlatformSubscriptionSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PlatformSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesAndPermissionsSeeder::class, PlatformSubscriptionSeeder::class]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function platformAdmin(): User
    {
        return $this->userWithRole(RoleEnum::PLATFORM_ADMINISTRATOR->roleName());
    }

    private function superAdmin(): User
    {
        return $this->userWithRole(RoleEnum::SUPER_ADMINISTRATOR->roleName());
    }

    private function schoolAdmin(): User
    {
        return $this->userWithRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());
    }

    private function plan(array $attributes = [], array $features = ['attendance', 'reports']): SubscriptionPlan
    {
        $plan = SubscriptionPlan::factory()->create($attributes);

        foreach ($features as $feature) {
            SubscriptionPlanFeature::factory()->create([
                'subscription_plan_id' => $plan->id,
                'feature_code' => $feature,
            ]);
        }

        return $plan;
    }

    private function tenant(array $attributes = []): Tenant
    {
        return Tenant::factory()->create($attributes);
    }

    private function provisionedSchool(): array
    {
        $plan = $this->plan(['trial_days' => null]);
        $tenant = $this->tenant();

        $subscription = app(SubscriptionService::class)->subscribeTenant($tenant, $plan);

        return compact('plan', 'tenant', 'subscription');
    }

    public function test_platform_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/platform/dashboard')->assertStatus(401);
        $this->getJson('/api/v1/platform/tenants')->assertStatus(401);
        $this->getJson('/api/v1/platform/plans')->assertStatus(401);
        $this->getJson('/api/v1/platform/subscriptions')->assertStatus(401);
        $this->getJson('/api/v1/platform/invoices')->assertStatus(401);
        $this->getJson('/api/v1/platform/licenses')->assertStatus(401);
        $this->getJson('/api/v1/platform/usage')->assertStatus(401);
        $this->getJson('/api/v1/platform/audit')->assertStatus(401);
        $this->getJson('/api/v1/platform/settings/grouped')->assertStatus(401);
        $this->getJson('/api/v1/subscription/mine')->assertStatus(401);
    }

    public function test_school_admin_and_plain_user_cannot_access_platform_endpoints(): void
    {
        $this->actingAs($this->schoolAdmin(), 'sanctum')
            ->getJson('/api/v1/platform/tenants')
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->actingAs($this->schoolAdmin(), 'sanctum')
            ->postJson('/api/v1/platform/subscriptions/provision', ['tenant_id' => 1, 'plan_id' => 1])
            ->assertStatus(403);

        $plain = $this->userWithRole('teacher');

        $this->actingAs($plain, 'sanctum')
            ->getJson('/api/v1/platform/dashboard')
            ->assertStatus(403);
    }

    public function test_platform_admin_manages_tenants(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        $school = SchoolProfile::factory()->create(['name' => 'Maple Grove Academy', 'is_active' => true]);

        $store = $this->postJson('/api/v1/platform/tenants', [
            'school_profile_id' => $school->id,
            'name' => 'Maple Grove Academy',
        ])->assertCreated()->assertJsonPath('success', true);

        $id = $store->json('data.id');
        $this->assertDatabaseHas('tenants', ['id' => $id, 'status' => 'active']);

        $this->getJson("/api/v1/platform/tenants/{$id}")->assertOk();

        $this->putJson("/api/v1/platform/tenants/{$id}", ['name' => 'Maple Grove International School'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Maple Grove International School');

        // Suspend and resume.
        $this->postJson("/api/v1/platform/tenants/{$id}/suspend", ['reason' => 'Payment failure'])
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');
        $this->assertDatabaseHas('subscription_history', ['tenant_id' => $id, 'action' => 'suspended']);

        $this->postJson("/api/v1/platform/tenants/{$id}/resume", ['reason' => 'Resolved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->deleteJson("/api/v1/platform/tenants/{$id}")->assertOk();
        $this->assertSoftDeleted('tenants', ['id' => $id]);
    }

    public function test_platform_admin_manages_plans_with_features(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        $store = $this->postJson('/api/v1/platform/plans', [
            'name' => 'Premium',
            'code' => 'premium',
            'billing_cycle' => 'monthly',
            'monthly_price' => 599,
            'trial_days' => 14,
            'features' => ['attendance', 'finance', 'library'],
        ])->assertCreated();

        $id = $store->json('data.id');
        $this->assertSame(3, SubscriptionPlanFeature::query()->where('subscription_plan_id', $id)->count());

        $this->putJson("/api/v1/platform/plans/{$id}", [
            'name' => 'Premium Plus',
            'code' => 'premium',
            'monthly_price' => 699,
            'features' => ['attendance', 'finance', 'library', 'analytics'],
        ])->assertOk();

        $this->assertSame(4, SubscriptionPlanFeature::query()->where('subscription_plan_id', $id)->count());
        $this->assertDatabaseHas('subscription_plan_features', [
            'subscription_plan_id' => $id,
            'feature_code' => 'analytics',
        ]);

        $this->postJson('/api/v1/platform/plans', [
            'name' => 'Broken',
            'code' => 'broken',
            'features' => ['not-a-real-feature'],
        ])->assertStatus(422);
    }

    public function test_subscription_provisioning_starts_trial_and_issues_license(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        $plan = $this->plan(['trial_days' => 14], ['attendance', 'reports']);
        $tenant = $this->tenant();

        $response = $this->postJson('/api/v1/platform/subscriptions/provision', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ])->assertCreated();

        $subscriptionId = $response->json('data.id');
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscriptionId,
            'tenant_id' => $tenant->id,
            'status' => 'trial',
            'trial_status' => 'active',
        ]);

        // A license was issued and masked in the response.
        $license = License::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertNotEquals($license->license_key, $license->maskedKey());
        $this->assertStringContainsString('****', $license->maskedKey());

        // The trial plan features were synced onto the subscription.
        $this->assertDatabaseHas('subscription_features', [
            'subscription_id' => $subscriptionId,
            'feature_code' => 'attendance',
            'is_enabled' => true,
        ]);

        // Audit trail recorded.
        $this->assertDatabaseHas('subscription_history', ['subscription_id' => $subscriptionId, 'action' => 'trial_started']);
        $this->assertDatabaseHas('subscription_history', ['tenant_id' => $tenant->id, 'action' => 'license_created']);
    }

    public function test_tenant_cannot_have_two_active_subscriptions(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        $plan = $this->plan();
        $tenant = $this->tenant();

        $this->postJson('/api/v1/platform/subscriptions/provision', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ])->assertCreated();

          $this->postJson('/api/v1/platform/subscriptions/provision', [
              'tenant_id' => $tenant->id,
              'plan_id' => $plan->id,
          ])->assertStatus(409);
      }

      public function test_subscription_can_be_manually_granted_without_payment(): void
      {
          $this->actingAs($this->platformAdmin(), 'sanctum');

          $plan = $this->plan(['trial_days' => null], ['attendance', 'reports']);
          $tenant = $this->tenant();

          $response = $this->postJson('/api/v1/platform/subscriptions/manual-grant', [
              'tenant_id' => $tenant->id,
              'plan_id' => $plan->id,
              'start_date' => now()->subDays(10)->toDateString(),
              'expiration_date' => now()->addMonths(6)->toDateString(),
              'amount' => 0,
              'auto_renewal' => false,
              'notes' => 'Courtesy access for the pilot semester.',
          ])->assertCreated();

          $subscriptionId = $response->json('data.id');

          $this->assertDatabaseHas('subscriptions', [
              'id' => $subscriptionId,
              'tenant_id' => $tenant->id,
              'plan_id' => $plan->id,
              'status' => 'active',
              'amount' => 0,
              'auto_renewal' => false,
          ]);

          $this->assertSame(now()->addMonths(6)->toDateString(), Subscription::findOrFail($subscriptionId)->expiration_date->toDateString());

          $this->assertDatabaseHas('licenses', ['tenant_id' => $tenant->id, 'plan_id' => $plan->id]);
          $this->assertDatabaseHas('subscription_features', ['subscription_id' => $subscriptionId, 'feature_code' => 'attendance']);
          $this->assertDatabaseHas('subscription_history', ['subscription_id' => $subscriptionId, 'action' => 'manual_grant']);

          // A second active subscription is rejected.
          $this->postJson('/api/v1/platform/subscriptions/manual-grant', [
              'tenant_id' => $tenant->id,
              'plan_id' => $plan->id,
          ])->assertStatus(409);
      }

      public function test_subscription_manual_grant_can_skip_license_issuance(): void
      {
          $this->actingAs($this->platformAdmin(), 'sanctum');

          $plan = $this->plan(['trial_days' => null], ['attendance']);
          $tenant = $this->tenant();

          $this->postJson('/api/v1/platform/subscriptions/manual-grant', [
              'tenant_id' => $tenant->id,
              'plan_id' => $plan->id,
              'issue_license' => false,
          ])->assertCreated();

          $this->assertDatabaseMissing('licenses', ['tenant_id' => $tenant->id]);
          $this->assertDatabaseHas('subscriptions', ['tenant_id' => $tenant->id, 'status' => 'active']);
      }

      public function test_subscription_can_be_manually_granted_by_school(): void
      {
          $this->actingAs($this->platformAdmin(), 'sanctum');

          $plan = $this->plan(['trial_days' => null], ['attendance']);
          $school = SchoolProfile::factory()->create(['short_name' => 'BPHS']);

          // The school has no tenant yet: granting must create it automatically.
          $this->assertDatabaseMissing('tenants', ['school_profile_id' => $school->id]);

          $response = $this->postJson('/api/v1/platform/subscriptions/manual-grant', [
              'school_profile_id' => $school->id,
              'plan_id' => $plan->id,
              'start_date' => now()->toDateString(),
              'expiration_date' => now()->addMonths(6)->toDateString(),
              'notes' => 'Granted for the pilot semester.',
          ])->assertCreated();

          $subscriptionId = $response->json('data.id');

          $this->assertDatabaseHas('tenants', ['school_profile_id' => $school->id, 'code' => 'BPHS', 'status' => 'active']);
          $tenant = Tenant::query()->where('school_profile_id', $school->id)->firstOrFail();

          $this->assertDatabaseHas('subscriptions', [
              'id' => $subscriptionId,
              'tenant_id' => $tenant->id,
              'plan_id' => $plan->id,
              'status' => 'active',
          ]);

          // A second grant to the same school reuses the same tenant.
          $school->update(['short_name' => 'BPHS2']);

          $this->postJson('/api/v1/platform/subscriptions/manual-grant', [
              'school_profile_id' => $school->id,
              'plan_id' => $plan->id,
          ])->assertStatus(409);

          $this->assertSame(1, Tenant::query()->where('school_profile_id', $school->id)->count());
      }

      public function test_subscription_manual_grant_requires_school_or_tenant(): void
      {
          $this->actingAs($this->platformAdmin(), 'sanctum');

          $plan = $this->plan(['trial_days' => null], ['attendance']);

          $this->postJson('/api/v1/platform/subscriptions/manual-grant', [
              'plan_id' => $plan->id,
          ])
              ->assertUnprocessable()
              ->assertJsonValidationErrors(['school_profile_id', 'tenant_id']);
      }

      public function test_subscription_lifecycle_renew_suspend_resume_cancel(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        ['tenant' => $tenant, 'subscription' => $subscription] = $this->provisionedSchool();

        $this->postJson("/api/v1/platform/subscriptions/{$subscription->id}/renew")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->postJson("/api/v1/platform/subscriptions/{$subscription->id}/suspend", ['reason' => 'Missed payment'])
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'suspend_reason' => 'Missed payment']);

        $this->postJson("/api/v1/platform/subscriptions/{$subscription->id}/resume", ['reason' => 'Payment received'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->postJson("/api/v1/platform/subscriptions/{$subscription->id}/cancel", ['reason' => 'School closed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('subscription_history', ['subscription_id' => $subscription->id, 'action' => 'cancelled']);
    }

    public function test_change_plan_resyncs_features_and_records_history(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        ['tenant' => $tenant, 'subscription' => $subscription] = $this->provisionedSchool();

        $upgraded = $this->plan(['code' => 'upgraded', 'name' => 'Upgraded'], ['attendance', 'finance', 'analytics']);

        $this->postJson("/api/v1/platform/subscriptions/{$subscription->id}/change-plan", [
            'plan_id' => $upgraded->id,
            'reason' => 'Growth',
        ])->assertOk()->assertJsonPath('data.plan_id', $upgraded->id);

        $this->assertDatabaseHas('subscription_features', ['subscription_id' => $subscription->id, 'feature_code' => 'analytics', 'is_enabled' => true]);
        $this->assertDatabaseMissing('subscription_features', ['subscription_id' => $subscription->id, 'feature_code' => 'reports']);
        $this->assertDatabaseHas('subscription_history', ['subscription_id' => $subscription->id, 'action' => 'plan_changed']);
    }

    public function test_feature_toggle_is_reflected_in_access(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        ['tenant' => $tenant, 'subscription' => $subscription] = $this->provisionedSchool();

        $features = app(FeatureAccessService::class);
        $this->assertTrue($features->allowsFeature($tenant, 'attendance'));
        $this->assertFalse($features->allowsFeature($tenant, 'finance'));

        $this->postJson("/api/v1/platform/subscriptions/{$subscription->id}/features", [
            'feature_code' => 'finance',
            'is_enabled' => true,
        ])->assertOk();

        $tenant->unsetRelation('subscriptions');
        $this->assertTrue($features->allowsFeature($tenant->refresh(), 'finance'));
        $this->assertDatabaseHas('subscription_history', ['subscription_id' => $subscription->id, 'action' => 'feature_enabled']);
    }

    public function test_license_keys_are_masked_and_reveal_requires_platform_admin(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        ['tenant' => $tenant, 'subscription' => $subscription] = $this->provisionedSchool();

        $license = License::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $list = $this->getJson('/api/v1/platform/licenses')->assertOk();
        $this->assertStringContainsString('****', $list->json('data.items.0.license_key'));
        $this->assertNotEquals($license->license_key, $list->json('data.items.0.license_key'));

        // Reveal only happens on explicit opt-in.
        $revealed = $this->getJson("/api/v1/platform/licenses/{$license->id}?reveal=1")->assertOk();
        $this->assertEquals($license->license_key, $revealed->json('data.license_key'));
        $this->assertTrue($revealed->json('data.revealed'));

        // Regeneration produces a new key.
        $this->postJson("/api/v1/platform/licenses/{$license->id}/regenerate")->assertOk();
        $this->assertDatabaseMissing('licenses', ['id' => $license->id, 'license_key' => $license->license_key]);
        $this->assertDatabaseHas('subscription_history', ['tenant_id' => $tenant->id, 'action' => 'license_regenerated']);
    }

    public function test_billing_generates_invoice_records_payment_and_reconciles(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        ['tenant' => $tenant, 'subscription' => $subscription] = $this->provisionedSchool();

        $generate = $this->postJson('/api/v1/platform/invoices/generate', [
            'subscription_id' => $subscription->id,
            'amount' => 299,
            'tax_rate' => 12,
        ])->assertCreated();

        $invoiceId = $generate->json('data.id');
        $this->assertGreaterThan(299, (float) $generate->json('data.total'));
        $this->assertDatabaseHas('subscription_invoices', ['id' => $invoiceId, 'status' => 'pending']);
        $this->assertDatabaseHas('subscription_history', ['subscription_id' => $subscription->id, 'action' => 'invoice_created']);

        $this->postJson('/api/v1/platform/payments', [
            'invoice_id' => $invoiceId,
            'amount' => $generate->json('data.total'),
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF-123',
        ])->assertCreated();

        $invoice = SubscriptionInvoice::query()->findOrFail($invoiceId);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0.0, $invoice->balance());
        $this->assertDatabaseHas('subscription_history', ['subscription_id' => $subscription->id, 'action' => 'payment_recorded']);

        // Partial payment leaves the invoice partially paid.
        $this->postJson('/api/v1/platform/invoices/generate', [
            'subscription_id' => $subscription->id,
            'amount' => 100,
        ])->assertCreated();

        $this->postJson('/api/v1/platform/payments', [
            'invoice_id' => $invoiceId,
            'amount' => 1,
        ])->assertStatus(409);
    }

    public function test_scheduled_check_enters_grace_then_enforces_expiration(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        $plan = $this->plan();
        $tenant = $this->tenant();

        $subscription = app(SubscriptionService::class)->subscribeTenant($tenant, $plan);
        $subscription->update([
            'status' => 'active',
            'expiration_date' => now()->subDays(2)->toDateString(),
            'grace_days' => 7,
            'expiration_behavior' => 'grace_period',
        ]);

        $counts = app(SubscriptionCheckService::class)->run();

        $subscription->refresh();
        $this->assertEquals('grace_period', $subscription->status);
        $this->assertNotNull($subscription->grace_ends_at);
        $this->assertGreaterThan(0, $counts['entered_grace']);

        // After the grace window the subscription is enforced as expired.
        $subscription->update([
            'grace_ends_at' => now()->subDay()->toDateString(),
            'expiration_date' => now()->subDays(10)->toDateString(),
        ]);

        Event::fake([SubscriptionExpired::class]);

        app(SubscriptionCheckService::class)->run();

        $subscription->refresh();
        $this->assertEquals('expired', $subscription->status);
        Event::assertDispatched(SubscriptionExpired::class);
    }

    public function test_suspended_behavior_marks_subscription_suspended_after_grace(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        $plan = $this->plan();
        $tenant = $this->tenant();
        $subscription = app(SubscriptionService::class)->subscribeTenant($tenant, $plan);

        $subscription->update([
            'status' => 'active',
            'expiration_date' => now()->subDays(30)->toDateString(),
            'grace_days' => 0,
            'grace_ends_at' => now()->subDays(29)->toDateString(),
            'expiration_behavior' => 'suspended',
        ]);

        app(SubscriptionCheckService::class)->run();

        $subscription->refresh();
        $this->assertEquals('suspended', $subscription->status);
    }

    public function test_scheduled_check_dispatches_expiring_notice(): void
    {
        Event::fake([SubscriptionExpiring::class]);

        $plan = $this->plan();
        $tenant = $this->tenant();
        $subscription = app(SubscriptionService::class)->subscribeTenant($tenant, $plan);

        $subscription->update([
            'status' => 'active',
            'expiration_date' => now()->addDays(10)->toDateString(),
        ]);

        app(SubscriptionCheckService::class)->run();

        Event::assertDispatched(SubscriptionExpiring::class);

        // Idempotent: a second run does not re-dispatch today.
        Event::fake([SubscriptionExpiring::class]);
        app(SubscriptionCheckService::class)->run();
        Event::assertNotDispatched(SubscriptionExpiring::class);
    }

    public function test_overdue_invoices_are_flagged_by_check_service(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        ['tenant' => $tenant, 'subscription' => $subscription] = $this->provisionedSchool();

        $invoice = SubscriptionInvoice::factory()->create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'status' => 'pending',
            'due_date' => now()->subDays(3)->toDateString(),
        ]);

        app(SubscriptionCheckService::class)->run();

        $this->assertDatabaseHas('subscription_invoices', ['id' => $invoice->id, 'status' => 'overdue']);
    }

    public function test_feature_gating_middleware_enforces_subscription(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        $plan = $this->plan(['trial_days' => null], ['attendance']);
        $tenant = $this->tenant();
        $subscription = app(SubscriptionService::class)->subscribeTenant($tenant, $plan);

        $middleware = app(EnsureFeatureAccess::class);

        $studentUser = $this->bindUserToTenant($tenant);

        $request = Request::create('/test-feature', 'GET');
        $request->headers->set('X-Tenant-Id', (string) $tenant->id);
        $request->setUserResolver(fn () => $studentUser);

        // An entitled feature passes the gate.
        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]), 'attendance');
        $this->assertEquals(200, $response->getStatusCode());

        // A non-entitled feature is rejected with a forbidden response.
        try {
            $middleware->handle($request, fn () => response()->json(['ok' => true]), 'finance');
            $this->fail('Expected an ApiException for a non-entitled feature.');
        } catch (ApiException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        // Platform administrators bypass feature gating entirely.
        $adminRequest = Request::create('/test-feature', 'GET');
        $adminRequest->setUserResolver(fn () => $this->platformAdmin());

        $response = $middleware->handle($adminRequest, fn () => response()->json(['ok' => true]), 'finance');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_school_user_reads_only_own_subscription(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        ['tenant' => $tenant, 'subscription' => $subscription] = $this->provisionedSchool();

        $studentUser = $this->bindUserToTenant($tenant);

        // The school user sees their own tenant summary.
        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/subscription/mine')
            ->assertOk()
            ->assertJsonPath('data.tenant.id', $tenant->id)
            ->assertJsonPath('data.subscription.id', $subscription->id)
            ->assertJsonPath('data.read_only', true);

        // But cannot mutate platform resources.
        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/platform/tenants', ['name' => 'Hacked School'])
            ->assertStatus(403);

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/platform/tenants')
            ->assertStatus(403);
    }

    public function test_audit_trail_is_recorded_and_accessible(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        ['tenant' => $tenant, 'subscription' => $subscription] = $this->provisionedSchool();

        $this->assertDatabaseHas('subscription_history', ['tenant_id' => $tenant->id, 'action' => 'created']);

        $audit = $this->getJson('/api/v1/platform/audit?filter[tenant_id]='.$tenant->id)->assertOk();
        $this->assertNotEmpty($audit->json('data.items'));

        $actions = $this->getJson('/api/v1/platform/audit/actions')->assertOk();
        $this->assertContains('plan_changed', $actions->json('data.*.value'));

        $this->getJson('/api/v1/platform/audit/999999')->assertStatus(404);
    }

    public function test_usage_snapshot_and_limit_warnings(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        $plan = $this->plan(['trial_days' => null, 'max_students' => 5], ['students']);
        $tenant = $this->tenant();

        $schoolProfile = SchoolProfile::factory()->create();
        $tenant->update(['school_profile_id' => $schoolProfile->id]);

        $campus = Campus::factory()->create(['school_profile_id' => $schoolProfile->id]);
        $year = AcademicYear::factory()->create();

        foreach ([7, 8, 9] as $i) {
            $student = Student::factory()->create();

            Enrollment::create([
                'student_id' => $student->id,
                'campus_id' => $campus->id,
                'academic_year_id' => $year->id,
                'grade_level_id' => \App\Models\GradeLevel::factory()->create([
                    'name' => "Grade {$i}",
                    'code' => (string) $i,
                ])->id,
                'enrollment_number' => fake()->unique()->numerify('ENR-####-######'),
                'reference_number' => fake()->unique()->numerify('KXN-EN-####-######'),
                'status' => 'enrolled',
                'enrollment_type' => 'new-student',
                'enrollment_date' => now()->toDateString(),
                'is_active' => true,
            ]);
        }

        app(SubscriptionService::class)->subscribeTenant($tenant, $plan);

        $this->postJson("/api/v1/platform/usage/tenants/{$tenant->id}/snapshot")->assertOk();
        $this->assertDatabaseHas('subscription_usage', ['tenant_id' => $tenant->id, 'students_count' => 3]);

        $status = $this->getJson("/api/v1/platform/usage/tenants/{$tenant->id}")->assertOk();
        $this->assertEquals(3, $status->json('data.limit_status.usage.students_count'));
        $this->assertEquals(5, $status->json('data.limit_status.limits.max_students'));
    }

    public function test_platform_dashboard_returns_metrics(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        $plan = $this->plan(['trial_days' => null]);
        $tenant = $this->tenant();
        app(SubscriptionService::class)->subscribeTenant($tenant, $plan);

        $this->getJson('/api/v1/platform/dashboard')
            ->assertOk()
            ->assertJsonPath('data.metrics.total_tenants', 1)
            ->assertJsonPath('data.metrics.active_subscriptions', 1);
    }

    public function test_settings_can_be_grouped_and_bulk_updated(): void
    {
        $this->actingAs($this->platformAdmin(), 'sanctum');

        $grouped = $this->getJson('/api/v1/platform/settings/grouped')->assertOk();
        $this->assertArrayHasKey('general', $grouped->json('data'));

        $this->putJson('/api/v1/platform/settings/bulk', [
            'settings' => ['default_grace_days' => 30, 'currency' => 'USD'],
        ])->assertOk();

        $this->assertDatabaseHas('subscription_settings', ['key' => 'default_grace_days', 'value' => '30']);
        $this->assertDatabaseHas('subscription_settings', ['key' => 'currency', 'value' => 'USD']);
    }

    public function test_public_plans_catalog_is_accessible_without_authentication(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSubscriptionSeeder::class);

        $inactive = SubscriptionPlan::factory()->create(['name' => 'Legacy', 'code' => 'legacy', 'is_active' => false]);

        $response = $this->getJson('/api/v1/public/plans')->assertOk();

        $response->assertJsonStructure(['data' => [
            '*' => ['id', 'name', 'code', 'description', 'billing_cycle', 'monthly_price', 'annual_price', 'trial_days', 'features'],
        ]]);

        $plans = $response->json('data');
        $this->assertCount(3, $plans);
        $this->assertEqualsCanonicalizing(['Starter', 'Standard', 'Enterprise'], array_column($plans, 'name'));
        $this->assertNotContains('legacy', array_column($plans, 'code'));

        $this->assertContains(
            ['code' => 'students', 'label' => 'Students'],
            $plans[0]['features'],
        );
    }

    private function bindUserToTenant(Tenant $tenant): User
    {
        if (! $tenant->school_profile_id) {
            $tenant->update(['school_profile_id' => SchoolProfile::factory()->create()->id]);
        }

        $campus = Campus::factory()->create(['school_profile_id' => $tenant->school_profile_id]);

        $user = User::factory()->create();
        $student = Student::factory()->create(['user_id' => $user->id]);

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'campus_id' => $campus->id,
            'academic_year_id' => AcademicYear::factory()->create()->id,
        ]);

        return $user;
    }
}