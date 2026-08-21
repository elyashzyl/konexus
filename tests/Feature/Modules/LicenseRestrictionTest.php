<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleEnum;
use App\Models\Campus;
use App\Models\Employee;
use App\Models\License;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_student_creation_blocks_at_license_limit(): void
    {
        $school = SchoolProfile::factory()->create();
        $tenant = Tenant::factory()->create(['school_profile_id' => $school->id]);
        License::factory()->create(['tenant_id' => $tenant->id, 'max_students' => 2]);

        $admin = $this->schoolAdmin($school);

        $this->actingAs($admin, 'sanctum');

        foreach (['Ana', 'Ben'] as $first) {
            $this->createStudent($first)->assertCreated();
        }

        $this->createStudent('Carla')
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'license limit for student'));

        $this->assertSame(2, Student::query()->count());
    }

    public function test_license_limits_override_plan_limits(): void
    {
        $school = SchoolProfile::factory()->create();
        $tenant = Tenant::factory()->create(['school_profile_id' => $school->id]);
        $plan = SubscriptionPlan::factory()->create(['max_students' => 1]);
        app(\App\Services\SubscriptionService::class)->subscribeTenant($tenant, $plan);
        License::factory()->create(['tenant_id' => $tenant->id, 'max_students' => 3]);

        $this->actingAs($this->schoolAdmin($school), 'sanctum');

        $this->createStudent('Ana')->assertCreated();
        $this->createStudent('Ben')->assertCreated();

        $this->assertSame(2, Student::query()->count());
    }

    public function test_staff_falls_back_to_plan_limit_when_license_has_no_staff_limit(): void
    {
        $school = SchoolProfile::factory()->create();
        $tenant = Tenant::factory()->create(['school_profile_id' => $school->id]);
        $plan = SubscriptionPlan::factory()->create(['max_staff' => 1]);
        app(\App\Services\SubscriptionService::class)->subscribeTenant($tenant, $plan);
        License::factory()->create(['tenant_id' => $tenant->id, 'max_students' => 100]);

        $this->actingAs($this->schoolAdmin($school), 'sanctum');

        $this->postJson('/api/v1/employees', [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'gender' => 'female',
            'employment_type' => 'staff',
        ])->assertCreated();

        $this->postJson('/api/v1/employees', [
            'first_name' => 'Jose',
            'last_name' => 'Rizal',
            'gender' => 'male',
            'employment_type' => 'teaching',
        ])->assertStatus(403)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'license limit for staff'));

        $this->assertSame(1, Employee::query()->count());
    }

    public function test_campus_creation_blocked_at_branch_limit(): void
    {
        $school = SchoolProfile::factory()->create();
        $tenant = Tenant::factory()->create(['school_profile_id' => $school->id]);
        $plan = SubscriptionPlan::factory()->create(['max_branches' => 1]);
        app(\App\Services\SubscriptionService::class)->subscribeTenant($tenant, $plan);

        $admin = $this->schoolAdmin($school);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/campuses', ['name' => 'Main Campus'])
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/campuses', ['name' => 'Annex Campus'])
            ->assertStatus(403)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'license limit for campus'));

        $this->assertSame(1, Campus::query()->count());
    }

    public function test_user_creation_blocked_at_user_limit(): void
    {
        $school = SchoolProfile::factory()->create();
        $tenant = Tenant::factory()->create(['school_profile_id' => $school->id]);
        License::factory()->create(['tenant_id' => $tenant->id, 'max_users' => 2]);

        $admin = $this->schoolAdmin($school);

        // The acting administrator already occupies one seat.
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Jane Registrar',
                'email' => 'jane@example.com',
                'password' => 'secret123',
                'roles' => ['registrar'],
                'school_profile_id' => $school->id,
            ])
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'John Accounting',
                'email' => 'john@example.com',
                'password' => 'secret123',
                'roles' => ['finance-officer'],
                'school_profile_id' => $school->id,
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'license limit for user account'));
    }

    public function test_expired_license_is_not_enforced(): void
    {
        $school = SchoolProfile::factory()->create();
        $tenant = Tenant::factory()->create(['school_profile_id' => $school->id]);
        License::factory()->create([
            'tenant_id' => $tenant->id,
            'max_students' => 1,
            'expiration_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($this->schoolAdmin($school), 'sanctum');

        $this->createStudent('Ana')->assertCreated();
        $this->createStudent('Ben')->assertCreated();

        $this->assertSame(2, Student::query()->count());
    }

    public function test_unmanaged_schools_are_not_restricted(): void
    {
        $school = SchoolProfile::factory()->create();
        // No tenant and no subscription: the school stays unrestricted.

        $this->actingAs($this->schoolAdmin($school), 'sanctum');

        $this->createStudent('Ana')->assertCreated();
        $this->createStudent('Ben')->assertCreated();
        $this->createStudent('Carla')->assertCreated();

        $this->assertSame(3, Student::query()->count());
    }

    public function test_bulk_import_blocked_at_limit(): void
    {
        $school = SchoolProfile::factory()->create();
        $tenant = Tenant::factory()->create(['school_profile_id' => $school->id]);
        License::factory()->create(['tenant_id' => $tenant->id, 'max_students' => 1]);

        $this->actingAs($this->schoolAdmin($school), 'sanctum');

        $this->createStudent('Ana')->assertCreated();

        $this->postJson('/api/v1/students/import', [
            'rows' => [
                ['first_name' => 'Ben', 'last_name' => 'Dela Cruz', 'gender' => 'male', 'birth_date' => '2012-04-15'],
            ],
        ])->assertStatus(403);
    }

    private function schoolAdmin(SchoolProfile $school): User
    {
        $user = User::factory()->create(['school_profile_id' => $school->id]);
        $user->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        return $user;
    }

    private function createStudent(string $firstName): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/students', [
            'first_name' => $firstName,
            'last_name' => 'Dela Cruz',
            'gender' => 'male',
            'birth_date' => '2012-04-15',
        ]);
    }
}
